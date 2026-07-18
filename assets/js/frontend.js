/**
 * Frontend JavaScript driver for antigravity-purchase-notifications
 */
(function ($) {
	'use strict';

	// Exit if core configurations are missing
	if (typeof wcpn_config === 'undefined') {
		return;
	}

	var config = wcpn_config;
	var queue = [];
	var currentIndex = 0;
	var rotationTimer = null;
	var currentCardElement = null;
	var isPaused = false;
	var isTabActive = true;

	// Helper to check device compatibility
	function isDeviceCompatible() {
		var width = $(window).width();
		if (width <= 480) {
			return config.enable_mobile;
		} else if (width > 480 && width <= 768) {
			return config.enable_tablet;
		} else {
			return config.enable_desktop;
		}
	}

	// Shuffle helper for notifications
	function shuffleArray(array) {
		var currentIndex = array.length, temporaryValue, randomIndex;
		while (0 !== currentIndex) {
			randomIndex = Math.floor(Math.random() * currentIndex);
			currentIndex -= 1;
			temporaryValue = array[currentIndex];
			array[currentIndex] = array[randomIndex];
			array[randomIndex] = temporaryValue;
		}
		return array;
	}

	// Initialize
	function init() {
		// Respect session-wide dismissal
		if (sessionStorage.getItem('wcpn_dismissed_session') === '1') {
			return;
		}

		if (!isDeviceCompatible()) {
			return;
		}

		// Listen to visibility change to pause when tab is inactive
		document.addEventListener('visibilitychange', handleVisibilityChange);

		var cacheKey = 'wcpn_notif_' + config.product_id;
		var cachedData = sessionStorage.getItem(cacheKey);

		if (cachedData) {
			try {
				queue = JSON.parse(cachedData);
				if (queue.length > 0) {
					startNotificationCycle();
				}
			} catch (e) {
				fetchNotifications();
			}
		} else {
			fetchNotifications();
		}
	}

	// Fetch orders list via AJAX
	function fetchNotifications() {
		$.ajax({
			url: config.ajax_url,
			type: 'GET',
			dataType: 'json',
			data: {
				action: 'wcpn_get_notifications',
				nonce: config.nonce,
				product_id: config.product_id
			},
			success: function (response) {
				if (response.success && response.data && response.data.length > 0) {
					queue = response.data;

					// Randomize/shuffle notifications to prevent duplicate patterns
					queue = shuffleArray(queue);

					// Save to browser sessionStorage
					try {
						sessionStorage.setItem('wcpn_notif_' + config.product_id, JSON.stringify(queue));
					} catch (e) { }

					startNotificationCycle();
				}
			}
		});
	}

	// Handles tab visibility adjustments
	function handleVisibilityChange() {
		if (document.hidden) {
			isTabActive = false;
			pauseCycle();
		} else {
			isTabActive = true;
			resumeCycle();
		}
	}

	// Starts the timer loops
	function startNotificationCycle() {
		// Show first card after the initial display delay
		setTimeout(function () {
			if (isTabActive) {
				showNextNotification();
			}
		}, config.display_delay);
	}

	// Display card
	function showNextNotification() {
		if (queue.length === 0 || sessionStorage.getItem('wcpn_dismissed_session') === '1') {
			return;
		}

		// Loop index check
		if (currentIndex >= queue.length) {
			currentIndex = 0; // Loop back and repeat
		}

		var notification = queue[currentIndex];
		currentIndex++;

		// Create DOM elements
		var cardHtml = '<a href="' + notification.permalink + '" class="wcpn-card wcpn-dark-theme wcpn-anim-' + config.animation_type + '" id="wcpn-active-card">';

		// 1. Close/Dismiss button
		if (config.dismissible) {
			cardHtml += '<button type="button" class="wcpn-close" aria-label="Dismiss notification">&times;</button>';
		}

		// 2. Product Thumbnail
		if (notification.image) {
			cardHtml += '<div class="wcpn-image-wrapper"><img src="' + notification.image + '" class="wcpn-image" alt="Product thumbnail" loading="lazy"></div>';
		}

		// 3. Body
		cardHtml += '<div class="wcpn-body"><p class="wcpn-text">' + notification.text + '</p>';

		// 4. Verified Badge
		if (notification.verified) {
			cardHtml += '<div class="wcpn-verified">';
			cardHtml += '<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
			cardHtml += '<span>Verified Purchase</span>';
			cardHtml += '</div>';
		}

		cardHtml += '</div></a>';

		var $container = $('#wcpn-notification-container');
		if ($container.length === 0) {
			return;
		}

		// Remove any existing card first
		removeActiveCard(function () {
			currentCardElement = $(cardHtml);
			$container.append(currentCardElement);

			// Register close button click action
			currentCardElement.find('.wcpn-close').on('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				dismissCycle();
			});

			// Register hover pause listeners
			if (config.pause_on_hover) {
				currentCardElement.on('mouseenter', pauseCycle);
				currentCardElement.on('mouseleave', resumeCycle);
			}

			// Small timeout to trigger entry animation transition
			setTimeout(function () {
				if (currentCardElement) {
					currentCardElement.addClass('wcpn-enter');
				}
			}, 20);

			// Schedule next card transition
			scheduleRotation();
		});
	}

	// Schedule the next card rotation
	function scheduleRotation() {
		clearRotationTimer();
		if (isPaused || !isTabActive) {
			return;
		}

		rotationTimer = setTimeout(function () {
			removeActiveCard(function () {
				showNextNotification();
			});
		}, config.rotation_interval);
	}

	// Remove active card with transition delay
	function removeActiveCard(callback) {
		var $activeCard = $('#wcpn-active-card');
		if ($activeCard.length === 0) {
			if (callback) callback();
			return;
		}

		$activeCard.removeClass('wcpn-enter').addClass('wcpn-exit');

		// Wait for animation duration before removing from DOM
		setTimeout(function () {
			$activeCard.remove();
			currentCardElement = null;
			if (callback) callback();
		}, config.animation_duration);
	}

	// Pause loop timer
	function pauseCycle() {
		isPaused = true;
		clearRotationTimer();
	}

	// Resume loop timer
	function resumeCycle() {
		isPaused = false;
		if (!currentCardElement) {
			showNextNotification();
		} else {
			scheduleRotation();
		}
	}

	// Dismiss notifications for session
	function dismissCycle() {
		sessionStorage.setItem('wcpn_dismissed_session', '1');
		clearRotationTimer();
		removeActiveCard();
	}

	function clearRotationTimer() {
		if (rotationTimer) {
			clearTimeout(rotationTimer);
			rotationTimer = null;
		}
	}

	// Load when DOM is fully ready
	$(document).ready(init);

})(jQuery);
