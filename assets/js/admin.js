/**
 * Admin Javascript driver for WooCommerce Purchase Notifications for WooCommerce settings panel.
 */
jQuery(document).ready(function ($) {

	// 1. Sidebar Tab Switching Logic
	$('.wcpn-tab-item').on('click', function () {
		var targetTab = $(this).data('tab');

		// Remove active classes
		$('.wcpn-tab-item').removeClass('active');
		$('.wcpn-tab-content').removeClass('active');

		// Set active tab
		$(this).addClass('active');
		$('#tab-' + targetTab).addClass('active');
	});

	// 2. Real-time Live Preview Engine
	var $previewCard = $('#wcpn-card-preview-element');

	function updatePreviewStyles() {
		// Appearance field values
		var bgColor = $('input[name="wcpn_settings[appearance][background_color]"]').val();
		var textColor = $('input[name="wcpn_settings[appearance][text_color]"]').val();
		var accentColor = $('input[name="wcpn_settings[appearance][accent_color]"]').val();
		var borderRadius = $('input[name="wcpn_settings[appearance][border_radius]"]').val();
		var borderStyle = $('input[name="wcpn_settings[appearance][border]"]').val();
		var shadowStyle = $('input[name="wcpn_settings[appearance][shadow]"]').val();
		var paddingStyle = $('input[name="wcpn_settings[appearance][padding]"]').val();
		var fontFamily = $('input[name="wcpn_settings[appearance][font_family]"]').val();
		var fontSize = $('input[name="wcpn_settings[appearance][font_size]"]').val();
		var cardWidth = $('input[name="wcpn_settings[appearance][notification_width]"]').val();
		var imageSize = $('input[name="wcpn_settings[appearance][image_size]"]').val();

		// Apply CSS properties to the preview element
		$previewCard.css({
			'background-color': bgColor,
			'color': textColor,
			'border-radius': borderRadius,
			'border': borderStyle,
			'box-shadow': shadowStyle,
			'padding': paddingStyle,
			'font-family': fontFamily,
			'font-size': fontSize,
			'width': '100%',
			'max-width': cardWidth
		});

		// Apply accent color to highlights
		$previewCard.find('.wcpn-preview-name-span, .wcpn-preview-loc-span, .wcpn-preview-qty-span, .wcpn-preview-prod-span, .wcpn-preview-verified-badge').css({
			'color': accentColor
		});

		$previewCard.find('.wcpn-verified-svg').css({
			'stroke': accentColor
		});

		$previewCard.find('.wcpn-preview-image').css({
			'width': imageSize,
			'height': imageSize
		});

		// Show or Hide Product Image Thumbnail
		var showImage = $('input[name="wcpn_settings[display][show_product_image]"]').is(':checked');
		if (showImage) {
			$previewCard.find('.wcpn-preview-image-wrapper').show();
		} else {
			$previewCard.find('.wcpn-preview-image-wrapper').hide();
		}

		// Show or Hide Verified Badge
		var showVerified = $('input[name="wcpn_settings[display][show_verified_badge]"]').is(':checked');
		if (showVerified) {
			$('#wcpn-preview-verified-element').css('display', 'flex');
		} else {
			$('#wcpn-preview-verified-element').hide();
		}

		// Rebuild text template
		updatePreviewText();
	}

	function updatePreviewText() {
		var templateText = $('textarea[name="wcpn_settings[display][custom_notification_template]"]').val();
		var namePrivacy = $('#wcpn-customer-name-mode').val();
		var anonMode = $('input[name="wcpn_settings[privacy][anonymous_mode]"]').is(':checked');
		var hideNames = $('input[name="wcpn_settings[privacy][hide_customer_names]"]').is(':checked');
		var hideLocs = $('input[name="wcpn_settings[privacy][hide_locations]"]').is(':checked');
		var hideQty = $('input[name="wcpn_settings[privacy][hide_quantity]"]').is(':checked');
		var hideTime = $('input[name="wcpn_settings[privacy][hide_purchase_time]"]').is(':checked');
		var locationSource = $('select[name="wcpn_settings[notification][customer_location_source]"]').val();
		var fallbackText = $('input[name="wcpn_settings[notification][customer_location_fallback]"]').val();

		// Mock details
		var customerName = "John D.";
		if (namePrivacy === 'Full First Name') {
			customerName = "John";
		} else if (namePrivacy === 'Initial Only') {
			customerName = "J.";
		} else if (namePrivacy === 'Anonymous' || anonMode) {
			customerName = "Someone";
		} else if (namePrivacy === 'Hidden' || hideNames) {
			customerName = "";
		}

		var customerLocation = "Chicago";
		if (hideLocs) {
			customerLocation = "";
		}

		var quantity = hideQty ? "" : "1x";
		var timeAgo = hideTime ? "" : "5 minutes ago";
		var productName = "Premium Leather Wallet";

		var replacements = {
			'{customer_name}': customerName ? '<span class="wcpn-preview-name-span" style="font-weight: 600;">' + customerName + '</span>' : '',
			'{customer_location}': customerLocation ? '<span class="wcpn-preview-loc-span">' + customerLocation + '</span>' : '',
			'{quantity}': quantity ? '<span class="wcpn-preview-qty-span">' + quantity + '</span>' : '',
			'{product_name}': '<span class="wcpn-preview-prod-span" style="font-weight: 600;">' + productName + '</span>',
			'{time_ago}': timeAgo ? '<span class="wcpn-preview-time-span">' + timeAgo + '</span>' : ''
		};

		var text = templateText;
		$.each(replacements, function (placeholder, val) {
			text = text.replace(new RegExp(placeholder, 'g'), val);
		});

		// Clean up spaces & hanging prepositions
		text = text.replace(/\s+/g, ' ');
		text = text.replace(/ from \s*\./g, '.');
		text = text.replace(/ from \s*\,/g, ',');
		text = text.replace(/ from \s*<span class="wcpn-preview-time-span">/g, ' <span class="wcpn-preview-time-span">');
		text = text.trim();

		$previewCard.find('.wcpn-preview-text').html(text);

		// Apply dynamic colors to the new elements
		var accentColor = $('input[name="wcpn_settings[appearance][accent_color]"]').val();
		$previewCard.find('.wcpn-preview-name-span, .wcpn-preview-loc-span, .wcpn-preview-qty-span, .wcpn-preview-prod-span').css({
			'color': accentColor
		});
	}

	// Hook color pickers changes
	$('.wcpn-color-picker').wpColorPicker({
		change: function (event, ui) {
			// Small timeout because color value takes a millisecond to update in input val
			setTimeout(function () {
				updatePreviewStyles();
			}, 10);
		},
		clear: function () {
			setTimeout(function () {
				updatePreviewStyles();
			}, 10);
		}
	});

	// Hook general inputs
	$('.wcpn-settings-form input, .wcpn-settings-form select, .wcpn-settings-form textarea').on('input change', function () {
		updatePreviewStyles();
	});

	// Trigger preview tests animations
	$('#wcpn-btn-animate-preview').on('click', function () {
		var animation = $('#wcpn-setting-animation-type').val().toLowerCase().replace(' ', '-');

		// Map some animations to classes or css triggers
		$previewCard.removeClass('wcpn-anim-bounce');

		if (animation === 'bounce') {
			// Trigger keyframe bounce
			$previewCard.addClass('wcpn-anim-bounce');
		} else {
			// Trigger a quick slide/fade visual toggle
			$previewCard.css({ 'opacity': 0, 'transform': 'scale(0.95)' });
			setTimeout(function () {
				$previewCard.css({ 'opacity': 1, 'transform': 'scale(1)' });
			}, 150);
		}
	});

	// Boot current preview settings on initial load
	updatePreviewStyles();
});
