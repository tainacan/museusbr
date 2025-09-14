<?php
/**
 * Funções do tema MuseusBr
 */

if (! defined('WP_DEBUG')) {
	die( 'Direct access forbidden.' );
}

// Valor padrão para a coleção de Museus. Será definido no customizer.
const MUSEUSBR_MUSEUS_COLLECTION_ID = 208; //267;
const MUSEUSBR_PONTOS_DE_MEMORIA_COLLECTION_ID = 179824;
const MUSEUSBR_FORMULARIOS_DE_VISITACAO_COLLECTION_ID = 219857;

// Metadados especiais
const MUSEUSBR_CODIGO_IDENTIFICADOR_IBRAM_METADATUM_ID = 15171;
const MUSEUSBR_FORMULARIO_DE_VISITACAO_TITULO_METADATUM_ID = 219860;
const MUSEUSBR_FORMULARIO_DE_VISITACAO_MUSEU_METADATUM_ID = 234544;
const MUSEUSBR_FORMULARIO_DE_VISITACAO_ANO_METADATUM_ID = 235999;

// Slug do papel de usuário "Gestor de Museu"
const MUSEUSBR_GESTOR_DE_MUSEU_ROLE = 'tainacan-gestor-de-museu';
const MUSEUSBR_PARCEIROS_DO_IBRAM_ROLE = 'tainacan-parceiros-do-ibram';

// Novas funcionalidades criadas para o MuseusBr
const MUSEUSBR_ENABLE_REGISTRO = true;
const MUSEUSBR_ENABLE_CERTIFICADO_CADASTRO = true;
const MUSEUSBR_ENABLE_CERTIFICADO_REGISTRO = true;
const MUSEUSBR_ENABLE_MUSEU_ADMIN_PAGE = true;
const MUSEUSBR_ENABLE_FORMULARIO_VISITACAO = true;

/**
 * Função utilitaria para obter o id da coleção Museus
 */
function museusbr_get_museus_collection_id() {
	return get_theme_mod( 'museusbr_collection', MUSEUSBR_MUSEUS_COLLECTION_ID );
}

/**
 * Função utilitaria para obter o id da coleção dos Pontos de Memória
 */
function museusbr_get_pontos_de_memoria_collection_id() {
	return get_theme_mod( 'museusbr_pontos_de_memoria_collection', MUSEUSBR_PONTOS_DE_MEMORIA_COLLECTION_ID );
}

/**
 * Função utilitária para obter o id da coleção dos Formulários de Visitação
 */
function museusbr_get_formularios_de_visitacao_collection_id() {
	return get_theme_mod( 'museusbr_formularios_de_visitacao_collection', MUSEUSBR_FORMULARIOS_DE_VISITACAO_COLLECTION_ID );
}

/**
 * Função utilitaria para obter o tipo de post da coleção Museus
 */
function museusbr_get_museus_collection_post_type() {
	return 'tnc_col_' . museusbr_get_museus_collection_id() . '_item';
}

/**
 * Função utilitaria para obter o tipo de post da coleção dos Pontos de Memória
 */
function museusbr_get_pontos_de_memoria_collection_post_type() {
	return 'tnc_col_' . museusbr_get_pontos_de_memoria_collection_id() . '_item';
}

/**
 * Função utilitaria para obter o tipo de post da coleção dos Formulários de Visitação
 */
function museusbr_get_formularios_de_visitacao_collection_post_type() {
	return 'tnc_col_' . museusbr_get_formularios_de_visitacao_collection_id() . '_item';
}

/**
 * Função utilitária para obter o id do metadado do código identificador da coleção dos Museus
 */
function museusbr_get_codigo_identificador_ibram_metadatum_id() {
	return get_theme_mod( 'museusbr_codigo_identificador_ibram_metadatum', MUSEUSBR_CODIGO_IDENTIFICADOR_IBRAM_METADATUM_ID );
}

/**
 * Função utilitária para obter o id do metadado de título da coleção do Formulário de Visitação
 */
function museusbr_get_formulario_de_visitacao_titulo_metadatum_id() {
	return get_theme_mod( 'museusbr_formulario_de_visitacao_titulo_metadatum', MUSEUSBR_FORMULARIO_DE_VISITACAO_TITULO_METADATUM_ID );
}

/**
 * Função utilitária para obter o id do metadado do museu na coleção do Formulário de Visitação
 */
function museusbr_get_formulario_de_visitacao_museu_metadatum_id() {
	return get_theme_mod( 'museusbr_formulario_de_visitacao_museu_metadatum', MUSEUSBR_FORMULARIO_DE_VISITACAO_MUSEU_METADATUM_ID );
}

/**
 * Função utilitária para obter o id do metadado de ano da coleção do Formulário de Visitação
 */
function museusbr_get_formulario_de_visitacao_ano_metadatum_id() {
	return get_theme_mod( 'museusbr_formulario_de_visitacao_ano_metadatum', MUSEUSBR_FORMULARIO_DE_VISITACAO_ANO_METADATUM_ID );
}

/**
 * Função utilitária para obter o valor do ano atual do Formulário de Visitação
 */
function museusbr_get_formulario_de_visitacao_ano_atual() {
	return get_theme_mod( 'museusbr_formulario_de_visitacao_ano_atual', 'none' );
}

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

/* ----------------------------- INC IMPORTS  ----------------------------- */
require get_stylesheet_directory() . '/inc/museu-tweaks.php';
require get_stylesheet_directory() . '/inc/museu-single-tweaks.php';
require get_stylesheet_directory() . '/inc/museu-admin.php';
require get_stylesheet_directory() . '/inc/customizer.php';
require get_stylesheet_directory() . '/inc/gestor-tweaks.php';
require get_stylesheet_directory() . '/inc/singleton.php';
require get_stylesheet_directory() . '/inc/metadata-section-icon-hook.php';
require get_stylesheet_directory() . '/inc/block-styles.php';
require get_stylesheet_directory() . '/inc/login-form-tweaks.php';
require get_stylesheet_directory() . '/inc/metabase.php';
require get_stylesheet_directory() . '/inc/certificado.php';
require get_stylesheet_directory() . '/inc/registro-post-type.php';
require get_stylesheet_directory() . '/inc/registro.php';
require get_stylesheet_directory() . '/inc/certificado-registro.php';
require get_stylesheet_directory() . '/inc/formulario-de-visitacao-tweaks.php';
