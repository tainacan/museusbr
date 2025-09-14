<?php

// Função para registrar o tipo de post 'registro'
function museusbr_create_registro_post_type() {

    if ( MUSEUSBR_ENABLE_REGISTRO === false )
        return;

    register_post_type('registro',
        array(
            'labels' => array(
                'name' => 'Registros',
                'singular_name' => 'Registro',
                'add_new' => 'Solicitar novo',
                'add_new_item' => 'Solicitar novo Registro',
                'edit_item' => 'Editar Registro',
                'new_item' => 'Novo Registro',
                'view_item' => 'Ver Registro',
                'search_items' => 'Buscar solicitações',
                'not_found' => 'Nenhum Registro encontrado',
                'not_found_in_trash' => 'Nenhum Registro Indeferido',
            ),
            'description' => 'Pedidos de registros dos museus cadastrados',
            'public' => true,
            'exclude_from_search' => true,
            'publicly_queryable' => false,
            'show_in_admin_bar' => false,
            'show_in_rest' => true,
            'show_in_menu' => 'edit.php?post_type=' . museusbr_get_museus_collection_post_type(),
            'show_in_nav_menus' => false,
            'has_archive' => false,
            'supports' => array('title', 'custom-fields', 'comments', 'author'),
            'menu_position' => 5,
            'menu_icon' => 'dashicons-archive',
        )
    );
    register_post_meta('registro', 'registro_museu_id', array(
        'type' => 'integer',
        'single' => true,
        'show_in_rest' => true,
        'auth_callback' => function() {
            return current_user_can('edit_registros');
        }
    ));
    register_post_meta('registro', 'cnpj', array(
        'type' => 'integer',
        'single' => true,
        'show_in_rest' => true,
        'auth_callback' => function() {
            return current_user_can('edit_registros');
        }
    ));
    register_post_meta('registro', 'instrumento_criacao', array(
        'type' => 'integer',
        'single' => true,
        'show_in_rest' => true,
        'auth_callback' => function() {
            return current_user_can('edit_registros');
        }
    ));
    register_post_meta('registro', 'cpf_rg', array(
        'type' => 'integer',
        'single' => true,
        'show_in_rest' => true,
        'auth_callback' => function() {
            return current_user_can('edit_registros');
        }
    ));
    register_post_meta('registro', 'documentos_identidade', array(
        'type' => 'integer',
        'single' => true,
        'show_in_rest' => true,
        'auth_callback' => function() {
            return current_user_can('edit_registros');
        }
    ));
    register_post_meta('registro', 'termo_solicitacao', array(
        'type' => 'integer',
        'single' => true,
        'show_in_rest' => true,
        'auth_callback' => function() {
            return current_user_can('edit_registros');
        }
    ));
    register_post_meta('registro', 'aderir_sbm', array(
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'default' => false,
        'auth_callback' => function() {
            return current_user_can('edit_registros');
        }
    ));
    register_post_meta('registro', 'certificado_registro', array(
        'type' => 'integer',
        'single' => true,
        'show_in_rest' => false,
        'auth_callback' => function() {
            return current_user_can('publish_posts');
        }
    ));
    register_post_meta('registro', 'justificativa_rejeite_texto', array(
        'type' => 'string',
        'single' => true,
        'show_in_rest' => false,
        'auth_callback' => function() {
            return current_user_can('delete_posts');
        }
    ));
    register_post_meta('registro', 'justificativa_rejeite_arquivo', array(
        'type' => 'integer',
        'single' => true,
        'show_in_rest' => false,
        'auth_callback' => function() {
            return current_user_can('delete_posts');
        }
    ));
    array_map(function($i) {
        register_post_meta('registro', 'documento_complementar_' . $i, array(
            'type' => 'integer',
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => function() {
                return current_user_can('edit_registros');
            }
        ));
    }, range(1, 5));

    add_registro_custom_capabilities();
    add_filter( 'map_meta_cap', 'registro_custom_capabilities', 10, 4 );
    add_filter( 'upload_dir', 'registro_upload_dir', 10, 1 );
}
add_action('init', 'museusbr_create_registro_post_type');

