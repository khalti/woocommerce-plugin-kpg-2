jQuery(function ($) {
	'use strict';

	/**
	 * Object to handle khalti admin functions.
	 */
	var wc_khalti_admin = {
		isTestMode: function () {
			return $('#woocommerce_khalti_testmode').is(':checked');
		},

		/**
		 * Initialize.
		 */
		init: function () {
			$(document.body).on('change', '#woocommerce_khalti_testmode', function () {
				var test_merchant_code = $('#woocommerce_khalti_sandbox_merchant_secret').parents('tr').eq(0),
					live_merchant_code = $('#woocommerce_khalti_merchant_secret').parents('tr').eq(0)
				if ($(this).is(':checked')) {
					live_merchant_code.hide();
					test_merchant_code.show();
				} else {
					test_merchant_code.hide();
					live_merchant_code.show();
				}
			});

			$('#woocommerce_khalti_testmode').change();
		}
	};

	wc_khalti_admin.init();
});
