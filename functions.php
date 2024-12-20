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

// Slug do papel de usuário "Gestor de Museu"
const MUSEUSBR_GESTOR_DE_MUSEU_ROLE = 'tainacan-gestor-de-museu';
const MUSEUSBR_PARCEIROS_DO_IBRAM_ROLE = 'tainacan-parceiros-do-ibram';

// Novas funcionalidades criadas para o MuseusBr
const MUSEUSBR_ENABLE_REGISTRO = true;
const MUSEUSBR_ENABLE_CERTIFICADO_CADASTRO = true;

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
        'museus_collection_id' => museusbr_get_museus_collection_id()
    ) );
	wp_enqueue_style( 'line-awesome-icons', 'https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css' );
	wp_enqueue_style( 'poppins-google-font', 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;700&display=swap', false );
}
add_action( 'admin_enqueue_scripts', 'museusbr_admin_enqueue_styles' );

/**
 * Altera o link de criação de posts da coleção dos museus no menu do admin
 */
function museusbr_museus_collection_add_new_post_menu() {
	global $submenu;

	$museus_collection_id = museusbr_get_museus_collection_id();

	if ( isset($submenu['edit.php?post_type=tnc_col_' . $museus_collection_id . '_item'][10]) && isset($submenu['edit.php?post_type=tnc_col_' . $museus_collection_id . '_item'][10][2]) )
		$submenu['edit.php?post_type=tnc_col_' . $museus_collection_id . '_item'][10][2] =  admin_url( '?page=tainacan_admin#/collections/' . $museus_collection_id . '/items/new' );

}
add_filter( 'admin_menu', 'museusbr_museus_collection_add_new_post_menu', 10);

/**
 * Inclui a coleção dos museus no menu admin
 */
function museusbr_list_museus_collection_in_admin($args, $post_type){

    if ( $post_type == museusbr_get_museus_collection_post_type() ){
		$args['show_ui'] = true;
		$args['show_in_menu'] = true;
		$args['menu_icon'] = 'dashicons-bank';
		$args['menu_position'] = 3;
		$args['labels'] = array(
			'name' => 'Museus',
			'singular_name' => 'Museu',
			'add_new' => 'Cadastrar novo',
			'add_new_item' => 'Cadastrar novo Museu',
			'edit_item' => 'Editar Museu',
			'new_item' => 'Novo Museu',
			'view_item' => 'Ver Museu',
			'search_items' => 'Buscar Museus',
			'not_found' => 'Nenhum cadastro de Museu encontrado',
			'not_found_in_trash' => 'Nenhum cadastro de Museu na lixeira',
		);
    }

    return $args;
}
add_filter('register_post_type_args', 'museusbr_list_museus_collection_in_admin', 10, 2);

/**
 * Pré-preenche o metadado do Código de Itentificação do Ibram com o ID do item
 */
function museusbr_preset_codigo_id($item) {
	if ( $item instanceof \Tainacan\Entities\Item ) {
		$collection_id = $item->get_collection_id();

	 	if ( $collection_id == museusbr_get_museus_collection_id() ) {
			
			try {
				// O metadado da instituição deve vir pré-preenchido
				$codigo_metadatum = new \Tainacan\Entities\Metadatum( museusbr_get_codigo_identificador_ibram_metadatum_id() );

				if ( $codigo_metadatum instanceof \Tainacan\Entities\Metadatum ) {
					
					$new_codigo_item_metadatum = new \Tainacan\Entities\Item_Metadata_Entity( $item, $codigo_metadatum );
			
					if ( !$new_codigo_item_metadatum->has_value() ) {
						$new_codigo_item_metadatum->set_value( $item->get_id() );
			
						if ( $new_codigo_item_metadatum->validate() )
							\Tainacan\Repositories\Item_Metadata::get_instance()->insert( $new_codigo_item_metadatum );
					}

				}
			} catch (Exception $e) {
				error_log('Erro ao tentar pré-preencher o metadado do Código de Identificação do Ibram: ' . $e->getMessage());
			}
		}
	}
};
add_action('tainacan-insert', 'museusbr_preset_codigo_id', 10, 1);


/* ----------------------------- INC IMPORTS  ----------------------------- */
require get_stylesheet_directory() . '/inc/museu-single-tweaks.php';
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