// Substituir o link de criação de novo post
function museusbr_replace_add_new_link($submenu_file) {
    global $submenu;

    if ( !isset($submenu['edit.php?post_type=registro']) )
        return $submenu_file;

    foreach ($submenu['edit.php?post_type=registro'] as $key => $value) {
        if ($value[2] == 'post-new.php?post_type=registro')
            $submenu['edit.php?post_type=registro'][$key][2] = 'admin.php?page=registro';
    }
    return $submenu_file;
}
add_filter('submenu_file', 'museusbr_replace_add_new_link');

function museusbr_change_add_new_link_for_registro( $url, $path ) {
    if ( $path === 'post-new.php?post_type=registro' )
        $url = admin_url('admin.php?page=registro');
    
    return $url;
}
add_filter( 'admin_url', 'museusbr_change_add_new_link_for_registro', 10, 2 );

// Substituir o link de edição de post
function museusbr_replace_edit_link($actions, $post) {
    if ( get_post_type($post) === 'registro' )
        $actions['edit'] = '<a href="' . admin_url('admin.php?page=registro&post=' . $post->ID) . '">Editar</a>';

    return $actions;
}
add_filter('post_row_actions', 'museusbr_replace_edit_link', 10, 2);

// Substituir o link no título do post
function museusbr_registro_edit_post_link( $url, $post_ID ) {
    if ( get_post_type($post_ID) === 'registro' )
        $url = admin_url( 'admin.php?page=registro&post=' . $post_ID );
    
    return $url;
}
add_filter('get_edit_post_link', 'museusbr_registro_edit_post_link', 10, 2);

function museusbr_registro_translate_words_array( $translated ) {
    if ( get_post_type() === 'registro' ) {
        $words = array(
            // 'word to translate' = > 'translation'
            'Publicado' => 'Aprovado',
            'Privado' => 'pendente', // Usando 'pendente' com p minúsculo para diferenciar de 'Pendente' do WordPress.
            'Pendente' => 'Enviado para análise',
            'Rascunho' => 'Em preenchimento',
            'Rascunho automático' => 'Em preenchimento',
            'Lixeira' => 'Indeferido',
            'Lixos' => 'Indeferidos',
            'Colocar na lixeira' => 'Indeferir',
        );

        $translated = str_replace(  array_keys($words),  $words,  $translated );
    }
   return $translated;
}
add_filter( 'gettext', 'museusbr_registro_translate_words_array' );
add_filter( 'ngettext', 'museusbr_registro_translate_words_array' );

function museusbr_get_registro_status_label( $status_slug ) {
    switch ( $status_slug ) {
        case 'publish':
            return 'Aprovado';
        case 'private':
            return 'Pendente';
        case 'pending':
            return 'Enviado para análise';
        case 'draft':
        case 'auto-draft':
            return 'Em preenchimento';
        case 'trash':
            return 'Indeferido';
        default:
            return $status_slug;
    }
}

function museusbr_change_post_statuses( $post_states, $post ) {

    if ( $post->post_type === 'registro' ) {
        foreach ( $post_states as $key => $state ) {
            $special_status_label = museusbr_get_registro_status_label($key);
            if ( $special_status_label === $state )
                continue;
            $post_states[$key] = $special_status_label;
        }
    }

    return $post_states;
}
add_filter( 'display_post_states', 'museusbr_change_post_statuses', 10, 2 );

/**
 * Função para obter uma lista de itens "museus".
 * @param array $args Argumentos para a busca de itens (WP_Query)
 * @param int $id_author ID do autor a ser filtrado (opcional) 
 */
function museusbr_get_museus_cadastrados($args = array(), $id_author = null) {
    $tainacan_items_repository = \Tainacan\Repositories\Items::get_instance();

    if ( $id_author ) {
        $args['author'] = $id_author;
    }

    $default_args = array(
        'orderby' => 'title',
        'order' => 'ASC'
    );
    $args = array_merge($default_args, $args);

    $items = $tainacan_items_repository->fetch($args, [ museusbr_get_museus_collection_id() ],  'OBJECT');

    return $items;
}

/**
 * Função para obter uma lista de itens "museus" cadastrados pelo usuário logado.
 */
