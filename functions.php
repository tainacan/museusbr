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

// Taxonomia de Estado do Museu que também é referência aos parceiros do IBRAM
const MUSEUSBR_ESTADO_DO_MUSEU_TAXONOMY_ID = 1056;

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


/* ----------------------------- GENERAL INC IMPORTS  ----------------------------- */
require get_stylesheet_directory() . '/inc/enqueues.php';
require get_stylesheet_directory() . '/inc/customizer.php';
require get_stylesheet_directory() . '/inc/gestor-tweaks.php';
require get_stylesheet_directory() . '/inc/singleton.php';
require get_stylesheet_directory() . '/inc/block-styles.php';
require get_stylesheet_directory() . '/inc/login-form-tweaks.php';
require get_stylesheet_directory() . '/inc/metabase.php';

/* ------------------------------ MUSEUS ----------------------------------------- */
require get_stylesheet_directory() . '/inc/museus/museu-tweaks.php';
require get_stylesheet_directory() . '/inc/museus/museu-single-tweaks.php';
require get_stylesheet_directory() . '/inc/museus/museu-admin.php';
require get_stylesheet_directory() . '/inc/museus/metadata-section-icon-hook.php';
require get_stylesheet_directory() . '/inc/museus/certificado.php';

/* ------------------------------ REGISTRO --------------------------------------- */
require get_stylesheet_directory() . '/inc/registro/registro-post-type.php';
require get_stylesheet_directory() . '/inc/registro/registro.php';
require get_stylesheet_directory() . '/inc/registro/certificado-registro.php';

/* ------------------------------ FORMULÁRIO DE VISITACAO ANUAL ------------------ */
require get_stylesheet_directory() . '/inc/fva/formulario-de-visitacao-tweaks.php';
