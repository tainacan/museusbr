<?php
/**
 * Funções do tema MuseusBR
 */

if (! defined('WP_DEBUG')) {
	die( 'Direct access forbidden.' );
}

const MUSEUSBR_MUSEUS_COLLECTION_ID = 208;//267;
const MUSEUSBR_GESTOR_DE_MUSEU_ROLE = 'tainacan-gestor-de-museu';

function museusbr_get_collection_post_type() {
	return 'tnc_col_' . get_theme_mod( 'museusbr_collection', MUSEUSBR_MUSEUS_COLLECTION_ID ) . '_item';
}
function museusbr_get_collection_id() {
	return get_theme_mod( 'museusbr_collection', MUSEUSBR_MUSEUS_COLLECTION_ID );
}

/**
 * Registra Scripts e Estilos
 */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
	wp_enqueue_style( 'museusbr-style', get_stylesheet_uri() );
	wp_enqueue_style( 'line-awesome-icons', 'https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css' );
	
	if ( is_singular( museusbr_get_collection_post_type() ) ) {
		wp_enqueue_style( 'museusbr-single-style', get_stylesheet_directory_uri() . '/assets/css/museu.css', array(), '0.1.0' );
		wp_enqueue_script( 'museusbr-single-script', get_stylesheet_directory_uri() . '/assets/js/museu.js', array(), '0.1.0', true );
	}
});

/** 
 * Registra estilo do lado admin
 */
add_action( 'admin_enqueue_scripts', function () {
	wp_enqueue_style( 'museusbr-admin-style', get_stylesheet_directory_uri() . '/admin.css' );
	wp_enqueue_style( 'line-awesome-icons', 'https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css' );
});

/** 
 * Opções extras do customizer
 */
require get_stylesheet_directory() . '/inc/customizer.php';

/** 
 * Alterações relativas ao usuário que é gestor do museu
 */
require get_stylesheet_directory() . '/inc/gestor-tweaks.php';

/**
 * Altera o link de criação de posts da coleção dos museus no menu do admin
 */
function museusbr_museus_collection_add_new_post_menu() {

	global $submenu;

	$museus_collection_id = museusbr_get_collection_id();

	if ( isset($submenu['edit.php?post_type=tnc_col_' . $museus_collection_id . '_item'][10]) && isset($submenu['edit.php?post_type=tnc_col_' . $museus_collection_id . '_item'][10][2]) )
		$submenu['edit.php?post_type=tnc_col_' . $museus_collection_id . '_item'][10][2] =  admin_url( '?page=tainacan_admin#/collections/' . $museus_collection_id . '/items/new' );

}
add_filter( 'admin_menu', 'museusbr_museus_collection_add_new_post_menu', 10);

/**
 * Inclui a coleção dos museus no menu admin
 */
function museusbr_list_museus_collection_in_admin($args, $post_type){

    if ( $post_type == museusbr_get_collection_post_type() ){
		$args['show_ui'] = true;
		$args['show_in_menu'] = true;
		$args['menu_icon'] = 'dashicons-bank';
		$args['menu_position'] = 3;
    }

    return $args;
}
add_filter('register_post_type_args', 'museusbr_list_museus_collection_in_admin', 10, 2);

/**
 * Adiciona Thumbnail do item na página do museu
 */
function museusbr_museu_single_page_hero_description_before() {

	if ( get_post_type() == museusbr_get_collection_post_type() ) {
    	the_post_thumbnail('tainacan-medium', array('class' => 'museu-item-thumbnail'));
		
		$item = tainacan_get_item();

		if ($item instanceof \Tainacan\Entities\Item) {

			add_filter( 'tainacan-get-item-metadatum-as-html-before-value', function($metadatum_value_before, $item_metadatum) {

				$metadatum_id = $item_metadatum->get_metadatum()->get_id();

				// Metadado do Site do Museu
				if ( $metadatum_id == 1200 )
					$metadatum_value_before .= '<i class="las la-link"></i>';

				// Metadados de Email
				if ( $metadatum_id == 1213 || $metadatum_id == 1216 )
					$metadatum_value_before .= '<i class="las la-envelope"></i>';

				// Metadados de Telefone
				if ( $metadatum_id == 1219 || $metadatum_id == 1222 )
					$metadatum_value_before .= '<i class="las la-phone"></i>';
				
				// Metadado de Contato Extra
				if ( $metadatum_id == 14629 )
					$metadatum_value_before .= '<i class="las la-id-card"></i>';
			
				return $metadatum_value_before;
			}, 10, 2 );

			echo '<div class="museu-item-other-metadata">';

				$sections_args = array(
					'metadata_section' => 'default_section',
					'hide_name'	=> true,
					'before' => '',
					'after' => '',
					'metadata_list_args' => array(
						'exclude_core' => true,
						'display_slug_as_class' => true
					)
				);

				tainacan_the_metadata_sections($sections_args);

			echo '</div>';
			echo '<div class="museu-item-description">' . $item->get_description() . '</div>';
		}

	}
}
add_action('blocksy:hero:description:before', 'museusbr_museu_single_page_hero_description_before');

/**
 * Adiciona navegação entre seções no cabeçalho
 */
function museusbr_museu_single_page_hero_custom_meta_after() {

	if ( get_post_type() == museusbr_get_collection_post_type() ) {
		?>
			<nav class="museu-item-sections-navigator">
				<ol>
					<li>
						<a href="#tainacan-item-metadata-label">
							<span class="navigator-icon">
								<i class="las la-info-circle"></i>
							</span>
							<span class="navigator-text"><?php _e( 'Informações', 'museusbr'); ?></span>
						</a>
					</li>
					<li id="tainacan-item-documents-label-nav">
						<a href="#tainacan-item-documents-label">
							<span class="navigator-icon">
								<i class="las la-image"></i>
							</span>
							<span class="navigator-text"><?php _e( 'Galeria de Fotos', 'museusbr'); ?></span>
						</a>
					</li>
					<li>
						<a href="#metadata-section-localizacao">
							<span class="navigator-icon">
								<i class="las la-map"></i>
							</span>
							<span class="navigator-text"><?php _e( 'Localização', 'museusbr'); ?></span>
						</a>
					</li>
				</ol>
			</nav>
		<?php
	}
}
add_action('blocksy:hero:custom_meta:after', 'museusbr_museu_single_page_hero_custom_meta_after');

/**
 * Sobrescreve o conteúdo da single do museu
 */
function museusbr_museu_single_page_content( $content ) {

	if ( ! is_singular( museusbr_get_collection_post_type() ) )
		return $content;
	
	ob_start();
	include( 'tainacan/museu-single-page.php' );
	$new_content = ob_get_contents();
	ob_end_clean();

	return $new_content;
}
add_filter( 'the_content', 'museusbr_museu_single_page_content', 12, 1);


/* ----------------------------- INC IMPORTS  ----------------------------- */
require get_stylesheet_directory() . '/inc/singleton.php';
require get_stylesheet_directory() . '/inc/metadata-section-icon-hook.php';