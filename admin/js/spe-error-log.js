jQuery(document).ready(function($) {

	// Clear logs button
	$('#spe-clear-logs').on('click', function(e) {
		e.preventDefault();

		if (!confirm('Are you sure you want to clear all error logs? This cannot be undone.')) {
			return;
		}

		$.ajax({
			url: smartproductemailsErrorLog.ajaxurl,
			type: 'POST',
			data: {
				action: 'smartproductemails_clear_logs',
				nonce: smartproductemailsErrorLog.nonce
			},
			success: function(response) {
				if (response.success) {
					alert(response.data.message);
					location.reload();
				} else {
					alert(response.data.message);
				}
			},
			error: function() {
				alert('Error clearing logs. Please try again.');
			}
		});
	});

	// Export logs button
	$('#spe-export-logs').on('click', function(e) {
		e.preventDefault();

		// Create form and submit to trigger download
		var form = $('<form>', {
			'method': 'POST',
			'action': smartproductemailsErrorLog.ajaxurl
		});

		form.append($('<input>', {
			'type': 'hidden',
			'name': 'action',
			'value': 'smartproductemails_export_logs'
		}));

		form.append($('<input>', {
			'type': 'hidden',
			'name': 'nonce',
			'value': smartproductemailsErrorLog.nonce
		}));

		$('body').append(form);
		form.submit();
		form.remove();
	});

	// View details modal
	$('.spe-view-details').on('click', function(e) {
		e.preventDefault();

		var $row = $(this).closest('.spe-log-row');
		var logId = $(this).data('log-id');

		// Build details HTML from row data
		var details = '<h2>Log Entry #' + logId + '</h2>';
		details += '<table class="spe-log-details-table">';
		details += '<tr><th>Level:</th><td>' + $row.find('.spe-badge').text() + '</td></tr>';
		details += '<tr><th>Type:</th><td>' + $row.find('.spe-log-type').text() + '</td></tr>';
		details += '<tr><th>Message:</th><td>' + $row.find('.spe-message-full').text() + '</td></tr>';
		details += '<tr><th>Location:</th><td>' + $row.find('.spe-log-location').html() + '</td></tr>';
		details += '<tr><th>Date:</th><td>' + $row.find('.spe-log-date').text() + '</td></tr>';

		// Add stack trace if exists
		if ($row.find('.spe-stack-trace').length) {
			details += '<tr><th>Stack Trace:</th><td>' + $row.find('.spe-stack-trace').html() + '</td></tr>';
		}

		// Add context if exists
		if ($row.find('.spe-context').length) {
			details += '<tr><th>Context:</th><td>' + $row.find('.spe-context').html() + '</td></tr>';
		}

		details += '</table>';

		$('#spe-log-details-content').html(details);
		$('#spe-log-details-modal').fadeIn();
	});

	// Close modal
	$('.spe-modal-close').on('click', function() {
		$('#spe-log-details-modal').fadeOut();
	});

	// Close modal on outside click
	$(window).on('click', function(e) {
		if ($(e.target).is('#spe-log-details-modal')) {
			$('#spe-log-details-modal').fadeOut();
		}
	});

});
