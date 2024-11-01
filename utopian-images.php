<?php
/**
 * Plugin Name: Utopian Images
 * Description: A bullet-proof thumbnail/image finder for both post and term objects
 * Version: 1.0.0
 * Author: Paul Sandberg
 * Author URI: http://www.uwpweb.com
 * License: GPL2
 */
if (!defined('ABSPATH')) {
	exit;
}

// Load plugin class files
require_once 'includes/class-utopian-images-template.php';
require_once 'includes/class-utopian-images-template-settings.php';

// Load plugin libraries
require_once 'includes/lib/class-utopian-images-template-admin-api.php';
require_once 'includes/lib/class-utopian-images-template-post-type.php';
require_once 'includes/lib/class-utopian-images-template-taxonomy.php';

/**
 * Returns the main instance of utopian_images_template to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object utopian_images_template
 */

register_activation_hook(__FILE__, 'uwp_create_default_values');

function uwp_create_default_values() {

	if (!get_option('uwp_thumbnail_meta_fields')) {
		update_option('uwp_thumbnail_meta_fields', 'img,image,thumb,thumbnail,pic,picture');
	}
	if (!get_option('uwp_thumbnail_priorities')) {
		update_option('uwp_thumbnail_priorities', 'thumbnail,attachment,meta,content');
	}

}

function uwp_get_upload_path() {
	$upload_dir = wp_upload_dir();
	$upload_dir['upload_folder'] = end(explode('/', $upload_dir['basedir']));
	return $upload_dir;
}

function uwp_strposa($haystack, $needles = array(), $offset = 0) {
	$chr = array();
	foreach ($needles as $needle) {
		$needle = trim($needle);
		if (strpos($haystack, $needle)) {
			$chr[$needle] = $res;
		}
	}
	if (empty($chr)) {
		return false;
	} else {
		return $chr;
	}

}

function uwp_file_exists($url) {
	$absolute = uwp_get_upload_path()['basedir'] . end(explode(uwp_get_upload_path()['upload_folder'], $url));
	if (file_exists($absolute)) {
		$return = true;
	} else {
		$return = false;

		if (@getimagesize($url)) {
			$return = true;
		} else {
		}
	}

	return $return;

}

class UWPRetrieveImage {

	function uwp_get_image_with_thumbnail($obj) {
		$thumburl = get_the_post_thumbnail_url($obj->id, 'full');
		if (uwp_file_exists($thumburl)) {
			$image = $thumburl;

		}
		return $image;
	}

	function uwp_get_image_with_attachment($obj) {
		$args = array(
			'numberposts' => 1,
			'order' => 'ASC',
			'fields' => 'ids',
			'post_mime_type' => 'image',
			'post_parent' => $obj->id,
			'post_type' => 'attachment',
		);

		$attachment = get_children($args)[0];

		if ($attachment) {
			$atturl = wp_get_attachment_url($attachment);
			if (uwp_file_exists($atturl)) {
				$image = $atturl;
			}

		}

		return $image;
	}

	function uwp_get_image_with_content($obj) {
		global $wpdb;
		if (in_array($obj->type, get_post_types('', 'names'))) {
			$content = $wpdb->get_var($wpdb->prepare(
				"SELECT post_content FROM $wpdb->posts WHERE ID = %d",
				$obj->id
			));
		} else {
			$content = $wpdb->get_var($wpdb->prepare(
				"SELECT term_description FROM $wpdb->terms WHERE term_id = %d",
				$obj->id
			));
		}
		preg_match_all('@src="([^"]+)"@', $content, $images);
		$images = array_pop($images);
		if ($images) {
			foreach ($images as $img) {
				if (uwp_strposa(' ' . $img . ' ', array('png', 'jpg', 'gif', 'jpeg', 'bmp'))) {
					if (uwp_file_exists($img)) {
						$image = $img;
						break 1;
					}
				}
			}
		}
		return $image;
	}

