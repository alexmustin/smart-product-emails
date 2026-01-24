/**
 * Smart Product Emails - Dynamic Tags Modal JavaScript
 *
 * Handles modal open/close, search filtering, and tag insertion into editor.
 *
 * @package SmartProductEmails
 */

(function($) {
	'use strict';

	var SPE_DynamicTags = {

		/**
		 * Initialize the module
		 */
		init: function() {
			this.cacheElements();
			this.bindEvents();
		},

		/**
		 * Cache DOM elements
		 */
		cacheElements: function() {
			this.$button = $('#spe-dynamic-tags-btn');
			this.$modal = $('#spe-dynamic-tags-modal');
			this.$overlay = this.$modal.find('.spe-modal-overlay');
			this.$closeBtn = this.$modal.find('.spe-modal-close');
			this.$searchInput = $('#spe-tag-search');
			this.$categories = this.$modal.find('.spe-tag-category');
			this.$tagItems = this.$modal.find('.spe-tag-item');
			this.$noResults = this.$modal.find('.spe-no-results');
		},

		/**
		 * Bind event handlers
		 */
		bindEvents: function() {
			var self = this;

			// Open modal
			this.$button.on('click', function(e) {
				e.preventDefault();
				self.openModal();
			});

			// Close modal
			this.$closeBtn.on('click', function() {
				self.closeModal();
			});

			this.$overlay.on('click', function() {
				self.closeModal();
			});

			// Close on escape key
			$(document).on('keydown', function(e) {
				if (e.key === 'Escape' && self.$modal.is(':visible')) {
					self.closeModal();
				}
			});

			// Search functionality
			this.$searchInput.on('input', function() {
				self.filterTags($(this).val());
			});

			// Tag click - insert into editor
			this.$tagItems.on('click', function() {
				var tag = $(this).data('tag');
				self.insertTag(tag);
				self.showInsertedFeedback($(this));
			});

			// Keyboard navigation
			this.$tagItems.on('keydown', function(e) {
				if (e.key === 'Enter' || e.key === ' ') {
					e.preventDefault();
					var tag = $(this).data('tag');
					self.insertTag(tag);
					self.showInsertedFeedback($(this));
				}
			});
		},

		/**
		 * Open the modal
		 */
		openModal: function() {
			this.$modal.fadeIn(200);
			this.$searchInput.val('').focus();
			this.filterTags('');
			$('body').addClass('spe-modal-open');
		},

		/**
		 * Close the modal
		 */
		closeModal: function() {
			this.$modal.fadeOut(200);
			$('body').removeClass('spe-modal-open');
		},

		/**
		 * Filter tags based on search query
		 *
		 * @param {string} query The search query
		 */
		filterTags: function(query) {
			var self = this;
			var lowerQuery = query.toLowerCase().trim();
			var hasResults = false;

			this.$tagItems.each(function() {
				var $item = $(this);
				var tag = $item.data('tag').toLowerCase();
				var description = $item.find('.spe-tag-description').text().toLowerCase();
				var example = $item.find('.spe-tag-example-value').text().toLowerCase();

				var matches = !lowerQuery ||
					tag.indexOf(lowerQuery) !== -1 ||
					description.indexOf(lowerQuery) !== -1 ||
					example.indexOf(lowerQuery) !== -1;

				$item.toggleClass('spe-hidden', !matches);

				if (matches) {
					hasResults = true;
				}
			});

			// Show/hide categories based on whether they have visible items
			this.$categories.each(function() {
				var $category = $(this);
				var hasVisibleItems = $category.find('.spe-tag-item:not(.spe-hidden)').length > 0;
				$category.toggleClass('spe-hidden', !hasVisibleItems);
			});

			// Show/hide no results message
			this.$noResults.toggle(!hasResults);
		},

		/**
		 * Insert tag into the editor at cursor position
		 *
		 * @param {string} tag The tag to insert
		 */
		insertTag: function(tag) {
			// Try to insert into TinyMCE (Visual editor)
			if (typeof tinymce !== 'undefined' && tinymce.activeEditor && !tinymce.activeEditor.isHidden()) {
				tinymce.activeEditor.execCommand('mceInsertContent', false, tag);
				return;
			}

			// Fallback to inserting into the plain text editor
			var $textarea = $('#content');
			if ($textarea.length) {
				this.insertAtCursor($textarea[0], tag);
			}
		},

		/**
		 * Insert text at cursor position in a textarea
		 *
		 * @param {HTMLElement} textarea The textarea element
		 * @param {string} text The text to insert
		 */
		insertAtCursor: function(textarea, text) {
			var startPos = textarea.selectionStart;
			var endPos = textarea.selectionEnd;
			var scrollTop = textarea.scrollTop;

			textarea.value = textarea.value.substring(0, startPos) +
				text +
				textarea.value.substring(endPos, textarea.value.length);

			// Move cursor to end of inserted text
			textarea.focus();
			textarea.selectionStart = startPos + text.length;
			textarea.selectionEnd = startPos + text.length;
			textarea.scrollTop = scrollTop;

			// Trigger change event for any listeners
			$(textarea).trigger('change');
		},

		/**
		 * Show visual feedback when a tag is inserted
		 *
		 * @param {jQuery} $item The clicked tag item
		 */
		showInsertedFeedback: function($item) {
			$item.addClass('spe-tag-inserted');
			setTimeout(function() {
				$item.removeClass('spe-tag-inserted');
			}, 800);
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		SPE_DynamicTags.init();
	});

})(jQuery);
