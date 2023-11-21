<?php
/**
 * Conjunto de filters e actions para o usuário tipo gestor do museu
 * 
 */


/**
 * Função para checar se o usuário atual é um gestor do museu
 */
function museusbr_user_is_gestor( $user = NULL ) {
	
	if ( !isset($user) || $user === NULL )
		$user = wp_get_current_user();

	return is_user_logged_in() && in_array( MUSEUSBR_GESTOR_DE_MUSEU_ROLE, $user->roles ? $user->roles : [] );
}

/**
 * Altera o link de edição de posts da coleção dos museus
 */
function museusbr_museus_collection_edit_post_link( $url, $post_ID) {

	if ( get_post_type($post_ID) == museusbr_get_collection_post_type() ) 
		$url = admin_url( '?page=tainacan_admin#/collections/' . museusbr_get_collection_id() . '/items/' . $post_ID . '/edit' );

    return $url;
}
add_filter( 'get_edit_post_link', 'museusbr_museus_collection_edit_post_link', 10, 2 );

/**
 * Altera o link de criação de posts da coleção dos museus na página dos museus
 */
function museusbr_museus_collection_add_new_post( $url, $path) {

	if ( str_contains($path, "post-new.php") && str_contains( $path, 'post_type=' . museusbr_get_collection_post_type() ) )
		$url = admin_url( '?page=tainacan_admin#/collections/' . museusbr_get_collection_id() . '/items/new' );
	
    return $url;
}
add_filter( 'admin_url', 'museusbr_museus_collection_add_new_post', 10, 2 );

/**
 * Redireciona o usuário após o login para a paǵina de gestão dos museus
 */
function museusbr_museus_login_redirect($redirect_url, $request, $user) {

	if ( museusbr_user_is_gestor($user) )
		return admin_url( 'edit.php?post_type=tnc_col_' . museusbr_get_collection_id() . '_item' );

	return $redirect_url;	
}	
add_filter('login_redirect', 'museusbr_museus_login_redirect', 10, 3);

/**
 * Redireciona o usuário após o login via modal para a paǵina de gestão dos museus
 */
function museusbr_museus_modal_login_redirect() {

	if ( museusbr_user_is_gestor() )
		return admin_url( 'edit.php?post_type=tnc_col_' . museusbr_get_collection_id() . '_item' );	
	
	return home_url();
}
add_filter('blocksy:account:modal:login:redirect_to', 'museusbr_museus_modal_login_redirect');

/** 
 * Remove links desnecessários do menu admin
 */
function museusbr_museus_menu_page_removing() {
    if ( museusbr_user_is_gestor() ) {
        remove_menu_page( 'tainacan_admin' );
        remove_menu_page( 'upload.php' );
	}
}
add_action( 'admin_menu', 'museusbr_museus_menu_page_removing' );

/**
 * Lista somente os museus do usuário atual, se ele for gestor
 */
function museusbr_pre_get_post( $query ) {
    if ( !is_admin() )
        return;

    if ( $query->is_main_query() && $query->query_vars['post_type'] == museusbr_get_collection_post_type() ) {
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

	if ( $pagenow == 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === museusbr_get_collection_post_type() )
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
		$options['hideItemEditionCommentsToggle'] = true;
		$options['hideItemEditionCollapses'] = true;
		$options['hideItemEditionMetadataTypes'] = true;
		$options['hideItemSingleExposers'] = true;
		$options['hideItemSingleActivities'] = true;
	}
	return $options;
};
add_filter('tainacan-admin-ui-options', 'museusbr_set_tainacan_admin_options');