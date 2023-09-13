<?php
/**
 * Funções do tema MuseusBR
 */

if (! defined('WP_DEBUG')) {
	die( 'Direct access forbidden.' );
}

const MUSEUSBR_MUSEUS_COLLECTION_ID = 208;//267;
const MUSEUSBR_MUSEUS_COLLECTION_POST_TYPE = 'tnc_col_' . MUSEUSBR_MUSEUS_COLLECTION_ID . '_item';
const MUSEUSBR_GESTOR_DE_MUSEU_ROLE = 'tainacan-gestor-de-museu';

/**
 * Registra Scripts e Estilos
 */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
	wp_enqueue_style( 'museusbr-style', get_stylesheet_uri() );
});

/** 
 * Registra estilo do lado admin
 */
add_action( 'admin_enqueue_scripts', function () {
	wp_enqueue_style( 'museusbr-admin-style', get_stylesheet_directory_uri() . '/admin.css' );
});

/**
 * Função para checar se o usuário atual é um gestor do museu
 */
function museusbr_user_is_gestor( $user = NULL ) {
	
	if ( !isset($user) || $user === NULL )
		$user = wp_get_current_user();

	return is_user_logged_in() && in_array( MUSEUSBR_GESTOR_DE_MUSEU_ROLE, $user->roles );
}

/**
 * Inclui a coleção dos museus no menu admin
 */
function museusbr_list_museus_collection_in_admin($args, $post_type){

    if ( $post_type == MUSEUSBR_MUSEUS_COLLECTION_POST_TYPE ){
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

	if ( get_post_type($post_ID) == MUSEUSBR_MUSEUS_COLLECTION_POST_TYPE ) 
		$url = admin_url( '?page=tainacan_admin#/collections/' . MUSEUSBR_MUSEUS_COLLECTION_ID . '/items/' . $post_ID . '/edit' );

    return $url;
}
add_filter( 'get_edit_post_link', 'museusbr_museus_collection_edit_post_link', 10, 2 );

/**
 * Altera o link de criação de posts da coleção dos museus na página dos museus
 */
function museusbr_museus_collection_add_new_post( $url, $path) {

	if ( str_contains($path, "post-new.php") && str_contains( $path, 'post_type=tnc_col_' . MUSEUSBR_MUSEUS_COLLECTION_ID . '_item' ) )
		$url = admin_url( '?page=tainacan_admin#/collections/' . MUSEUSBR_MUSEUS_COLLECTION_ID . '/items/new' );
	
    return $url;
}
add_filter( 'admin_url', 'museusbr_museus_collection_add_new_post', 10, 2 );

/**
 * Altera o link de criação de posts da coleção dos museus no menu do admin
 */
function museusbr_museus_collection_add_new_post_menu() {

	global $submenu;

	if ( isset($submenu['edit.php?post_type=tnc_col_' . MUSEUSBR_MUSEUS_COLLECTION_ID . '_item'][10]) && isset($submenu['edit.php?post_type=tnc_col_' . MUSEUSBR_MUSEUS_COLLECTION_ID . '_item'][10][2]) )
		$submenu['edit.php?post_type=tnc_col_' . MUSEUSBR_MUSEUS_COLLECTION_ID . '_item'][10][2] =  admin_url( '?page=tainacan_admin#/collections/' . MUSEUSBR_MUSEUS_COLLECTION_ID . '/items/new' );

}
add_filter( 'admin_menu', 'museusbr_museus_collection_add_new_post_menu', 10);

/**
 * Redireciona o usuário após o login para a paǵina de gestão dos museus
 */
function museusbr_museus_login_redirect($redirect_url, $request, $user) {

	if ( museusbr_user_is_gestor($user) )
		return admin_url( 'edit.php?post_type=tnc_col_' . MUSEUSBR_MUSEUS_COLLECTION_ID . '_item' );

	return $redirect_url;	
}	
add_filter('login_redirect', 'museusbr_museus_login_redirect', 10, 3);

/**
 * Redireciona o usuário após o login via modal para a paǵina de gestão dos museus
 */
function museusbr_museus_modal_login_redirect() {

	//if ( museusbr_user_is_gestor() )
		return admin_url( 'edit.php?post_type=tnc_col_' . MUSEUSBR_MUSEUS_COLLECTION_ID . '_item' );	
	
	//return home_url();
}
add_filter('blocksy:account:modal:login:redirect_to', 'museusbr_museus_modal_login_redirect');

/**
 * Adiciona Thumbnail do item na página do museu
 */
function museusbr_museu_single_page_hero_description_before() {

	if ( get_post_type() == MUSEUSBR_MUSEUS_COLLECTION_POST_TYPE ) {
    	the_post_thumbnail('tainacan-medium', array('class' => 'museu-item-thumbnail'));
		
		$item = tainacan_get_item();

		if ($item instanceof \Tainacan\Entities\Item)
			echo '<div class="museu-item-description">' . $item->get_description() . '</div>';

	}
}
add_action('blocksy:hero:description:before', 'museusbr_museu_single_page_hero_description_before');


/**
 * Sobrescreve o conteúdo da single do museu
 */
function museusbr_museu_single_page_content( $content ) {

	if ( ! is_singular( MUSEUSBR_MUSEUS_COLLECTION_POST_TYPE ) )
		return $content;
	
	ob_start();
	include( 'tainacan/museu-single-page.php' );
	$new_content = ob_get_contents();
	ob_end_clean();

	return $new_content;
}
add_filter( 'the_content', 'museusbr_museu_single_page_content', 12, 1);


/**
 * Lista somente os museus do usuário atual, se ele for gestor
 */
function museusbr_pre_get_post( $query ) {
    if ( !is_admin() )
        return;

    if ( $query->is_main_query() && $query->query_vars['post_type'] == MUSEUSBR_MUSEUS_COLLECTION_POST_TYPE ) {
        if ( museusbr_user_is_gestor() )
            $query->query_vars['author'] = get_current_user_id();
    }
}
add_action( 'pre_get_posts', 'museusbr_pre_get_post' );

/**
 * Adiciona classe css ao Admin do WordPress para estilizar a página que lista os museus
 */
function museusbr_custom_body_class($classes) {
	global $pagenow;

	if ( $pagenow == 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === MUSEUSBR_MUSEUS_COLLECTION_POST_TYPE )
        $classes .= ' post-type-museusbr-museus';

	if ( museusbr_user_is_gestor() )
		$classes .= ' user-is-gestor-do-museu';

    return $classes;
}
add_filter('admin_body_class', 'museusbr_custom_body_class');


/*
 * Adiciona parâmetros para o Admin Tainacan para esconder elementos que não são necessários
 */
function museusbr_set_tainacan_admin_options($options) {
	
	if ( museusbr_user_is_gestor() ) {
		$options['hideTainacanHeader'] = true;
		$options['hidePrimaryMenu'] = true;
		$options['hideRepositorySubheader'] = true;
		$options['hideCollectionSubheader'] = true;
		$options['hideItemEditionCollectionName'] = true;
		$options['hideItemEditionDocumentTextInput'] = true;
		$options['hideItemEditionDocumentUrlInput'] = true;
		$options['hideItemEditionCommentsToggle'] = true;
		$options['hideItemEditionCollapses'] = true;
		$options['hideItemEditionFocusMode'] = true;
		$options['hideItemEditionRequiredOnlySwitch'] = true;
		$options['hideItemEditionMetadataTypes'] = true;
	}
	return $options;
};
add_filter('tainacan-admin-ui-options', 'museusbr_set_tainacan_admin_options');