function museusbr_get_meus_museus_cadastrados($args = array()) {
    return museusbr_get_museus_cadastrados($args, get_current_user_id());
}


/**
 * Adiciona a capacidade de editar registros privados
 */
function registro_custom_capabilities( $caps, $cap, $user_id, $args ) {
    
    $post = isset($args[0]) ? get_post( $args[0] ) :  null;
    if ( $post && 'registro' === $post->post_type ) {
        if ( 'edit_posts' === $cap ) {
            $caps = array( 'edit_registros' );
        } elseif ( 'edit_post' === $cap ) {
            $caps = array( 'edit_registro' );
        }
        // elseif ( 'read_post' === $cap ) {
        //     $caps = array( 'read_registro' );
        // } elseif ( 'delete_post' === $cap ) {
        //     $caps = array( 'delete_registro' );
        elseif ( 'edit_private_posts' === $cap ) {
            $caps = array( 'edit_private_registros' );
        }
    }
    return $caps;
}

/**
 * Associa as capabilities customizadas ao perfil de gestor
 */
function add_registro_custom_capabilities() {
    
    $gestor_role = get_role( MUSEUSBR_GESTOR_DE_MUSEU_ROLE );
    if ( $gestor_role ) {
        $gestor_role->add_cap( 'edit_registros' );
        $gestor_role->add_cap( 'edit_registro' );
        // $gestor_role->add_cap( 'read_registro' );
        // $gestor_role->add_cap( 'delete_registro' );
        // $gestor_role->add_cap( 'edit_private_registros' );
    }


    $parceiros_role = get_role( MUSEUSBR_PARCEIROS_DO_IBRAM_ROLE );
    if ( $parceiros_role ) {
        $parceiros_role->add_cap( 'edit_registros' );
        $parceiros_role->add_cap( 'read_registro' );
        // $parceiros_role->add_cap( 'delete_registro' );
        // $parceiros_role->add_cap( 'edit_private_registros' );
    }

    $admin_role = get_role( 'administrator' );
    if ( $admin_role ) {
        $admin_role->add_cap( 'edit_registros' );
        $admin_role->add_cap( 'edit_registro' );
        // $admin_role->add_cap( 'read_registro' );
        // $admin_role->add_cap( 'delete_registro' );
        // $admin_role->add_cap( 'edit_private_registros' );
    }
    
}

/**
 * Define um diretório de upload específico para os registros
 */
function registro_upload_dir($uploads) {
    
    $post_id = false;
    
    // regular ajax uploads via Admin Panel will send post_id
    if ( isset($_REQUEST['post_id']) && $_REQUEST['post_id'] ) {
        $post_id = sanitize_text_field($_REQUEST['post_id']);
    }

    // API requests to media endpoint will send post
    if ( false === $post_id && isset($_REQUEST['post']) && is_numeric($_REQUEST['post']) ) {
        $post_id = sanitize_text_field($_REQUEST['post']);
    }

    if (false === $post_id)
        return $uploads;
    
    if ( get_post_type($post_id) === 'registro' ) {
        $subdir = '/uploads/museusbr-registro/' . $post_id;
        $uploads['subdir'] = $subdir;
        $uploads['path'] = WP_CONTENT_DIR . $subdir;
        $uploads['url'] = content_url($subdir);
        $uploads['basedir'] = WP_CONTENT_DIR . $subdir;
        $uploads['baseurl'] = content_url($subdir);
    }

    return $uploads;
}

/**
 * Adiciona um botão extra a tela da lsita de registros
 */
function museusbr_add_registros_download_button($where) {
    global $post_type_object;
    if ($post_type_object->name === 'registro') {
        echo '<div class="alignright actions" style="margin-left: 1rem;"><a class="button action" href="' . esc_url(get_theme_mod('museusbr_lista_de_registro_metabase_link', 'https://metabase.tainacan.org/public/question/ffd491bf-ab44-4465-b972-9350807b33bc.csv')) . '" download target="_blank">Baixar lista de registros</a></div>';
    }
}
add_action('manage_posts_extra_tablenav', 'museusbr_add_registros_download_button');