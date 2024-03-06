<?php
/**
 * Funções do tema MuseusBR
 */

if (! defined('WP_DEBUG')) {
	die( 'Direct access forbidden.' );
}

// Valor padrão para a coleção de Museus. Será definido no customizer.
const MUSEUSBR_MUSEUS_COLLECTION_ID = 208;//267;

// Slug do papel de usuário "Gestor de Museu"
const MUSEUSBR_GESTOR_DE_MUSEU_ROLE = 'tainacan-gestor-de-museu';

/**
 * Função utilitaria para obter o id da coleção Museus
 */
function museusbr_get_collection_id() {
	return get_theme_mod( 'museusbr_collection', MUSEUSBR_MUSEUS_COLLECTION_ID );
}

/**
 * Função utilitaria para obter o tipo de post da coleção Museus
 */
function museusbr_get_collection_post_type() {
	return 'tnc_col_' . museusbr_get_collection_id() . '_item';
}

/**
 * Registra Scripts e Estilos
 */
function museusbr_register_scripts_and_styles() {
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
	wp_enqueue_style( 'museusbr-style', get_stylesheet_uri() );
	wp_enqueue_style( 'line-awesome-icons', 'https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css' );
	wp_enqueue_script( 'museusbr-archive-script', get_stylesheet_directory_uri() . '/assets/js/museus.js', array(), wp_get_theme()->get('Version'), true );

	if ( is_singular( museusbr_get_collection_post_type() ) ) {
		wp_enqueue_style( 'museusbr-single-style', get_stylesheet_directory_uri() . '/assets/css/museu.css', array(), wp_get_theme()->get('Version') );
		wp_enqueue_script( 'museusbr-single-script', get_stylesheet_directory_uri() . '/assets/js/museu.js', array(), wp_get_theme()->get('Version'), true );
	}
}
add_action( 'wp_enqueue_scripts', 'museusbr_register_scripts_and_styles' );

/** 
 * Registra estilo do lado admin
 */
function museusbr_admin_enqueue_styles() {
	wp_enqueue_style( 'museusbr-admin-style', get_stylesheet_directory_uri() . '/admin.css' );
	wp_enqueue_style( 'line-awesome-icons', 'https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css' );
}
add_action( 'admin_enqueue_scripts', 'museusbr_admin_enqueue_styles' );

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

/* ----------------------------- INC IMPORTS  ----------------------------- */
require get_stylesheet_directory() . '/inc/museu-single-tweaks.php';
require get_stylesheet_directory() . '/inc/customizer.php';
require get_stylesheet_directory() . '/inc/gestor-tweaks.php';
require get_stylesheet_directory() . '/inc/singleton.php';
require get_stylesheet_directory() . '/inc/metadata-section-icon-hook.php';
require get_stylesheet_directory() . '/inc/block-styles.php';
require get_stylesheet_directory() . '/inc/login-form-tweaks.php';
require get_stylesheet_directory() . '/inc/metabase.php';
