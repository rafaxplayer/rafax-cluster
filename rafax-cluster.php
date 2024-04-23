<?php
/**
 * Plugin Name:       Rafax Cluster
 * Description:       Bloques de gutemberg para crear cluster de categorias y posts.
 * Requires at least: 6.1
 * Requires PHP:      7.0
 * Version:           0.1.1
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
class rfc_cluster
{

	function __construct()
	{

		add_action('init', [$this, 'rfc_cluster_init']);
		add_action('admin_init', [$this, 'rfc_settings']);
		add_filter('block_categories_all', [$this, 'rfc_add_custom_category_blocks'], 10, 2);

	}

	function rfc_cluster_init()
	{
		//enqueue scripts
		$this->rfc_register_scripts();

		add_action('admin_menu', [$this, 'rfc_settings_page']);
		add_action('admin_init', [$this, 'rfc_settings']);

		//error_log(print_r($options, true));

		//Register blocks
		if (!function_exists('register_block_type')) {
			return;
		}

		// registro del bloque
		register_block_type(
			'rafax/cluster-entradas',
			array(
				'attributes' => array(
					'showFeaturedImage' => array(
						'type' => 'boolean',
						'default' => true,
					),
					'includePosts' => array(
						'type' => 'array',
						'default' => array(),
					),
					'excludePosts' => array(
						'type' => 'array',
						'default' => array(),
					),
					'category' => array(
						'type' => 'string',
						'default' => 'all',
					),
					'numberPosts' => array(
						'type' => 'string',
						'default' => 6,
					),
					'orderBy' => array(
						'type' => 'string',
						'default' => 'date',
					),
					'order' => array(
						'type' => 'string',
						'default' => 'DESC',
					),
					'styleGrid' => array(
						'type' => 'string',
						'default' => 'grid-cols-3'
					),
					'typeSelect' => array(
						'type' => 'string',
						'default' => '1'
					),
				),
				'editor_script' => 'rfc_editor_script',
				'editor_style' => 'rfc_editor-styles',
				'style' => 'rfc_frontend-styles',
				'render_callback' => array($this, 'rfc_block_entradas_callback'),

			)
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
						'type' => 'integer',
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
				'editor_script' => 'rfc_editor_script',
				'editor_style' => 'rfc-editor-styles',
				'style' => 'rfc-frontend-styles',
				'render_callback' => array($this, 'rfc_block_categorias_callback'),
			)
		);

		register_block_type(
			'rafax/directorist-csv',
			array(
				'attributes' => array(
					'csvFile' => array(
						'type' => 'object',
						'default' => ''
					),
					'numberItems' => array(
						'type' => 'string',
						'default' => '10'
					),
					'rand' => array(
						'type' => 'boolean',
						'default' => false
					),
					'removeCsv' => array(

						'type' => 'string',
						'default' => ''
					),
					'plantilla' => array(
						'type' => 'string',
						'default' => '1'
					)

				),
				'editor_script' => 'rfc_editor_script',
				'editor_style' => 'rfc-editor-styles',
				'style' => 'rfc-frontend-styles',
				'render_callback' => array($this, 'rfc_block_directorist_callback'),
			)
		);


	}



	/*
																		 /*SETTINGS AND OPTIONS
																		 */
	function rfc_settings_page()
	{
		add_menu_page(__('Rafax clusters settings', 'rafax-cluster'), __('Rafax cluster', 'rafax-cluster'), 'manage_options', 'rfc_settings', [$this, 'rfc_setting_page'], 'dashicons-welcome-widgets-menus', 80);
	}

	function rfc_settings()
	{
		// plugin settings
		register_setting('rfc_options', 'rfc_options');

		add_settings_section('rfc_settings_section', __('Opciones de Rafax cluster', 'rafax-cluster'), [$this, 'rfc_section_callback'], 'rfc_options');

		add_settings_field('rfc_remove_uninstall', __('Eliminar todos los datos del plugin al desinstalar', 'rafax-cluster'), [$this, 'rfc_remove_uninstall_option'], 'rfc_options', 'rfc_settings_section');

		add_settings_field('rfc_template_directory', __('Edita la plantilla para los elemntos de directorist block', 'rafax-cluster'), [$this, 'rfc_template_directory_option'], 'rfc_options', 'rfc_settings_section');


	}

	function rfc_remove_uninstall_option()
	{
		$options = get_option('rfc_options');

		?>
		<input type="checkbox" id="rfc_remove_uninstall" name="rfc_options[rfc_remove_uninstall]" value="1" <?php checked(isset($options['rfc_remove_uninstall'])); ?> />
		<?php
	}

	function rfc_template_directory_option()
	{
		$options = get_option('rfc_options');

		?>
		<textarea rows="15" style="width:600px" id="rfc_template_directory" name="rfc_options[rfc_template_directory]"
			value="1"></testarea> <?php

	}

	function rfc_section_callback()
	{
		?>
												<p> <?php echo __('Usar imagenes en las categorias ( Utiliza el plugin category images )', 'rafax-cluster') ?></p> <a href="https://zahlan.net/blog/2012/06/categories-images/" target="_blank">Intrucciones para implementar la imagen en la plantilla</a>
												<?php
	}

	function rfc_setting_page()
	{
		if (!current_user_can('manage_options'))
			wp_die(__('You do not have sufficient permissions to access this page.', 'rafax-cluster'));
		?>
													<div class="wrap">
													<h2><?php _e('Rafax cluster', 'rafax-cluster'); ?></h2>
														<form method="post" action="options.php">
															<?php settings_fields('rfc_options'); ?>
															<?php do_settings_sections('rfc_options'); ?>
															<?php submit_button(); ?>
														</form>
													</div>
													<?php
	}


	/* REGISTER AND ENQUEUE SCRIPTS */
	function rfc_register_scripts()
	{
		wp_register_script('rfc_editor_script', plugins_url('build/index.js', __FILE__), array('wp-blocks', 'wp-element', 'wp-editor', 'wp-i18n', 'wp-components'), filemtime(plugin_dir_path(__FILE__) . 'build/index.js'));

		// estilos
		wp_register_style('rfc-editor-styles', plugins_url('editor.css', __FILE__), array('wp-edit-blocks'), filemtime(plugin_dir_path(__FILE__) . 'editor.css'));

		// estilos frontend
		wp_register_style('rfc-frontend-styles', plugins_url('style.css', __FILE__), array(), filemtime(plugin_dir_path(__FILE__) . 'style.css'));


	}
	// csv to array for fc_block_directorist_callback
	function csvToArray($filename, $count)
	{
		$csvData = [];
		if (($handle = fopen($filename, "r")) !== FALSE) {
			$headers = fgetcsv($handle, 1000, ",");
			while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
				$row = array();
				for ($i = 0; $i < count($headers); $i++) {
					$row[$headers[$i]] = $data[$i];
				}
				$csvData[] = $row;
				if (count($csvData) > $count) {
					break;
				}
			}
			fclose($handle);
		}
		return $csvData;
	}
	// mapped_implode for fc_block_directorist_callback
	function mapped_implode($glue, $array)
	{
		return implode(
			$glue,
			array_map(
				function ($k, $v) {
					return '[' . $v . ']';
				},
				array_keys($array),
				array_values($array)
			)
		);
	}


	/* BLOCKS */
	// callback to register block
	function rfc_block_directorist_callback($attributes, $content)
	{

		if (!empty($attributes['removeCsv'])) {

			wp_delete_attachment($attributes['removeCsv'], true);
			//error_log(print_r($attributes['removeCsv'], true));

		}

		if (!empty($attributes['csvFile']) && !empty($attributes['csvFile']['url'])) {

			$csv = $this->csvToArray($attributes['csvFile']['url'], $attributes['numberItems'], );

			if (count($csv) > 0) {

				if ($attributes['rand']) {
					shuffle($csv);
				}

				$output = '<div class="cluster cluster-posts style-4">';
				if (current_user_can('administrator')) {
					$output .= '<div class="rfc-keys">' . $this->mapped_implode(' ', array_keys($csv[0])) . '</div>';
				}
				$count = 1;
				$path = plugin_dir_path(__FILE__) . '/templates/directory-' . $attributes['plantilla'] . '.php';
				foreach ($csv as $row) {

					include ($path);
					$count++;

				}

				$output .= '</div>';
			}

			return $output;

		}

		return 'No hay csv ';
	}

	//callback to register block
	function rfc_block_categorias_callback($attributes, $content)
	{
		$args = array(
			'taxonomy' => 'category',
			'hide_empty' => $attributes['hideEmpty'],
			'exclude' => $attributes['excludeCats'],
		);

		$args['number'] = empty($attributes['numberCats']) ? -1 : $attributes['numberCats'];

		if ($attributes['showOnlyParent']) {
			$args['parent'] = 0;
		}

		if ($attributes['showParent'] > 0) {
			$args['parent'] = $attributes['showParent'];
		}

		$categories = get_categories($args);

		if (!$categories) {

			return sprintf('<p style="color:red;font-weight:bold">%s</p>', __('No hay categorias', 'rafax-cluster'));

		}

		$target = $attributes['targetBlank'] ? 'target="_blank"' : '';

		$output = '<div class="cluster cluster-cats ' . $attributes['styleGrid'] . ' style-4 ">';

		foreach ($categories as $cat) {

			$output .= '<a ' . $target . ' class="post-grid-item vertical" href="' . get_category_link($cat->term_id) . '">';

			if (function_exists('z_taxonomy_image_url') && z_taxonomy_image_url($cat->term_id)) {
				$output .= '<div class="thumb">';
				$output .= sprintf('<img src="%s" alt="%s" />', z_taxonomy_image_url($cat->term_id, 'medium'), $cat->name);
				$output .= '</div>';
			}
			$output .= '<div class="content" >';
			$output .= '<div class="title" >';
			$output .= $attributes['showCount'] ? sprintf('<h3>%s ( %s )</h3>', $cat->name, $cat->count) : sprintf('<h3>%s</h3>', $cat->name);
			$output .= '</div>';
			$output .= $attributes['showDescription'] ? '<div class="description" ><p>' . $cat->description . '</p></div> ' : '';
			$output .= '</div>';
			$output .= '</a>';

		}

		$output .= '</div>';
		return $output;
	}

	//callback to register block
	function rfc_block_entradas_callback($attributes)
	{
		$postId = get_the_ID();

		error_log(print_r($attributes, true));

		$args = array(
			'post_status' => 'publish',
			'orderby' => $attributes['orderBy'],
			'order' => $attributes['order']
		);

		if (count($attributes['includePosts']) === 0) {

			$args['numberposts'] = empty($attributes['numberPosts']) ? -1 : $attributes['numberPosts'];
			$args['exclude'] = array_merge($attributes['excludePosts'], array($postId));

			if ($attributes['category'] !== 'all') {
				$args['category'] = $attributes['category'];
			}
		} else {
			$args['include'] = $attributes['includePosts'];
		}

		$posts = get_posts($args);

		if (!$posts) {
			return sprintf('<p style="color:red;font-weight:bold">%s</p>', __('No hay entradas', 'rafax-cluster'));

		}

		$output = '<div class="cluster cluster-posts ' . $attributes['styleGrid'] . ' style-4">';

		foreach ($posts as $post) {
			$output .= '<a id="' . $post->ID . '" href="' . get_the_permalink($post->ID) . '" class="post-grid-item vertical">';
			$output .= '<div class="thumb">';
			$output .= $attributes['showFeaturedImage'] && has_post_thumbnail($post->ID) ? '<img src="' . get_the_post_thumbnail_url($post->ID, 'medium') . '" alt="' . $post->post_title . '" />' : '';
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

	function rfc_add_custom_category_blocks($block_categories, $block_editor_context)
	{
		array_push(
			$block_categories,
			array('slug' => 'rafax-blocks', 'title' => __('Rafax Blocks', 'rafax-cluster'), 'icon' => '')
		);
		return $block_categories;
	}

}

if (class_exists('rfc_cluster')) {

	new rfc_cluster();

}