	function uwp_get_image_with_meta($obj) {
		global $wpdb;
		(!is_array($obj->meta) ? $obj->meta = array() : $obj->meta = $obj->meta);
		(in_array($obj->type, get_post_types('', 'names')) ? ($table = 'postmeta' AND $idfield = 'post_id') : ($table = 'termmeta' AND $idfield = 'term_id'));
		$keysearch = implode('|', $obj->meta);
		if (in_array($obj->type, get_post_types('', 'names'))) {
			$vs = $wpdb->get_col($wpdb->prepare(
				"SELECT meta_value FROM $wpdb->postmeta WHERE post_id = %d AND meta_key REGEXP %s",
				$obj->id, $keysearch
			));
		} else {
			$vs = $wpdb->get_col($wpdb->prepare(
				"SELECT meta_value FROM $wpdb->termmeta WHERE term_id = %d AND meta_key REGEXP %s",
				$obj->id, $keysearch
			));

		}
		if ($vs) {
			foreach ($vs as $v) {
				if (uwp_file_exists($v)) {
					$image = $v;
					break 1;
				}
			}
		}

		return $image;
	}

}

function utopian_images_template() {
	$instance = utopian_images_template::instance(__FILE__, '1.0.0');

	if (is_null($instance->settings)) {
		$instance->settings = utopian_images_template_Settings::instance($instance);
	}

	return $instance;
}

function uwp_thumb($args = null) {
	$UWPRetrieveImage = new UWPRetrieveImage();
	$uwp_thumbnail_priorities = get_option('uwp_thumbnail_priorities');
	(substr($uwp_thumbnail_priorities, -1) == ',' ? $uwp_thumbnail_priorities = substr($uwp_thumbnail_priorities, 0, -1) : $uwp_thumbnail_priorities = $uwp_thumbnail_priorities);
	$uwp_thumbnail_priorities = explode(',', $uwp_thumbnail_priorities);
	(!is_array($uwp_thumbnail_priorities) ? $uwp_thumbnail_priorities = array() : $uwp_thumbnail_priorities = $uwp_thumbnail_priorities);
	foreach ($uwp_thumbnail_priorities as $uwp_thumbnail_priority) {
		$uwp_thumbnail_priorities_arr[$uwp_thumbnail_priority] = $uwp_thumbnail_priority;
	}
	global $post, $wpdb;
	$id = $args['id'];
	$type = $args['type'];
	if (!$id) {
		if ($type) {
			if (in_array($type, get_post_types('', 'names'))) {
				$id = $post->ID;
			} else {
				$id = $wp_query->queried_object->term_id;
			}
		} else {
			if ($post->post_type) {
				$id = $post->ID;
				$type = $post->post_type;
			} else {
				$id = $wp_query->queried_object->term_id;
				$type = $wp_query->queried_object->taxonomy;
			}
		}
	} else {
		if (!$type) {
			$type = $wpdb->get_var($wpdb->prepare(
				"SELECT post_type FROM $wpdb->posts WHERE ID = %d",
				$id
			));
			if (!$type) {
				$type = $wpdb->get_var($wpdb->prepare(
					"SELECT taxonomy FROM $wpdb->term_taxonomy WHERE term_id = %d",
					$id
				));
			}
			if (!$type) {
				$return = null;
			}

		}

	}
	$obj = new stdClass;
	$obj->id = $id;
	$obj->type = $type;
	$obj->meta = explode(',', get_option('uwp_thumbnail_meta_fields'));
	$obj->require_file_exist = get_option('require_file_exist');

	if (!in_array($obj->type, get_post_types('', 'names'))) {
		unset($uwp_thumbnail_priorities_arr['thumbnail'], $uwp_thumbnail_priorities_arr['content'], $uwp_thumbnail_priorities_arr['attachment']);
	}
	foreach ($uwp_thumbnail_priorities_arr as $uwp_thumbnail_priorities_item) {
		$uwp_thumbnail_priorities[] = $uwp_thumbnail_priorities_item;
	}
	foreach ($uwp_thumbnail_priorities as $field) {
		$fc = 'uwp_get_image_with_' . $field;
		$image = $UWPRetrieveImage->$fc($obj);
		if ($image) {
			break 1;
		}
	}

	return $image;

}
utopian_images_template();

?>
