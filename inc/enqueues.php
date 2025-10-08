<?php

/**
 * Registra Scripts e Estilos
 */
function museusbr_register_scripts_and_styles() {
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
	wp_enqueue_style( 'museusbr-style', get_stylesheet_uri() );
	wp_enqueue_style( 'line-awesome-icons', 'https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css' );
	wp_enqueue_script( 'museusbr-archive-script', get_stylesheet_directory_uri() . '/assets/js/museus.js', array(), wp_get_theme()->get('Version'), true );

	if ( is_singular( museusbr_get_museus_collection_post_type() ) || is_singular( museusbr_get_pontos_de_memoria_collection_post_type() ) ) {
		wp_enqueue_style( 'museusbr-single-style', get_stylesheet_directory_uri() . '/assets/css/museu.css', array(), wp_get_theme()->get('Version') );
		wp_enqueue_script( 'museusbr-single-script', get_stylesheet_directory_uri() . '/assets/js/museu.js', array(), wp_get_theme()->get('Version'), true );
	}
}
add_action( 'wp_enqueue_scripts', 'museusbr_register_scripts_and_styles' );

/** 
 * Registra estilo do lado admin
 */
function museusbr_admin_enqueue_styles() {
	wp_enqueue_style( 'museusbr-admin-style', get_stylesheet_directory_uri() . '/assets/css/admin.css' );
	wp_enqueue_script( 'museusbr-admin-script', get_stylesheet_directory_uri() . '/assets/js/admin.js', array('wp-hooks'), wp_get_theme()->get('Version') );
	wp_localize_script( 'museusbr-admin-script', 'museusbr_theme', array(
        'museus_collection_id' => museusbr_get_museus_collection_id(),
		'current_user_is_gestor' => museusbr_user_is_gestor(),
		'museus_admin_page_url' => admin_url( 'admin.php?page=museu'),
    ) );
	wp_enqueue_style( 'line-awesome-icons', 'https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css' );
	wp_enqueue_style( 'poppins-google-font', 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;700&display=swap', false );
}
add_action( 'admin_enqueue_scripts', 'museusbr_admin_enqueue_styles' );

/**
 * Registra pequeno estilo para o menu Personalizar do WordPress
 */
function museusbr_custom_customize_enqueue() {
	wp_enqueue_style( 'museusbr-customizer-style', get_stylesheet_directory_uri() . '/assets/css/customizer.css', array(), wp_get_theme()->get('Version') );
}
add_action( 'customize_controls_enqueue_scripts', 'museusbr_custom_customize_enqueue' );