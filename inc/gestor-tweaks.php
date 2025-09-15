<?php
/**
 * Conjunto de filters e actions para o usuário tipo gestor do museu
 * 
 */


/**
 * Função para checar se o usuário atual é um gestor do museu ou parceiro do IBRAM
 * Usada para se checar e esconder elementos ou mudar comportamentos.
 */
function museusbr_user_is_gestor_or_parceiro( $user = NULL ) {
	
	if ( !is_user_logged_in() )
			return false;

	if ( !isset($user) || $user === NULL )
		$user = wp_get_current_user();
	
	return is_user_logged_in() && (
		in_array( MUSEUSBR_GESTOR_DE_MUSEU_ROLE, $user->roles ? $user->roles : [] ) ||
		in_array( MUSEUSBR_PARCEIROS_DO_IBRAM_ROLE, $user->roles ? $user->roles : [] )
	);
}

/**
 * Função para checar se o usuário atual é um parceiro do IBRAM
 */
function museusbr_user_is_parceiro( $user = NULL ) {
	
	if ( !is_user_logged_in() )
			return false;

	if ( !isset($user) || $user === NULL )
		$user = wp_get_current_user();
	
	return is_user_logged_in() && in_array( MUSEUSBR_PARCEIROS_DO_IBRAM_ROLE, $user->roles ? $user->roles : [] );
}

/**
 * Função para checar se o usuário atual é um gestor do museu
 */
function museusbr_user_is_gestor( $user = NULL ) {
	
	if ( !is_user_logged_in() )
			return false;

	if ( !isset($user) || $user === NULL )
		$user = wp_get_current_user();
	
	return is_user_logged_in() && in_array( MUSEUSBR_GESTOR_DE_MUSEU_ROLE, $user->roles ? $user->roles : [] );
}

/**
 * Altera o link de criação de posts da coleção dos museus na página dos museus
 */
function museusbr_museus_collection_add_new_post( $url, $path) {

	if ( str_contains($path, "post-new.php") && str_contains( $path, 'post_type=' . museusbr_get_museus_collection_post_type() ) )
		$url = admin_url( 'admin.php?page=tainacan_admin#/collections/' . museusbr_get_museus_collection_id() . '/items/new' );
	
    return $url;
}
add_filter( 'admin_url', 'museusbr_museus_collection_add_new_post', 10, 2 );

/**
 * Redireciona o usuário após o login para a paǵina de gestão dos museus
 */
function museusbr_museus_login_redirect($redirect_url, $request, $user) {

	if ( museusbr_user_is_gestor_or_parceiro($user) ) {

		// Se o usuário possui apenas um item na coleção de museus, redireciona para a página de admin do museu
		$items = get_posts( array(
			'post_type' => museusbr_get_museus_collection_post_type(),
			'author' => $user->ID,
		) );
		
		if ( count($items) == 1 )
			return admin_url( 'admin.php?page=museu&post=' . $items[0]->ID );

		return admin_url( 'edit.php?post_type=tnc_col_' . museusbr_get_museus_collection_id() . '_item' );
	}

	return $redirect_url;	
}	
add_filter('login_redirect', 'museusbr_museus_login_redirect', 10, 3);

/**
 * Redireciona o usuário após o login via modal para a paǵina de gestão dos museus
 */
function museusbr_museus_modal_login_redirect() {

	return admin_url( 'edit.php?post_type=tnc_col_' . museusbr_get_museus_collection_id() . '_item' );	
}
add_filter('blocksy:account:modal:login:redirect_to', 'museusbr_museus_modal_login_redirect');

/** 
 * Remove links desnecessários do menu admin
 */
function museusbr_museus_menu_page_removing() {
    if ( museusbr_user_is_gestor_or_parceiro() ) {
        remove_menu_page( 'tainacan_admin' );
        remove_menu_page( 'upload.php' );
	}
}
add_action( 'admin_menu', 'museusbr_museus_menu_page_removing' );

/**
 * Lista somente os museus do usuário atual, se ele for gestor
 */
function museusbr_pre_get_post( $query ) {
    if ( !is_admin() || !$query->is_main_query() )
        return;

    if ( $query->query_vars['post_type'] == museusbr_get_museus_collection_post_type() || $query->query_vars['post_type'] == 'registro' ) {
        if ( museusbr_user_is_gestor() ) {
			$query->set( 'author', get_current_user_id() );
		}else if ( museusbr_user_is_parceiro() ) {

			// Descobre qual o estado do usuário parceiro atual
			$user_id = get_current_user_id();

			if ( $user_id <= 0 ) {
				$query->set( 'author', get_current_user_id() );
				return; 
			}

			// Obtem a informação do estado do usuário, que está guardado com o user meta
			$user_meta = get_user_meta($user_id);
			$user_estado = ( isset($user_meta['user_registration_estado']) && count($user_meta['user_registration_estado']) >= 0 ) ? $user_meta['user_registration_estado'][0] : NULL;

			$user_estado_term = museusbr_get_estado_term_from_user_meta($user_estado);

			if ( $user_estado_term === NULL ) {
				$query->set( 'author', get_current_user_id() );
				return;
			}

			// Pega o valor do metadado de ID 15202, que guarda o estado do museu
			$tax_query = $query->get('tax_query');

			// Se não há tax_query existente, cria um novo array
			if (empty($tax_query)) {
				$tax_query = array();
			}

			// Adiciona a nova query de taxonomia
			$tax_query[] = array(
				'taxonomy' => 'tnc_tax_' . MUSEUSBR_ESTADO_DO_MUSEU_TAXONOMY_ID,
				'field' => 'slug',
				'terms'   => $user_estado_term
			);

			// Se há múltiplas queries de taxonomia, define a relação como AND
			if (count($tax_query) > 1) {
				$tax_query['relation'] = 'AND';
			}
			
			$query->set('tax_query', $tax_query);

		}
    }
}
add_action( 'pre_get_posts', 'museusbr_pre_get_post' );

