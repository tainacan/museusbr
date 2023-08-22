<?php
/**
 * Funções do tema MuseusBR
 */

if (! defined('WP_DEBUG')) {
	die( 'Direct access forbidden.' );
}

const MUSEUSBR_MUSEUS_COLLECTION_ID = 208;//267;

/**
 * Registra Scripts e Styles
 */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
	wp_enqueue_style('museusbr-style', get_stylesheet_uri());
});

/**
 * Inclui a coleção dos museus no menu admin
 */
function museusbr_list_museus_collection_in_admin($args, $post_type){

    if ( $post_type == 'tnc_col_' . MUSEUSBR_MUSEUS_COLLECTION_ID . '_item' ){
		$args['show_ui'] = true;
		$args['show_in_menu'] = true;
		$args['menu_icon'] = 'dashicons-bank';
		$args['menu_position'] = 3;
    }

    return $args;
}
add_filter('register_post_type_args', 'museusbr_list_museus_collection_in_admin', 10, 2);

/**
 * Altera o link de edição de posts da coleção dos museus
 */
function museusbr_museus_collection_edit_post_link( $url, $post_ID) {

	if ( get_post_type($post_ID) == 'tnc_col_' . MUSEUSBR_MUSEUS_COLLECTION_ID . '_item' ) 
		$url = admin_url( '?page=tainacan_admin#/collections/' . MUSEUSBR_MUSEUS_COLLECTION_ID . '/items/' . $post_ID . '/edit' );

    return $url;
}
add_filter( 'get_edit_post_link', 'museusbr_museus_collection_edit_post_link', 10, 2 );

/**
 * Altera o link de criação de posts da coleção dos museus na página dos museus
 */
function museusbr_museus_collection_add_new_post( $url, $path) {

	if ( str_contains($path, "post-new.php") && str_contains( $path, 'post_type=tnc_col_' . MUSEUSBR_MUSEUS_COLLECTION_ID . '_item' ) )
		$url = admin_url( '?page=tainacan_admin#/collections/' . MUSEUSBR_MUSEUS_COLLECTION_ID . '/new' );
	
    return $url;
}
add_filter( 'admin_url', 'museusbr_museus_collection_add_new_post', 10, 2 );

/**
 * Altera o link de criação de posts da coleção dos museus no menu do admin
 */
function museusbr_museus_collection_add_new_post_menu() {

	global $submenu;

	if ( isset($submenu['edit.php?post_type=tnc_col_' . MUSEUSBR_MUSEUS_COLLECTION_ID . '_item'][10]) && isset($submenu['edit.php?post_type=tnc_col_' . MUSEUSBR_MUSEUS_COLLECTION_ID . '_item'][10][2]) )
		$submenu['edit.php?post_type=tnc_col_' . MUSEUSBR_MUSEUS_COLLECTION_ID . '_item'][10][2] =  admin_url( '?page=tainacan_admin#/collections/' . MUSEUSBR_MUSEUS_COLLECTION_ID . '/new' );

}
add_filter( 'admin_menu', 'museusbr_museus_collection_add_new_post_menu', 10);

/**
 * Redireciona o usuário após o login para a paǵina de gestão dos museus
 */
function museusbr_museus_login_redirect() {
	return admin_url( 'edit.php?post_type=tnc_col_' . MUSEUSBR_MUSEUS_COLLECTION_ID . '_item' );	
}	
add_filter('login_redirect', 'museusbr_museus_login_redirect');

/**
 * Redireciona o usuário após o login via modal para a paǵina de gestão dos museus
 */
function museusbr_museus_modal_login_redirect() {
	return admin_url( 'edit.php?post_type=tnc_col_' . MUSEUSBR_MUSEUS_COLLECTION_ID . '_item' );	
}
add_filter('blocksy:account:modal:login:redirect_to', 'museusbr_museus_modal_login_redirect');

/**
 * Adiciona Thumbnail do item na página do museu
 */
function museusbr_museu_single_page_hero_description_before() {
	if ( get_post_type() == 'tnc_col_' . MUSEUSBR_MUSEUS_COLLECTION_ID . '_item' ) 
    	the_post_thumbnail('tainacan-medium', array('class' => 'museu-item-thumbnail'));
}
add_action('blocksy:hero:description:before', 'museusbr_museu_single_page_hero_description_before');


/**
 * Sobrescreve o conteúdo da single do museu
 */
function museusbr_uai( $content ) {

	if ( ! is_singular( 'tnc_col_' . MUSEUSBR_MUSEUS_COLLECTION_ID . '_item' ) )
		return $content;
	
	ob_start();
	include( 'tainacan/museu-single-page.php' );
	$new_content = ob_get_contents();
	ob_end_clean();

	return $new_content;
}
add_filter( 'the_content', 'museusbr_uai', 12, 1);