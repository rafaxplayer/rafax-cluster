<?php
/**
 * Plugin Name:       Rafax Cluster
 * Description:       Bloques de gutemberg para crear cluster de categorias y posts.
 * Requires at least: 6.1
 * Requires PHP:      7.0
 * Version:           1.3
 * Author:            Rafax
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       rafax-cluster
 *
 * @package           create-block
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}


class RafaxCluster
{

	function __construct()
	{
		add_action('init', [$this, 'init']);
		
		add_action('admin_menu', [$this, 'setSettingsPage']);
		add_filter('block_categories_all', [$this, 'customCategoryBlocks'], 10, 2);

	}
	function init()
	{
		//enqueue scripts
		$this->registerScripts();

		//cargar scripts de admin para mediaupload
		add_action('admin_enqueue_scripts', [$this, 'enqueueMediaScripts']);

		// funciones ajax
		add_action('wp_ajax_filter_categories', [$this, 'blockCategoriesCallback']);
		add_action('wp_ajax_nopriv_filter_categories', [$this, 'blockCategoriesCallback']);

		//metabox
		add_action('add_meta_boxes', [$this,'custom_add_metabox']);
		add_action('save_post', [$this,'custom_save_metabox']);

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
					'contentType' => array(
						'type' => 'string',
						'default' => 'post'
					),
					'parentPage' => array(
						'type' => 'number',
						'default' => 0
					),
					'postType' => array(
						'type' => 'string',
						'default' => '1'
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
					'showSearch' => array(
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

	}

	// Necesario para poder usar la biblioteca de medios
	function enqueueMediaScripts()
	{
		if (is_admin() && isset($_GET['page']) && $_GET['page'] === 'setSettings') {
			wp_enqueue_media();
		}

	}

	/*SETTINGS AND OPTIONS*/
	function setSettingsPage()
	{
		add_menu_page(__('Rafax clusters settings', 'rafax-cluster'), __('Rafax cluster', 'rafax-cluster'), 'manage_options', 'setSettings', [$this, 'settingsPage'], 'dashicons-welcome-widgets-menus', 80);
		add_action('admin_init', [$this, 'setSettings']);
	}

	function setSettings()
	{

		register_setting('rfc_options', 'rfc_options', function ($input) {
			$output = [];
			if (isset($input['rfc_custom_image_nonce']) && wp_verify_nonce($input['rfc_custom_image_nonce'], 'rfc_custom_image_nonce')) {
				$output['rfc_custom_image'] = esc_url_raw($input['rfc_custom_image']);
			}
			if (isset($input['rfc_images_size'])) {
				$valid_sizes = ['thumbnail', 'medium', 'medium_large', 'large'];
				$output['rfc_images_size'] = in_array($input['rfc_images_size'], $valid_sizes) ? $input['rfc_images_size'] : 'thumbnail';
			}
			
			// Validar el CSS
			if (isset($input['rfc_custom_css'])&& wp_verify_nonce($input['rfc_customcss_nonce'], 'rfc_customcss_nonce')) {
				$output['rfc_custom_css'] = trim($input['rfc_custom_css']); // Eliminar espacios innecesarios
			}
			if (isset($input['rfc_remove_uninstall'])) {
				$output['rfc_remove_uninstall'] = (bool) $input['rfc_remove_uninstall'];
			}
			return $output;
		});

		add_settings_section(
			'settingsSection',
			__('Opciones de Rafax cluster', 'rafax-cluster'),
			[$this, 'sectionCallback'],
			'rfc_options'
		);

		add_settings_field('rfc_custom_image', __('Imagen por defecto', 'rafax-cluster'), [$this, 'customImageOption'], 'rfc_options', 'settingsSection');
		add_settings_field('rfc_images_size', __('Tamaño de las imágenes', 'rafax-cluster'), [$this, 'imagesSizeOption'], 'rfc_options', 'settingsSection');
		add_settings_field('rfc_custom_css', __('CSS adicional', 'rafax-cluster'), [$this, 'customCssOption'], 'rfc_options', 'settingsSection');
		add_settings_field('rfc_remove_uninstall', __('Eliminar datos al desinstalar', 'rafax-cluster'), [$this, 'removeUninstallOption'], 'rfc_options', 'settingsSection');


	}

	//Metabox para titulos
	function custom_add_metabox() {
		// Añadir metabox para "post" y "page"
		add_meta_box(
			'custom_cluster_title',             // ID del metabox
			__('Rafax Cluster', 'rafax-cluster'), // Título del metabox
			[$this,'custom_metabox_content'],           // Callback que mostrará el contenido
			['post', 'page'],                   // Tipos de contenido donde aparecerá (entradas y páginas)
			'side',                             // Contexto donde aparecerá el metabox ('normal', 'side', 'advanced')
			'default'                           // Prioridad del metabox ('high', 'default', 'low')
		);
	}

	function custom_metabox_content($post) {
		// Recuperar el valor guardado del título personalizado
		$custom_title = get_post_meta($post->ID, '_custom_cluster_title', true);
		wp_nonce_field('custom_cluster_title_nonce', 'custom_cluster_title_nonce_field');
	
		echo '<label for="custom_cluster_title">' . __('Título en el Cluster:', 'rafax-cluster') . '</label>';
		echo '<input type="text" id="custom_cluster_title" name="custom_cluster_title" value="' . esc_attr($custom_title) . '" class="widefat" />';
	}

	function custom_save_metabox($post_id) {
		// Verificar nonce para asegurar que la solicitud es válida
		if (!isset($_POST['custom_cluster_title_nonce_field']) || !wp_verify_nonce($_POST['custom_cluster_title_nonce_field'], 'custom_cluster_title_nonce')) {
			return $post_id;
		}
	
		// No guardar si el post está en autosave
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return $post_id;
		}
	
		// Verificar si el campo está presente en la solicitud
		if (isset($_POST['custom_cluster_title'])) {
			// Guardar el título personalizado
			update_post_meta($post_id, '_custom_cluster_title', sanitize_text_field($_POST['custom_cluster_title']));
		}
	}
	

	// Callback para renderizar el campo de la imagen personalizada
	function customImageOption()
	{
		$nonce = wp_create_nonce('rfc_custom_image_nonce');
		$options = get_option('rfc_options');
		$image_url = isset($options['rfc_custom_image']) ? $options['rfc_custom_image'] : '';
		?>
		<input type="hidden" name="rfc_options[rfc_custom_image_nonce]" value="<?php echo esc_attr($nonce); ?>" />
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
	function customCssOption()
	{

		$options = get_option('rfc_options');
		$nonce=wp_create_nonce('rfc_customcss_nonce');
		?>
		<input type="hidden" name="rfc_options[rfc_customcss_nonce]" value="<?php echo esc_attr($nonce); ?>" />
		<textarea name="rfc_options[rfc_custom_css]" id="rfc_custom_css" cols="50"
			rows="8"><?php echo isset($options['rfc_custom_css']) ? esc_textarea($options['rfc_custom_css']) : ''; ?></textarea>
		<?php
	}
	function removeUninstallOption()
	{
		$options = get_option('rfc_options');

		?>
		<input type="checkbox" id="rfc_remove_uninstall" name="rfc_options[rfc_remove_uninstall]" value="1" <?php checked(!empty($options['rfc_remove_uninstall']) ? 1 : 0); ?> />
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

		wp_register_script('rfc_filter_categories', plugins_url('assets/ajax-filter.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . 'assets/ajax-filter.js'));

		wp_localize_script(
			'rfc_filter_categories',
			'phpData',
			[
				'ajax_url' => admin_url('admin-ajax.php'),
				'pSetUrl' => admin_url('admin.php?page=setSettings'),
				'security' => wp_create_nonce('ajax_nonce'),
			]
		);
		wp_enqueue_script('rfc_filter_categories');

		// estilos
		wp_register_style('rfc-editor-styles', plugins_url('assets/editor.css', __FILE__), array('wp-edit-blocks'), filemtime(plugin_dir_path(__FILE__) . 'assets/editor.css'));

		// estilos frontend
		wp_register_style('rfc-frontend-styles', plugins_url('style.css', __FILE__), array(), filemtime(plugin_dir_path(__FILE__) . 'style.css'));

		
		 //añadir css de las opciones
		$options = get_option('rfc_options');
		$custom_css = isset($options['rfc_custom_css']) ? $options['rfc_custom_css'] : '';

		// Si hay CSS personalizado, agregarlo
		if (!empty($custom_css)) {
						// Agregar el CSS personalizado al frontend
			wp_add_inline_style('rfc-frontend-styles', $custom_css);
		}
		


	}

	/* BLOCKS Functions*/
	function getPostImage()
	{
		$options = get_option('rfc_options');

		$imageSize = isset($options['rfc_images_size']) ? $options['rfc_images_size'] : 'medium';

		return has_post_thumbnail() ? get_the_post_thumbnail_url(null, $imageSize) : '';

	}

	/*obtener imagenes de las categorias*/
	function getCategoryImage($cat)
	{
		if (defined('WP_DEBUG') && WP_DEBUG) {
			//error_log('Category Object: ' . print_r($cat, true));
			//error_log('AJAX Data: ' . print_r($_POST, true));
		}
		// Validar el objeto de categoría
		if (!is_object($cat) || !isset($cat->term_id)) {
			return ''; // Retorna vacío si no es un objeto de término válido
		}

		// Obtener las opciones generales del plugin o tema
		$options = get_option('rfc_options', []);
		$default_image_url = !empty($options['rfc_custom_image']) ? esc_url($options['rfc_custom_image']) : '';
		$image_size = $options['rfc_images_size'] ?? 'medium';

		// Inicializar la URL de la imagen
		$image_url = '';

		// Prioridad 1: Plugin "z Taxonomy Image"
		if (function_exists('z_taxonomy_image_url')) {
			$plugin_image_url = z_taxonomy_image_url($cat->term_id, $image_size);
			if ($plugin_image_url) {
				$image_url = esc_url($plugin_image_url);
			}
		}
		// Prioridad 2: Tema "Wasabi"
		elseif (defined('WASABI_THEME_PATH')) {
			$wasabi_image_id = get_term_meta($cat->term_id, 'wasabi_hero_image_id', true);
			if ($wasabi_image_id) {
				$image_url = esc_url(wp_get_attachment_image_url($wasabi_image_id, $image_size));
			}
		}
		// Prioridad 3: Tema "ASAP"
		elseif (defined('ASAP_THEME_DIR')) {
			$asap_image_id = get_term_meta($cat->term_id, 'category-cover-image-id', true);
			if ($asap_image_id) {
				$image_url = esc_url(wp_get_attachment_image_url($asap_image_id, $image_size));
			}
		}


		// Retornar la URL de la imagen o la imagen por defecto si no se encuentra
		return $image_url ?: $default_image_url;
	}

	/* Callback to register block categories and ajax categories filter*/
	function blockCategoriesCallback($attributes, $content = null)
	{
		$attributes = wp_parse_args($attributes, [
			'hideEmpty' => false,
			'excludeCats' => [],
			'numberCats' => -1,
			'showOnlyParent' => false,
			'showParent' => 0,
			'showSearch' => false,
			'targetBlank' => false,
			'showImages' => false,
			'showCount' => false,
			'showDescription' => false,
			'styleGrid' => 'grid-cols-3'

		]);

		//convertir el array attributes en variables
		$hideEmpty = $attributes['hideEmpty'];
		$excludeCats = $attributes['excludeCats'];
		$numberCats = $attributes['numberCats'];
		$showOnlyParent = $attributes['showOnlyParent'];
		$showParent = $attributes['showParent'];
		$showSearch = $attributes['showSearch'];
		$targetBlank = $attributes['targetBlank'];
		$showImages = $attributes['showImages'];
		$showCount = $attributes['showCount'];
		$showDescription = $attributes['showDescription'];
		$styleGrid = $attributes['styleGrid'];

		// comprobamos que la peticion viene por ajax
		$isAjax = defined('DOING_AJAX') && DOING_AJAX;

		$categories = null;
		// si recibimos la peticion desde el filtro por ajax
		if ($isAjax) {
			// verificamos el nonce de seguridad si la peticion viene por ajax
			check_ajax_referer('ajax_nonce', 'security');

			if (isset($_POST['letter']) && !empty($_POST['letter'])) {
				$letter = sanitize_text_field($_POST['letter']);
				$hideEmpty = $_POST['hideEmpty'];
				$targetBlank = $_POST['targetBlank'];
				$showImages = $_POST['showImages'];
				$showCount = $_POST['showCount'];
				$showDescription = $_POST['showDescription'];
				$styleGrid = $_POST['styleGrid'];

			} else {
				wp_send_json_error(['message' => 'Letra no proporcionada o inválida.']);
			}

			if ($letter != '#') {

				add_filter('terms_clauses', function ($clauses, $taxonomy, $args) use ($letter) {
					global $wpdb;

					// Modificar la cláusula WHERE para filtrar nombres que empiecen con la letra
					$clauses['where'] .= $wpdb->prepare(" AND t.name LIKE %s", $letter . '%');

					return $clauses;
				}, 10, 3);
			}
			// no se de que va esto pero es la manera de hacer consulata sobre la primera letra ;)

			$categories = get_terms([
				'taxonomy' => 'category',
				'hide_empty' => $hideEmpty,
			]);
			//error_log(print_r($categories));

		} else {
			// Flujo normal del bloque sin ajax
			$args = [
				'taxonomy' => 'category',
				'hide_empty' => $hideEmpty,
				'exclude' => $excludeCats,
				'number' => ($numberCats > 0) ? $numberCats : -1,
			];
			//si solo queremos mostrar categorias padre
			if ($showOnlyParent) {
				$args['parent'] = 0;
			}
			//si solo queremos mostrar categorias hijo de un padre
			if ($showParent > 0) {
				$args['parent'] = $showParent;
			}
			$categories = get_categories($args);
		}


		if (!$categories) {

			$errorMessage = '<p style="color:red;font-weight:bold">' . esc_html__('No hay categorías disponibles.', 'rafax-cluster') . '</p>';
			// respuesta ajax
			if ($isAjax) {
				wp_send_json_error(['message' => $errorMessage]);
			}
			// respuesta flujo normal
			return $errorMessage;
		}

		$output = [];

		if ($showSearch) {
			$alphabet = range('A', 'Z');
			$output[] = '<div class="alphabet-filter">';
			$output[] = '<a href="#" data-letter="#">#</a>';
			$output[] = implode('', array_map(fn($letter) => '<a href="#" data-letter="' . $letter . '">' . $letter . '</a>', $alphabet));
			$output[] = '</div>';
		}

		$output[] = $isAjax ? '' : '<div 
		class="cluster cluster-cats ' . esc_attr($styleGrid) . ' style-4" 
		data-show-images="' . esc_attr($showImages ? 'true' : 'false') . '" 
		data-style-grid="' . esc_attr($styleGrid) . '" 
		data-show-description="' . esc_attr($showDescription ? 'true' : 'false') . '" 
		data-show-count="' . esc_attr($showCount ? 'true' : 'false') . '" 
		data-target-blank="' . esc_attr($targetBlank ? 'true' : 'false') . '"
		data-hide-empty="' . esc_attr($hideEmpty ? 'true' : 'false') . '">';

		foreach ($categories as $cat) {

			$image_url = $this->getCategoryImage($cat);

			$imageTag = $showImages && $image_url
				? sprintf('<img src="%s" alt="%s" loading="lazy"/>', $image_url, esc_attr($cat->name))
				: '';

			$description = $showDescription
				? sprintf('<div class="description"><p>%s</p></div>', esc_html($cat->description))
				: '';

			$output[] = sprintf(
				'<a %s class="post-grid-item vertical" href="%s">
                <div class="thumb">%s</div>
                <div class="content">
                    <div class="title"><h3>%s%s</h3></div>
                    %s
                </div>
            </a>',
				$targetBlank ? 'target="_blank"' : '',
				esc_url(get_category_link($cat->term_id)),
				$imageTag,
				esc_html($cat->name),
				$showCount ? sprintf(' (%d)', $cat->count) : '',
				$description
			);
		}

		$output[] = $isAjax ? '' : '</div>';

		$resultHtml = implode("\n", $output);

		if ($isAjax) {
			wp_send_json_success(['html' => $resultHtml]);

		}

		return $resultHtml;

	}

	//callback to register posts block
	function blockPostsCallback($attributes)
	{
		// Validar atributos con valores por defecto
		$defaults = [
			'orderBy' => 'date',
			'order' => 'DESC',
			'includePosts' => [],
			'excludePosts' => [],
			'numberPosts' => -1,
			'category' => 'all',
			'styleGrid' => 'default',
			'showFeaturedImage' => false,
		];
		$attributes = wp_parse_args($attributes, $defaults);

		$postId = get_the_ID();

		$content_type = isset($attributes['contentType']) ? $attributes['contentType'] : 'post';
		// Construir argumentos para WP_Query
		$args = [
			'post_type' => $content_type,
			'post_status' => 'publish',
			'orderby' => sanitize_key($attributes['orderBy']),
			'order' => sanitize_key($attributes['order']),
		];

		if (!empty($attributes['parentPage'])) {
			$args['post_parent'] = $attributes['parentPage']; // Filtrar por página padre
		}

		//var_dump($args);
		if (empty($attributes['includePosts'])) {
			$args['posts_per_page'] = $attributes['numberPosts'] > 0 ? (int) $attributes['numberPosts'] : -1;
			$args['post__not_in'] = array_merge($attributes['excludePosts'], [$postId]);

			if (!empty($attributes['category']) && $attributes['category'] !== 'all') {
				$args['cat'] = (int) $attributes['category'];
			}
		} else {
			$args['post__in'] = array_map('intval', $attributes['includePosts']);
		}

		// Aplicar filtro para extensibilidad
		$args = apply_filters('block_posts_query_args', $args, $attributes);

		// Ejecutar consulta
		$query = new WP_Query($args);

		// Verificar si hay posts
		if (!$query->have_posts()) {
			return sprintf('<p style="color:red;font-weight:bold">%s</p>', __('No hay entradas', 'rafax-cluster'));
		}

		// Construir salida HTML
		$output = [];
		$output[] = '<div class="cluster cluster-posts ' . esc_attr($attributes['styleGrid']) . ' style-4">';

		while ($query->have_posts()) {
			$query->the_post();
			$post_classes = 'post-grid-item vertical';
			$post_link = esc_url(get_the_permalink());
			$post_id = esc_attr(get_the_ID());
			$custom_title = get_post_meta($post_id, '_custom_cluster_title', true);
    		$post_title = $custom_title ? $custom_title : get_the_title(); 

			$output[] = "<a id=\"{$post_id}\" href=\"{$post_link}\" class=\"{$post_classes}\">";

			// Mostrar imagen destacada si se requiere
			if ($attributes['showFeaturedImage']) {
				$thumbnail_url = get_the_post_thumbnail_url($post_id, 'medium');
				if ($thumbnail_url) {
					$output[] = '<div class="thumb">';
					$output[] = '<img src="' . esc_url($thumbnail_url) . '" alt="' . $post_title . '" loading="lazy"/>';
					$output[] = '</div>';
				}
			}

			// Mostrar contenido del post
			$output[] = '<div class="content">';
			$output[] = '<div class="title"><h3>' . esc_html($post_title) . '</h3></div>';
			$output[] = '</div>';

			$output[] = '</a>';
		}

		// Restablecer datos globales del post
		wp_reset_postdata();

		$output[] = '</div>';

		// Unir y retornar salida
		return implode("\n", $output);
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