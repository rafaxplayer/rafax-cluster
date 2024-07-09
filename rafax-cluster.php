<?php
/**
 * Plugin Name:       Rafax Cluster
 * Description:       Bloques de gutemberg para crear cluster de categorias y posts.
 * Requires at least: 6.1
 * Requires PHP:      7.0
 * Version:           0.1.2
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
class RafaxCluster
{



	function __construct()
	{

		add_action('init', [$this, 'init']);
		add_action('admin_init', [$this, 'setSettings']);
		add_filter('block_categories_all', [$this, 'customCategoryBlocks'], 10, 2);

	}

	function init()
	{
		//enqueue scripts
		$this->registerScripts();


		add_action('admin_menu', [$this, 'setSettingsPage']);
		add_action('admin_init', [$this, 'setSettings']);
		//cargar scripts de admin para mediaupload
		add_action('admin_enqueue_scripts', [$this, 'enqueueMediaScripts']);


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
				'render_callback' => array($this, 'blockPostsCallback'),

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
					'showImages' => array(
						'type' => 'boolean',
						'default' => false
					),
					'numberCats' => array(
						'type' => 'integer',
						'default' => 100
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
				'render_callback' => array($this, 'blockCategoriesCallback'),
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
					),

				),
				'editor_script' => 'rfc_editor_script',
				'editor_style' => 'rfc-editor-styles',
				'style' => 'rfc-frontend-styles',
				'render_callback' => array($this, 'blockDirectoristCallback'),
			)
		);


	}

	// Necesario para poder usar la biblioteca de medios
	function enqueueMediaScripts()
	{
		wp_enqueue_media();
	}

	/*SETTINGS AND OPTIONS*/

	function setSettingsPage()
	{
		add_menu_page(__('Rafax clusters settings', 'rafax-cluster'), __('Rafax cluster', 'rafax-cluster'), 'manage_options', 'setSettings', [$this, 'settingsPage'], 'dashicons-welcome-widgets-menus', 80);
	}

	function setSettings()
	{

		// plugin settings
		register_setting('rfc_options', 'rfc_options');

		add_settings_section(
			'settingsSection',
			__('Opciones de Rafax cluster', 'rafax-cluster'),
			[$this, 'sectionCallback'],
			'rfc_options'
		);
		add_settings_field(
			'rfc_custom_image',
			__('Imagen por defecto en bloque de entradas y categorias', 'rafax-cluster'),
			[$this, 'customImageOption'],
			'rfc_options',
			'settingsSection'
		);

		add_settings_field(
			'rfc_images_size',
			__('Elige el tamaño de las imagenes de posts y categorias cluster', 'rafax-cluster'),
			[$this, 'imagesSizeOption'],
			'rfc_options',
			'settingsSection'
		);

		add_settings_field(
			'rfc_remove_uninstall',
			__('Eliminar todos los datos del plugin al desinstalar', 'rafax-cluster'),
			[$this, 'removeUninstallOption'],
			'rfc_options',
			'settingsSection'
		);


	}

	// Callback para renderizar el campo de la imagen personalizada
	function customImageOption()
	{
		$options = get_option('rfc_options');
		$image_url = isset($options['rfc_custom_image']) ? $options['rfc_custom_image'] : '';
		?>
		<input type="hidden" name="rfc_options[rfc_custom_image]" id="rfc_custom_image"
			value="<?php echo esc_attr($image_url); ?>" />
		<input type="button" id="upload_image_button" class="button"
			value="<?php _e('Seleccionar imagen', 'rafax-cluster'); ?>" />
		<button id="remove_image_button" class="button"><?php _e('Eliminar imagen', 'rafax-cluster'); ?></button>
		<div id="preview_image">
			<?php if (!empty($image_url)): ?>
				<img src="<?php echo esc_url($image_url); ?>" style="max-width: 100px;" />
			<?php endif; ?>
		</div>
		<script>
			jQuery(document).ready(function ($) {
				$('#upload_image_button').click(function () {
					var mediaUploader;
					if (mediaUploader) {
						mediaUploader.open();
						return;
					}
					mediaUploader = wp.media.frames.file_frame = wp.media({
						title: '<?php _e('Seleccionar imagen', 'rafax-cluster'); ?>',
						button: {
							text: '<?php _e('Seleccionar imagen', 'rafax-cluster'); ?>'
						},
						multiple: false
					});
					mediaUploader.on('select', function () {
						attachment = mediaUploader.state().get('selection').first().toJSON();
						$('#rfc_custom_image').val(attachment.url);
						$('#preview_image').html('<img src="' + attachment.url + '" style="max-width: 100px;" />');
					});
					mediaUploader.open();
				});
				$(document).on('click', '#remove_image_button', function () {
					$('#rfc_custom_image').val('');
					$('#preview_image').html('');
				});
			});
		</script>
		<?php
	}

	function imagesSizeOption()
	{

		$options = get_option('rfc_options');

		?>

		<select name="rfc_options[rfc_images_size]" id="rfc_images_size">
			<option value="thumbnail" <?php selected($options['rfc_images_size'], "thumbnail"); ?>>Small
				<?php echo get_option("thumbnail_size_w") . 'x' . get_option("thumbnail_size_h"); ?>
			</option>
			<option value="medium" <?php selected($options['rfc_images_size'], "medium"); ?>>Medium
				<?php echo get_option("medium_size_w") . 'x' . get_option("medium_size_h"); ?>
			</option>
			<option value="medium_large" <?php selected($options['rfc_images_size'], "medium_large"); ?>>Medium Large
				<?php echo get_option("medium_large_size_w") . 'x' . get_option("medium_large_size_h"); ?>
			</option>
			<option value="large" <?php selected($options['rfc_images_size'], "large"); ?>>Large
				<?php echo get_option("large_size_w") . 'x' . get_option("large_size_h"); ?>
			</option>
		</select>
		<?php


	}

	function removeUninstallOption()
	{
		$options = get_option('rfc_options');

		?>
		<input type="checkbox" id="rfc_remove_uninstall" name="rfc_options[rfc_remove_uninstall]" value="1" <?php checked(isset($options['rfc_remove_uninstall'])); ?> />
		<?php
	}




	function sectionCallback()
	{
		?>
		<h4> <?php echo __('Para usar imagenes en el bloque de categorias, tienes dos opciones:', 'rafax-cluster') ?></h4>
		<ul>
			<li><?php echo esc_html(__('Utilizar el plugin Images category', 'rafax-cluster')) ?> | <a
					href="https://zahlan.net/blog/2012/06/categories-images/" target="_blank">Intrucciones para implementar la
					imagen en la plantilla</a></li>
			<li><?php echo esc_html(__('Utilizar el tema Wasabi', 'rafax-cluster')) ?> | <a href="https://wasabitheme.com/"
					target="_blank">Url del tema</a></li>
		</ul>
		<?php
	}

	function settingsPage()
	{
		if (!current_user_can('manage_options')) {
			wp_die(__('You do not have sufficient permissions to access this page.', 'rafax-cluster'));
		} ?>

		<div class="wrap">
			<h2>

				<?php _e('Rafax cluster', 'rafax-cluster'); ?>
			</h2>
			<form method="post" action="options.php">
				<?php submit_button(); ?>
				<?php settings_fields('rfc_options'); ?>
				<?php do_settings_sections('rfc_options'); ?>
				<?php submit_button(); ?>
			</form>
		</div><?php
	}


	/* REGISTER AND ENQUEUE SCRIPTS */
	function registerScripts()
	{
		wp_register_script('rfc_editor_script', plugins_url('build/index.js', __FILE__), array('wp-blocks', 'wp-element', 'wp-editor', 'wp-i18n', 'wp-components'), filemtime(plugin_dir_path(__FILE__) . 'build/index.js'));

		wp_localize_script(
			'rfc_editor_script',
			'phpData',
			array(
				'pSetUrl' => admin_url('admin.php?page=setSettings'),
			)
		);

		// estilos
		wp_register_style('rfc-editor-styles', plugins_url('editor.css', __FILE__), array('wp-edit-blocks'), filemtime(plugin_dir_path(__FILE__) . 'editor.css'));

		// estilos frontend
		wp_register_style('rfc-frontend-styles', plugins_url('style.css', __FILE__), array(), filemtime(plugin_dir_path(__FILE__) . 'style.css'));


	}

	/* BLOCKS */
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

	function getImageDefault()
	{
		$options = get_option('rfc_options');

		$imageDefault = $options['rfc_custom_image'];

		return $imageDefault ? $imageDefault : '';
	}

	// callback to register blocks
	function blockDirectoristCallback($attributes, $content)
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

				// mostrar los encabezados al admin solo
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
	function blockCategoriesCallback($attributes, $content)
	{
		$args = array(
			'taxonomy' => 'category',
			'hide_empty' => $attributes['hideEmpty'],
			'exclude' => $attributes['excludeCats'],
		);
		$options = get_option('rfc_options');

		$imageSize = $options['rfc_images_size'];

		$args['number'] = $attributes['numberCats'] < 1 ? -1 : $attributes['numberCats'];

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

			$output .= '<a ' . $target . ' class="post-grid-item vertical" href="' . esc_url(get_category_link($cat->term_id)) . '">';

			if ($attributes['showImages']) {
				$output .= '<div class="thumb">';

				//compatibilidad con imagenes en el bloque de categoria si esta presente el plugin "Images Category" o el tema "Wasabi"
				$image_url = $this->getImageDefault();

				if (function_exists('z_taxonomy_image_url') && z_taxonomy_image_url($cat->term_id)) {

					$image_url = esc_url(z_taxonomy_image_url($cat->term_id, $imageSize));
				} elseif (defined('WASABI_THEME_PATH')) {

					$image_id = get_term_meta($cat->term_id, 'wasabi_hero_image_id', true);

					if ($image_id) {
						$image_url = esc_url(wp_get_attachment_image_url($image_id, $imageSize));

					}

				}
				$output .= $image_url ? sprintf('<img src="%s" alt="%s" />', $image_url, esc_attr($cat->name)) : '';
				$output .= '</div>';

			}

			$output .= '<div class="content" >';
			$output .= '<div class="title" >';
			$output .= $attributes['showCount'] ? sprintf('<h3>%s ( %s )</h3>', esc_html($cat->name), $cat->count) : sprintf('<h3>%s</h3>', esc_html($cat->name));
			$output .= '</div>';
			$output .= $attributes['showDescription'] ? '<div class="description" ><p>' . esc_html($cat->description) . '</p></div> ' : '';
			$output .= '</div>';
			$output .= '</a>';

		}

		$output .= '</div>';
		return $output;
	}

	//callback to register block
	function blockPostsCallback($attributes)
	{
		$postId = get_the_ID();

		$options = get_option('rfc_options');

		$imageSize = $options['rfc_images_size'];


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
			$output .= '<a id="' . esc_attr($post->ID) . '" href="' . esc_url(get_the_permalink($post->ID)) . '" class="post-grid-item vertical">';
			if ($attributes['showFeaturedImage']) {
				$image_url = has_post_thumbnail($post->ID) ? get_the_post_thumbnail_url($post->ID, $imageSize) : $this->getImageDefault();
				$output .= '<div class="thumb">';
				$output .= $image_url ? '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($post->post_title) . '" />' : '';
				$output .= '</div>';
			}
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

	function customCategoryBlocks($block_categories, $block_editor_context)
	{
		array_push(
			$block_categories,
			array('slug' => 'rafax-blocks', 'title' => __('Rafax Blocks', 'rafax-cluster'), 'icon' => '')
		);
		return $block_categories;
	}




}

if (class_exists('RafaxCluster')) {

	new RafaxCluster();

}