/**
 * Mapeia os estados guardados como User Meta pelo plugin de registro de usuário para o termo de taxonomia Estado
 */
function museusbr_get_estado_term_from_user_meta( $user_estado ) {
	switch ( $user_estado ) {
		case 'Acre':
			return 'ac';
		case 'Alagoas':
			return 'al';
		case 'Amapá':
			return 'ap';
		case 'Amazonas':
			return 'am';
		case 'Bahia':
			return 'ba';
		case 'Ceará':
			return 'ce';
		case 'Distrito Federal':
			return 'df';
		case 'Espírito Santo':
			return 'es';
		case 'Goiás':
			return 'go';
		case 'Maranhão':
			return 'ma';
		case 'Mato Grosso':
			return 'mt';
		case 'Mato Grosso do Sul':
			return 'ms';
		case 'Minas Gerais':
			return 'mg';
		case 'Pará':
			return 'pa';
		case 'Paraíba':
			return 'pb';
		case 'Paraná':
			return 'pr';
		case 'Pernambuco':
			return 'pe';
		case 'Piauí':
			return 'pi';
		case 'Rio de Janeiro':
			return 'rj';
		case 'Rio Grande do Norte':
			return 'rn';
		case 'Rio Grande do Sul':
			return 'rs';
		case 'Rondônia':
			return 'ro';
		case 'Roraima':
			return 'rr';
		case 'Santa Catarina':
			return 'sc';
		case 'São Paulo':
			return 'sp';
		case 'Sergipe':
			return 'se';
		case 'Tocantins':
			return 'to';
		default:
			return NULL;
	}
}

/**
 * Adiciona classe css ao Admin do WordPress para estilizar a página que lista os museus
 */
function museusbr_custom_body_class($classes) {
	global $pagenow;

	if ( $pagenow == 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === museusbr_get_museus_collection_post_type() )
        $classes .= ' post-type-museusbr-museus';

	if ( museusbr_user_is_gestor_or_parceiro() )
		$classes .= ' user-is-gestor-do-museu';

    return $classes;
}
add_filter('admin_body_class', 'museusbr_custom_body_class');

/**
 * Traduz palavras específicas para o gestor do museu
 */
function museusbr_gestor_translate_words_array( $translated ) {
	if ( get_post_type() !== 'registro' ) {
		$words = array(
			'Pendente' => 'Em análise',
		);
		

		$translated = str_replace(  array_keys($words),  $words,  $translated );
	}
    
   return $translated;
}
add_filter( 'gettext', 'museusbr_gestor_translate_words_array' );
add_filter( 'ngettext', 'museusbr_gestor_translate_words_array' );


/*
 * Adiciona parâmetros para o Admin Tainacan para esconder elementos que não são necessários
 */
function museusbr_set_tainacan_admin_options($options) {
	
	if ( museusbr_user_is_gestor_or_parceiro() ) {
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
		$options['itemEditionStatusOptionOnFooterDropdown'] = true;
		// $options['hideItemEditionStatusPublishOption'] = true;
	}
	return $options;
};
add_filter('tainacan-admin-ui-options', 'museusbr_set_tainacan_admin_options');

function museusbr_desabilita_notificacoes_para_gestores() {

	if ( !museusbr_user_is_gestor_or_parceiro() )
		return;

	// Remove as notificações do WordPress
	remove_action('admin_notices', 'update_nag', 3);
	remove_action('admin_notices', 'maintenance_nag');
	
	// Remove notificações de atualizações
	remove_action('admin_init', '_maybe_update_core');
	remove_action('admin_init', '_maybe_update_plugins');
	remove_action('admin_init', '_maybe_update_themes');
	
	// Remove o widget de eventos e notícias do dashboard
	remove_meta_box('dashboard_primary', 'dashboard', 'side');
	
	// Esconde a contagem de atualizações no menu admin
	add_action('admin_head', function() {
		echo '<style>
			#adminmenu .update-plugins,
			#adminmenu .awaiting-mod,
			#wp-admin-bar-updates,
			.update-nag {
				display: none !important;
			}
		</style>';
	});
}
add_filter('admin_init', 'museusbr_desabilita_notificacoes_para_gestores');
