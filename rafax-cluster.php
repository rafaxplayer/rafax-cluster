<?php
/**
 * Plugin Name:       Rafax Cluster
 * Description:       Bloques de gutemberg para crear cluster de categorias y posts.
 * Requires at least: 6.1
 * Requires PHP:      7.0
 * Version:           0.1.0
 * Author:            The WordPress Contributors
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       rafax-cluster
 *
 * @package           create-block
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function rafax_cluster_block_init()
{
	if (!function_exists('register_block_type')) {
		return;
	}

	wp_register_script('rafax_editor_script', plugins_url('build/index.js', __FILE__), array('wp-blocks', 'wp-element', 'wp-editor', 'wp-i18n', 'wp-components'), filemtime(plugin_dir_path(__FILE__) . 'build/index.js'));

	// estilos
	wp_register_style('rafax-editor-styles', plugins_url('editor.css', __FILE__), array('wp-edit-blocks'), filemtime(plugin_dir_path(__FILE__) . 'editor.css'));

	// estilos frontend
	wp_register_style('rafax-frontend-styles', plugins_url('style.css', __FILE__), array(), filemtime(plugin_dir_path(__FILE__) . 'style.css'));


	// registro del bloque
	register_block_type(
		'rafax/cluster-entradas',
		[
			'attributes' => [
				'showFeaturedImage' => [
					'type' => 'boolean',
					'default' => false,
				],
				'includePosts' => [
					'type' => 'array',
					'default' => [],
				],
				'excludePosts' => [
					'type' => 'array',
					'default' => [],
				],
				'category' => [
					'type' => 'string',
					'default' => 'all',
				],
				'numberPosts' => [
					'type' => 'string',
					'default' => 6,
				],
				'styleGrid' => [
					'type' => 'string',
					'default' => 'grid-cols-3'
				],

			],

			'editor_script' => 'rafax_editor_script',
			'editor_style' => 'rafax-editor-styles',
			'style' => 'rafax-frontend-styles',
			'render_callback' => 'rafax_cluster_entradas_callback',

		]
	);

	// registro del bloque
	register_block_type(
		'rafax/cluster-categorias',
		array(
			'attributes' => array(
				'showOnlyParent' => array(
					'type' => 'boolean',
					'default' => false
				),
				'showCount' => array(
					'type' => 'boolean',
					'default' => false
				),
				'numberCats' => array(
					'type' => 'string',
					'default' => 0
				),
				'hideEmpty' => array(
					'type' => 'boolean',
					'default' => false
				),
				'excludeCats' => array(
					'type' => 'array',
					'default' => array()
				),

				'showParent' => array(
					'type' => 'string',
					'default' => 0
				),
				'targetBlank' => array(
					'type' => 'boolean',
					'default' => false
				),
				'showDescription' => array(
					'type' => 'boolean',
					'default' => false
				),
				'styleGrid' => array(
					'type' => 'string',
					'default' => 'grid-cols-3'
				),
			),

			'editor_script' => 'rafax_editor_script',
			'editor_style' => 'rafax-editor-styles',
			'style' => 'rafax-frontend-styles',
			'render_callback' => 'rafax_cluster_categorias_callback',
		)
	);

}

add_action('init', 'rafax_cluster_block_init');

function rafax_cluster_categorias_callback($attributes, $content)
{
	$args = array(
		'taxonomy' => 'category',
		'hide_empty' => $attributes['hideEmpty'],
		'number' => $attributes['numberCats'],
		'exclude' => $attributes['excludeCats'],

	);
	if ($attributes['showOnlyParent']) {
		$args['parent'] = 0;
	}

	if ($attributes['showParent'] > 0) {
		$args['parent'] = $attributes['showParent'];
	}
	//error_log(print_r($args, true));
	
	$categories = get_categories($args);

	if (!$categories) {

		return 'No hay categorías';

	}

	$target = $attributes['targetBlank'] ? 'target="_blank"' : '';

	$output = '<div class="cluster cluster-cats ' . $attributes['styleGrid'] . ' style-4 ">';


	foreach ($categories as $cat) {

		$output .= '<a ' . $target . ' class="post-grid-item vertical" href="' . get_category_link($cat->term_id) . '">';
		$output .= '<div class="content" >';
		$output .= '<div class="title" >';
		$output .= $attributes['showCount'] ? sprintf('<h3>%s ( %s )</h3>', $cat->name, $cat->count) : '<h3>' . $cat->name . '</h3>';
		$output .= '</div>';
		$output .= $attributes['showDescription'] ? '<div class="description" ><p>' . $cat->description . '</p></div> ' : '';
		$output .= '</div>';
		$output .= '</a>';

	}

	$output .= '</div>';
	return $output;
}

function rafax_cluster_entradas_callback($attributes)
{
	$postId = get_the_ID();

	//error_log(print_r($attributes, true));

	$args = array(

		'post_status' => 'publish',
	);

	if (count($attributes['includePosts']) === 0) {

		$args['numberposts'] = $attributes['numberPosts'] > 0 ? $attributes['numberPosts'] : -1;
		$args['exclude'] = array_merge($attributes['excludePosts'], array($postId));

		if ($attributes['category'] !== 'all') {
			$args['category'] = $attributes['category'];
		}
	} else {
		$args['include'] = $attributes['includePosts'];
	}

	
	$posts = get_posts($args);

	

	$output = '<div class="cluster cluster-posts ' . $attributes['styleGrid'] . ' style-4">';

	foreach ($posts as $post) {
		$output .= '<a id="' . $post->ID . '" href="' . get_the_permalink($post->ID) . '" class="post-grid-item vertical">';
		$output .= '<div class="thumb">';
		$output .= $attributes['showFeaturedImage'] ? '<img src="' . get_the_post_thumbnail_url($post->ID, 'medium') . '" alt="' . $post->post_title . '" />' : '';
		$output .= '</div>';
		$output .= '<div class="content">';
		$output .= '<div class="title" >';
		$output .= '<h3>' . $post->post_title . '</h3>';
		$output .= '</div>';
		$output .= '</div>';
		$output .= '</a>';
	}

	$output .= '</div>';

	return $output;

}
// ya no es necesario porque no se renderiza en edit.js
/* add_action('rest_api_init', 'rafax_image_posts');
function rafax_image_posts()
{
	register_rest_field(
		array('post'),
		'image_post',
		array(
			'get_callback' => 'get_image_post',
			'update_callback' => null,
			'schema' => null
		)
	);
} */

function get_image_post($object, $field_name, $request)
{
	if ($object['featured_media']) {
		$imagen = wp_get_attachment_image_src($object['featured_media'], 'medium');
		return $imagen[0];
	}
	return false;
}

function rafax_custom_category_blocks($block_categories, $block_editor_context)
{
	array_push(
		$block_categories,
		array('slug' => 'rafax-blocks', 'title' => __('Rafax Blocks', 'rafax-cluster'), 'icon' => '')
	);
	return $block_categories;
}
add_filter('block_categories_all','rafax_custom_category_blocks',10,2);