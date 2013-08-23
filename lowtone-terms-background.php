<?php
/*
 * Plugin Name: Term Backgrounds
 * Plugin URI: http://wordpress.lowtone.nl/plugins/terms-background/
 * Description: Add custom background images to terms.
 * Version: 1.0
 * Author: Lowtone <info@lowtone.nl>
 * Author URI: http://lowtone.nl
 * License: http://wordpress.lowtone.nl/license
 */
/**
 * @author Paul van der Meijs <code@lowtone.nl>
 * @copyright Copyright (c) 2013, Paul van der Meijs
 * @license http://wordpress.lowtone.nl/license/
 * @version 1.0
 * @package wordpress\plugins\lowtone\terms\background
 */

namespace lowtone\terms\background {

	use lowtone\content\packages\Package,
		lowtone\wp\terms\Term,
		lowtone\wp\terms\meta\Meta;

	// Includes
	
	if (!include_once WP_PLUGIN_DIR . "/lowtone-content/lowtone-content.php") 
		return trigger_error("Lowtone Content plugin is required", E_USER_ERROR) && false;

	$__i = Package::init(array(
			Package::INIT_PACKAGES => array("lowtone", "lowtone\\wp"),
			Package::INIT_MERGED_PATH => __NAMESPACE__,
			Package::INIT_ACTIVATE => function() {

				// Install term meta
				
				Meta::install();

			},
			Package::INIT_SUCCESS => function() {

				add_action("load-edit-tags.php", function() {
					wp_enqueue_style("lowtone_terms_background_admin", plugins_url("/assets/styles/admin.css", __FILE__));

					$addInput = function($term) {
						$current = image($term);

						$buttonText = $current ? __("Change image", "lowtone_terms_background") : __("Select image", "lowtone_terms_background");

						echo '<tr class="lowtone terms background">' . 
							'<th scope="row" valign="top"><label for="description">' . 
								__("Background image") . 
								'</label></th>' . 
							'<td>' .
							sprintf('<a href="#" class="button insert-media add_media" data-editor="html_description" title="Add Media"><span class="wp-media-buttons-icon"></span> %s</a>', esc_html($buttonText)) . 
							call_user_func(function() use ($current) {
								if (!$current)
									return;

								return '<dl>' . 
									'<dt>' . __("Current image", "lowtone_terms_background") . '</dt>' . 
									'<dd>' . sprintf('<a title="%s" class="current">', __("Click to remove", "lowtone_terms_background")) . sprintf('<img src="%s" />', $current) . '</a></dd>' . 
									'</dl>';
							}) . 
							'<span class="description">' . __("This image is used for the background when posts associated to this term are displayed.", "lowtone_terms_background") . '</span>' . 
							'</td>' .
							'</tr>';
					};

					foreach (array("edit_category_form_fields", "edit_link_category_form_fields", "edit_tag_form_fields") as $action) 
						add_action($action, $addInput);

				}, 100);

				add_action("wp_head", function() {
					if (!is_tax())
						return;

					$term = get_queried_object();

					$image = false;

					try {
						$image = image($term);
					} catch (\ErrorException $e) {}

					if (!$image)
						return;

					echo '<style>' . 
						'body {' . 
						'background-image:url("' . esc_html(apply_filters("lowtone_terms_background_image", $image, $term)) . '");' . 
						'background-size:' . esc_html(apply_filters("lowtone_terms_background_size", "cover", $term)) . ';' . 
						'background-position:' . esc_html(apply_filters("lowtone_terms_background_position", "top center", $term)) . ';' . 
						'background-repeat:' . esc_html(apply_filters("lowtone_terms_background_repeat", "no-repeat", $term)) . ';' . 
						'}' . 
						'</style>';
				}, 9999);

			}
		));

	// Functions

	function image($term) {
		if (is_array($term))
			$term = (object) $term;

		if (is_object($term)) {
			if (!isset($term->{Term::PROPERTY_TERM_ID}))
				throw new \ErrorException("No term ID set on given object.");

			$term = $term->{Term::PROPERTY_TERM_ID};
		}

		$meta = Meta::findOne(array(
				Meta::PROPERTY_TERM_ID => $term,
				Meta::PROPERTY_META_KEY => "_lowtone_term_background",
			));

		return $meta && ($url = wp_get_attachment_url($meta->{Meta::PROPERTY_META_VALUE})) 
			? $url 
			: NULL;
	}

}