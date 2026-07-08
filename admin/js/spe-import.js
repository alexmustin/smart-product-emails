/* global jQuery, smartproductemailsImport */
( function ( $ ) {
	'use strict';

	var cfg = smartproductemailsImport;
	var state = {
		data: null,
		conflictStrategy: 'overwrite',
		cancelRequested: false,
		running: false,
	};

	function esc( str ) {
		return $( '<div>' ).text( str == null ? '' : String( str ) ).html();
	}

	function ajax( action, extra ) {
		var payload = $.extend( { action: action, nonce: cfg.nonce }, extra || {} );
		return $.post( cfg.ajaxurl, payload );
	}

	function goToStep( n ) {
		$( '.spe-import-step' ).hide();
		$( '#spe-import-step-' + n ).show();
		$( '.spe-import-step-indicator' ).removeClass( 'active' );
		$( '.spe-import-step-indicator[data-step="' + n + '"]' ).addClass( 'active' );
	}

	function statusLabel( status ) {
		return { processing: 'Processing', onhold: 'On-Hold', completed: 'Completed' }[ status ] || status;
	}

	function tooltipBadge( label, tooltipText ) {
		return '<span class="spe-import-info-badge">' + esc( label ) +
			' <span class="spe-import-tip" tabindex="0" title="' + esc( tooltipText ) + '">?</span></span>';
	}

	function pluralize( count, singular, plural ) {
		return 1 === count ? singular : plural;
	}

	/**
	 * A deliberately prominent, on-brand promotional banner for the
	 * On-Hold/Completed PRO upsell - shown on both Review (before import)
	 * and Report (after import, the stronger conversion moment since the
	 * user can now see real numbers of what's imported and waiting).
	 *
	 * @param {string} variant 'review' or 'report'.
	 * @param {number} count On-Hold + Completed assignment count.
	 * @param {Object} proInfo {installed, licensed} - from the scan response.
	 * @return {string} HTML, or '' if there's nothing to promote.
	 */
	function renderProBanner( variant, count, proInfo ) {
		if ( ! count || count <= 0 || ( proInfo && proInfo.licensed ) ) {
			return '';
		}

		var installed = !! ( proInfo && proInfo.installed );
		var ctaUrl    = installed ? cfg.urls.proLicense : cfg.urls.proUpgrade;
		var ctaText   = installed ? 'Activate License' : 'Upgrade to PRO';
		var noun      = pluralize( count, 'message', 'messages' );

		var countHtml = '<span class="spe-import-pro-banner-count">' + count + ' ' + esc( noun ) + '</span>';
		var headline  = 'You’re missing out on ' + countHtml + ' for On-Hold &amp; Completed Emails';

		// "Review" (before Start Import) speaks about the import as imminent;
		// "Report" (after it's run) speaks about it as already done.
		var importedClause = ( 'report' === variant )
			? 'They’ve already been imported'
			: 'They will be imported now';

		// Someone with PRO installed but unlicensed just needs to activate,
		// not go buy anything again - different action, different link.
		var actionClause = installed ? 'you’ll need to activate your PRO license' : 'you’ll need to upgrade to PRO';
		var readyClause   = installed
			? 'Once activated, these messages will be active and ready to use immediately.'
			: 'Once you upgrade, these messages will be active and ready to use immediately.';

		var body = 'The On-Hold and Completed status messages are a PRO feature only. ' +
			importedClause + ', but if you want to use them ' + actionClause + '. ' + readyClause;

		return '<div class="spe-import-pro-banner">' +
			'<span class="dashicons dashicons-star-filled"></span>' +
			'<div class="spe-import-pro-banner-text">' +
			'<strong>' + headline + '</strong>' +
			'<p>' + esc( body ) + '</p>' +
			'</div>' +
			'<a href="' + esc( ctaUrl ) + '" target="_blank" rel="noopener noreferrer" class="spe-import-pro-cta">' + esc( ctaText ) + '</a>' +
			'</div>';
	}

	/* ---------------------------------------------------------------- */
	/* Step 1: Discovery                                                 */
	/* ---------------------------------------------------------------- */

	function runScan() {
		$( '#spe-import-step-1' ).html( '<p class="spe-import-loading">Scanning for Woo Custom Emails data…</p>' );

		ajax( 'smartproductemails_import_scan' ).done( function ( response ) {
			if ( ! response || ! response.success ) {
				$( '#spe-import-step-1' ).html( '<p class="spe-import-error">' + esc( cfg.i18n.scanFailed ) + '</p>' );
				return;
			}

			state.data = response.data;

			var job = state.data.job;

			if ( job && 'running' === job.status ) {
				renderInterruptedJob( job );
				goToStep( 4 );
				return;
			}

			if ( job && 'completed' === job.status && ! state.data.exists ) {
				// Nothing left to import and last run already completed - show the report.
				renderReport( job );
				goToStep( 5 );
				return;
			}

			renderDiscovery();
			goToStep( 1 );
		} ).fail( function () {
			$( '#spe-import-step-1' ).html( '<p class="spe-import-error">' + esc( cfg.i18n.scanFailed ) + '</p>' );
		} );
	}

	function renderDiscovery() {
		var data = state.data;

		if ( ! data.exists ) {
			$( '#spe-import-step-1' ).html(
				'<div class="spe-import-empty">' +
				'<p><strong>No Woo Custom Emails data found on this site.</strong></p>' +
				'<p>We look for the Woo Custom Emails custom post type, its product assignments, and its settings option. ' +
				'If you expect to see data here, make sure you are on the same site that previously ran Woo Custom Emails.</p>' +
				'</div>'
			);
			return;
		}

		var c = data.counts;
		var html = '<div class="spe-import-card">' +
			'<p>Found <strong>' + c.messages_found + '</strong> Woo Custom Emails message(s) assigned across <strong>' + c.products_affected + '</strong> product(s).</p>' +
			'<ul class="spe-import-summary-list">' +
			'<li>Processing: ' + c.by_status.processing + '</li>' +
			'<li>On-Hold: ' + c.by_status.onhold + '</li>' +
			'<li>Completed: ' + c.by_status.completed + '</li>' +
			'</ul>' +
			( c.dangling > 0 ? '<p class="spe-import-warning">' + c.dangling + ' assignment(s) reference a missing or unpublished message and will be flagged during import.</p>' : '' ) +
			'<p><button type="button" class="button" id="spe-import-rescan">Scan Again</button> ' +
			'<button type="button" class="button button-primary" id="spe-import-next-1">Next</button></p>' +
			'</div>';

		$( '#spe-import-step-1' ).html( html );

		$( '#spe-import-rescan' ).on( 'click', runScan );
		$( '#spe-import-next-1' ).on( 'click', function () {
			renderReview();
			goToStep( 2 );
		} );
	}

	/* ---------------------------------------------------------------- */
	/* Step 2: Review                                                    */
	/* ---------------------------------------------------------------- */

	function renderReview() {
		var data = state.data;
		var c = data.counts;
		var s = data.settings;

		var proGated = c.by_status.onhold + c.by_status.completed;
		var proBanner = renderProBanner( 'review', proGated, data.pro );

		var totalFailed = ( c.by_classification && c.by_classification.failed ) || 0;

		var html = '<div class="spe-import-card">' +
			proBanner +
			'<table class="widefat striped spe-import-table">' +
			'<thead><tr><th>Status</th><th>New</th><th>Unchanged</th><th>Changed</th><th>Conflicts</th></tr></thead>' +
			'<tbody>' +
			reviewRow( 'Processing', 'processing' ) +
			reviewRow( 'On-Hold', 'onhold' ) +
			reviewRow( 'Completed', 'completed' ) +
			'</tbody></table>' +
			( c.dangling > 0 ? '<p class="spe-import-warning">' + c.dangling + ' assignment(s) reference a missing or unpublished message and will be skipped with an error.</p>' : '' ) +
			( totalFailed > 0 ? '<p class="spe-import-warning">' + totalFailed + ' assignment(s) failed on a previous run and will stay skipped until their source data changes.</p>' : '' ) +
			conflictSummary( data.conflicts ) +
			'<h3>Settings</h3>' +
			'<ul class="spe-import-summary-list">' +
			'<li>Extra display classes: ' + ( s.display_classes ? tooltipBadge( 'Migrated', cfg.i18n.displayClassesTooltip ) : '<em>none set</em>' ) + '</li>' +
			'<li>Show in admin emails: ' + tooltipBadge( 'Not Imported', cfg.i18n.adminEmailTooltip ) + '</li>' +
			'</ul>' +
			'<p><button type="button" class="button" id="spe-import-back-2">Back</button> ' +
			'<button type="button" class="button button-primary" id="spe-import-next-2">Next</button></p>' +
			'</div>';

		$( '#spe-import-step-2' ).html( html );

		$( '#spe-import-back-2' ).on( 'click', function () {
			renderDiscovery();
			goToStep( 1 );
		} );
		$( '#spe-import-next-2' ).on( 'click', function () {
			renderConfigure();
			goToStep( 3 );
		} );
	}

	function reviewRow( label, status ) {
		var breakdown = state.data.counts.by_status_classification[ status ] || {};
		var col = function ( key ) {
			return breakdown[ key ] || 0;
		};

		return '<tr><td>' + label + '</td>' +
			'<td>' + col( 'new' ) + '</td>' +
			'<td>' + col( 'unchanged' ) + '</td>' +
			'<td>' + col( 'changed' ) + '</td>' +
			'<td>' + col( 'conflict' ) + '</td>' +
			'</tr>';
	}

	function conflictSummary( conflicts ) {
		if ( ! conflicts || ! conflicts.total ) {
			return '';
		}

		var rows = conflicts.items.map( function ( row ) {
			return '<li><strong>' + esc( row.product_name ) + '</strong> - ' + statusLabel( row.status ) + ': ' +
				'currently <em>' + esc( row.existing_summary ) + '</em> &rarr; Woo Custom Emails has <em>' + esc( row.incoming_title ) + '</em></li>';
		} ).join( '' );

		var more = conflicts.total > conflicts.items.length
			? '<p class="spe-import-note">+ ' + ( conflicts.total - conflicts.items.length ) + ' more not shown.</p>'
			: '';

		return '<details class="spe-import-conflicts" open>' +
			'<summary>' + conflicts.total + ' conflicting assignment(s) - what\'s already there vs. what Woo Custom Emails would bring in</summary>' +
			'<ul class="spe-import-summary-list">' + rows + '</ul>' +
			more +
			'</details>';
	}

	/* ---------------------------------------------------------------- */
	/* Step 3: Configure                                                 */
	/* ---------------------------------------------------------------- */

	function renderConfigure() {
		var data = state.data;
		var c = data.counts.by_classification;
		var hasConflicts = c.conflict > 0;

		var html = '<div class="spe-import-card">';

		if ( hasConflicts ) {
			html += '<h3>Existing Smart Product Emails data found</h3>' +
				'<p>' + c.conflict + ' product/status assignment(s) already have their own Smart Product Emails configuration ' +
				'(not previously imported). What should we do?</p>' +
				'<label><input type="radio" name="spe-conflict-strategy" value="overwrite" checked> Overwrite existing data (Recommended)</label><br/>' +
				'<label><input type="radio" name="spe-conflict-strategy" value="skip"> Leave as-is / skip</label>';
		}

		html += '<p id="spe-import-recap"></p>' +
			'<p><button type="button" class="button" id="spe-import-back-3">Back</button> ' +
			'<button type="button" class="button button-primary" id="spe-import-next-3">Next</button></p>' +
			'</div>';

		$( '#spe-import-step-3' ).html( html );

		function updateRecap() {
			state.conflictStrategy = $( 'input[name="spe-conflict-strategy"]:checked' ).val() || 'overwrite';
			var pending = c['new'] + c.changed + ( 'overwrite' === state.conflictStrategy ? c.conflict : 0 );
			var skipped = ( 'skip' === state.conflictStrategy ) ? c.conflict : 0;
			var text = 'You are about to create/update ' + pending + ' item(s).';
			if ( skipped > 0 ) {
				text += ' ' + skipped + ' conflicting item(s) will be left as-is.';
			} else if ( hasConflicts ) {
				text += ' ' + c.conflict + ' conflicting item(s) will be overwritten.';
			}
			$( '#spe-import-recap' ).text( text );
		}

		$( 'input[name="spe-conflict-strategy"]' ).on( 'change', updateRecap );
		updateRecap();

		$( '#spe-import-back-3' ).on( 'click', function () {
			renderReview();
			goToStep( 2 );
		} );
		$( '#spe-import-next-3' ).on( 'click', function () {
			renderConfirm();
			goToStep( 4 );
		} );
	}

	/* ---------------------------------------------------------------- */
	/* Step 4: Confirm & Import                                          */
	/* ---------------------------------------------------------------- */

	function renderConfirm() {
		var html = '<div class="spe-import-card">' +
			'<p>Click Start Import to begin. Navigating away is safe - the import can always be resumed - but please don\'t close the tab while it\'s running.</p>' +
			'<p><button type="button" class="button" id="spe-import-back-4">Back</button> ' +
			'<button type="button" class="button button-primary" id="spe-import-start">Start Import</button></p>' +
			'<div id="spe-import-progress-wrap" style="display:none;">' +
			'<div class="spe-import-progress-bar"><div class="spe-import-progress-fill" id="spe-import-progress-fill"></div></div>' +
			'<p id="spe-import-progress-text"></p>' +
			'<div id="spe-import-log" class="spe-import-log"></div>' +
			'<p><button type="button" class="button" id="spe-import-cancel">Cancel Import</button></p>' +
			'</div>' +
			'</div>';

		$( '#spe-import-step-4' ).html( html );

		$( '#spe-import-back-4' ).on( 'click', function () {
			renderConfigure();
			goToStep( 3 );
		} );
		$( '#spe-import-start' ).on( 'click', startImport );
		$( '#spe-import-cancel' ).on( 'click', function () {
			if ( window.confirm( cfg.i18n.confirmCancel ) ) {
				state.cancelRequested = true;
			}
		} );
	}

	function renderInterruptedJob( job ) {
		var pct = job.total_count ? Math.round( ( job.processed_count / job.total_count ) * 100 ) : 0;
		var html = '<div class="spe-import-card">' +
			'<p>An import was previously in progress (' + job.processed_count + ' / ' + job.total_count + ' done, ' + pct + '%).</p>' +
			'<p><button type="button" class="button button-primary" id="spe-import-resume">Resume Import</button> ' +
			'<button type="button" class="button" id="spe-import-fresh">Start Fresh Scan</button></p>' +
			'<div id="spe-import-progress-wrap" style="display:none;">' +
			'<div class="spe-import-progress-bar"><div class="spe-import-progress-fill" id="spe-import-progress-fill"></div></div>' +
			'<p id="spe-import-progress-text"></p>' +
			'<div id="spe-import-log" class="spe-import-log"></div>' +
			'<p><button type="button" class="button" id="spe-import-cancel">Cancel Import</button></p>' +
			'</div>' +
			'</div>';

		$( '#spe-import-step-4' ).html( html );

		$( '#spe-import-resume' ).on( 'click', function () {
			state.conflictStrategy = job.conflict_strategy;
			startImport();
		} );
		$( '#spe-import-fresh' ).on( 'click', function () {
			ajax( 'smartproductemails_import_cancel' ).always( runScan );
		} );
		$( '#spe-import-cancel' ).on( 'click', function () {
			if ( window.confirm( cfg.i18n.confirmCancel ) ) {
				state.cancelRequested = true;
			}
		} );
	}

	function startImport() {
		state.cancelRequested = false;
		state.running = true;
		$( '#spe-import-start, #spe-import-resume, #spe-import-fresh, #spe-import-back-4' ).prop( 'disabled', true );
		$( '#spe-import-progress-wrap' ).show();
		runBatch();
	}

	function runBatch() {
		if ( state.cancelRequested ) {
			ajax( 'smartproductemails_import_cancel' ).always( function () {
				state.running = false;
				$( '#spe-import-progress-text' ).append( '<br/>Import cancelled. You can resume it later from this page.' );
			} );
			return;
		}

		ajax( 'smartproductemails_import_run_batch', { conflict_strategy: state.conflictStrategy } ).done( function ( response ) {
			if ( ! response || ! response.success ) {
				$( '#spe-import-progress-text' ).text( cfg.i18n.batchFailed );
				return;
			}

			var job = response.data.job;
			var pct = job.total_count ? Math.round( ( job.processed_count / job.total_count ) * 100 ) : 100;

			$( '#spe-import-progress-fill' ).css( 'width', pct + '%' );
			$( '#spe-import-progress-text' ).text( job.processed_count + ' / ' + job.total_count + ' processed (' + pct + '%)' );

			response.data.log.forEach( function ( row ) {
				var line = esc( row.product_name ) + ' - ' + statusLabel( row.status ) + ': ' + row.outcome;
				if ( row.message ) {
					line += ' - ' + esc( row.message );
				}
				$( '#spe-import-log' ).prepend( '<div class="spe-import-log-row spe-import-log-' + esc( row.outcome ) + '">' + line + '</div>' );
			} );

			if ( response.data.done ) {
				state.running = false;
				renderReport( job );
				goToStep( 5 );
			} else {
				runBatch();
			}
		} ).fail( function () {
			$( '#spe-import-progress-text' ).text( cfg.i18n.batchFailed );
		} );
	}

	/* ---------------------------------------------------------------- */
	/* Step 5: Report                                                    */
	/* ---------------------------------------------------------------- */

	function renderReport( job ) {
		var data = state.data || { pro: {}, source_active: false, settings: {} };
		var totals = job.totals || {};

		function badge( status, label ) {
			var active = data.status_active && data.status_active[ status ];
			var cls = active ? 'spe-import-badge-active' : 'spe-import-badge-inactive';
			var text = active ? 'Active' : ( data.pro && data.pro.installed ? 'Imported - license required' : 'Imported - requires PRO' );
			return '<li>' + label + ': <span class="' + cls + '">' + text + '</span></li>';
		}

		var proGated = ( data.counts && data.counts.by_status )
			? data.counts.by_status.onhold + data.counts.by_status.completed
			: 0;
		var proBanner = renderProBanner( 'report', proGated, data.pro );

		var html = '<div class="spe-import-card">' +
			proBanner +
			'<h3>Import complete</h3>' +
			'<ul class="spe-import-summary-list">' +
			'<li>Created: ' + ( totals.created || 0 ) + '</li>' +
			'<li>Updated: ' + ( totals.updated || 0 ) + '</li>' +
			'<li>Skipped: ' + ( totals.skipped || 0 ) + '</li>' +
			'<li>Conflicts encountered: ' + ( totals.conflict || 0 ) + '</li>' +
			'<li>Errors: ' + ( totals.errors || 0 ) + '</li>' +
			'</ul>' +
			'<h3>Status</h3>' +
			'<ul class="spe-import-summary-list">' +
			badge( 'processing', 'Processing' ) +
			badge( 'onhold', 'On-Hold' ) +
			badge( 'completed', 'Completed' ) +
			'</ul>' +
			'<h3>Settings</h3>' +
			'<ul class="spe-import-summary-list">' +
			'<li>Extra display classes: ' + ( data.settings && data.settings.display_classes ? tooltipBadge( 'Migrated', cfg.i18n.displayClassesTooltip ) : '<em>none set</em>' ) + '</li>' +
			'<li>Show in admin emails: ' + tooltipBadge( 'Not Imported', cfg.i18n.adminEmailTooltip ) + '</li>' +
			'</ul>' +
			( data.source_active
				? '<p><button type="button" class="button" id="spe-import-deactivate">Deactivate Woo Custom Emails</button></p>'
				: '' ) +
			'<p><a href="#" id="spe-import-run-again">Run Again</a></p>' +
			'</div>';

		$( '#spe-import-step-5' ).html( html );

		$( '#spe-import-deactivate' ).on( 'click', function () {
			if ( ! window.confirm( cfg.i18n.confirmDeactivate ) ) {
				return;
			}
			ajax( 'smartproductemails_import_deactivate_source' ).done( function () {
				$( '#spe-import-deactivate' ).prop( 'disabled', true ).text( 'Deactivated' );
			} );
		} );

		$( '#spe-import-run-again' ).on( 'click', function ( e ) {
			e.preventDefault();
			runScan();
		} );
	}

	jQuery( function () {
		runScan();
	} );
} )( jQuery );
