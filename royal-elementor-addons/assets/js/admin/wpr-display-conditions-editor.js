(function ($) {
	'use strict';

	// Section ID => control IDs that indicate "configured"
	var sectionControls = {
		wpr_dc_visitor_roles: ['wpr_dc_visitor_type', 'wpr_dc_specific_users', 'wpr_dc_capability'],
		wpr_dc_user_profile: ['wpr_dc_user_meta_key'],
		wpr_dc_page_content: ['wpr_dc_post_types', 'wpr_dc_specific_posts', 'wpr_dc_taxonomy', 'wpr_dc_meta_key', 'wpr_dc_post_hierarchy'],
		wpr_dc_archive: ['wpr_dc_archive_types', 'wpr_dc_archive_term_property'],
		wpr_dc_date_time: ['wpr_dc_date_from', 'wpr_dc_date_to', 'wpr_dc_weekdays', 'wpr_dc_time_from', 'wpr_dc_time_to', 'wpr_dc_recurring_from', 'wpr_dc_recurring_to'],
		wpr_dc_device_browser: ['wpr_dc_devices', 'wpr_dc_browsers'],
		wpr_dc_visitor_location: ['wpr_dc_ip_addresses', 'wpr_dc_referrer_domains', 'wpr_dc_geo_countries', 'wpr_dc_geo_cities'],
		wpr_dc_url_parameters: ['wpr_dc_param_name'],
		wpr_dc_woocommerce: ['wpr_dc_woo_cart', 'wpr_dc_woo_cart_products', 'wpr_dc_woo_cart_categories', 'wpr_dc_woo_product_type'],
		wpr_dc_language: ['wpr_dc_languages'],
		wpr_dc_custom_fields: ['wpr_dc_acf_field'],
		wpr_dc_dynamic_tags: ['wpr_dc_dynamic_tag'],
		wpr_dc_interaction: ['wpr_dc_interaction_type'],
		wpr_dc_random_limits: ['wpr_dc_random_enabled', 'wpr_dc_limit_enabled'],
		wpr_dc_fallback: ['wpr_dc_fallback_enabled']
	};

	function hasValue(val) {
		if (val === null || val === undefined || val === '' || val === 'no') return false;
		if (Array.isArray(val) && val.length === 0) return false;
		return true;
	}

	function checkState() {
		var currentElement = elementor.getCurrentElement();
		if (!currentElement) return;

		var settings = currentElement.model.get('settings');
		if (!settings) return;

		$('#elementor-controls .elementor-control-type-section').each(function () {
			var $section = $(this);
			$section.removeClass('wpr-dc-status-active');

			$.each(sectionControls, function (sectionId, controls) {
				if ($section.hasClass('elementor-control-' + sectionId)) {
					for (var i = 0; i < controls.length; i++) {
						var val = settings.get(controls[i]);
						if (hasValue(val)) {
							$section.addClass('wpr-dc-status-active');
							return false; // break $.each
						}
					}
				}
			});
		});
	}

	$(window).on('elementor:init', function () {
		// Check on switcher clicks and section header clicks
		$(document).on(
			'click',
			'.elementor-control-type-switcher .elementor-switch, .elementor-panel-heading, .elementor-control-type-select2 .select2',
			function () {
				setTimeout(checkState, 100);
			}
		);

		// Check on input changes (text fields, selects)
		$(document).on(
			'change input',
			'#elementor-controls input, #elementor-controls select, #elementor-controls textarea',
			function () {
				setTimeout(checkState, 100);
			}
		);

		// Check when section is activated in panel
		elementor.channels.editor.on('section:activated', function () {
			setTimeout(checkState, 100);
		});

		// Check when element panel opens
		elementor.hooks.addAction('panel/open_editor/widget', function () {
			setTimeout(checkState, 200);
		});
		elementor.hooks.addAction('panel/open_editor/section', function () {
			setTimeout(checkState, 200);
		});
		elementor.hooks.addAction('panel/open_editor/column', function () {
			setTimeout(checkState, 200);
		});
		elementor.hooks.addAction('panel/open_editor/container', function () {
			setTimeout(checkState, 200);
		});
	});

})(jQuery);
