<?php

if ( is_admin() && MUSEUSBR_ENABLE_CERTIFICADO_REGISTRO ) {
    new MUSEUSBR_Certificado_Registro_Page();
}

/**
 * MUSEUSBR_Certificado_Page classe para criar e exibir a página de uma certificado
 */
class MUSEUSBR_Certificado_Registro_Page {

    private $id_certificado = '';
    private $tainacan_items_repository = null;

    /**
     * Constructor will create the menu item
     */
    public function __construct() {
        add_action( 'admin_menu', array($this, 'add_menu_certificado_page' ));
        add_action( 'admin_print_styles-admin_page_certificado-registro', array( $this, 'admin_print_certificado_custom_css' ) );
        add_filter( 'admin_title', array( $this, 'certificado_admin_title' ), 10, 2);

        $this->tainacan_items_repository = \Tainacan\Repositories\Items::get_instance();
    }

    /**
     * Menu item will allow us to load the page to display the table
     */
    public function add_menu_certificado_page() {
        add_submenu_page(
            '', // Definindo o parent como nulo para não criar um menu, apenas registrar a página
            'Certificado de Registro',
            'Certificado de Registro',
            'read',
            'certificado-registro',
            array($this, 'render_certificado_page')
        );
    }

    /**
     * Change the admin title
     */
    function certificado_admin_title($admin_title, $title) {
        if ( isset( $_GET['page'] ) && $_GET['page'] === 'certificado-registro' )
            $admin_title = 'Certificado de Registro no MuseusBr';
        return $admin_title;
    }

    /**
     * Add the custom css to the page
     */
    function admin_print_certificado_custom_css() {
        wp_dequeue_style( 'dashicons' );
        wp_dequeue_style( 'admin-bar' );
        wp_dequeue_style( 'common' );
        wp_dequeue_style( 'forms' );
        wp_dequeue_style( 'admin-menu' );
        wp_dequeue_style( 'dashboard' );
        wp_dequeue_style( 'list-tables' );
        wp_dequeue_style( 'edit' );
        wp_dequeue_style( 'revisions' );
        wp_dequeue_style( 'media' );
        wp_dequeue_style( 'themes' );
        wp_dequeue_style( 'about' );
        wp_dequeue_style( 'nav-menus' );
        wp_dequeue_style( 'wp-pointer' );
        wp_dequeue_style( 'widgets' );
        wp_dequeue_style( 'site-icon' );
        wp_dequeue_style( 'l10n' );
        wp_dequeue_style( 'buttons' );
        wp_dequeue_style( 'wp-auth-check' );
        wp_dequeue_style( 'media-views' );
        wp_dequeue_style( 'museusbr-admin-style' );
        wp_enqueue_style( 'museusbr-certificado-style', get_stylesheet_directory_uri() . '/assets/css/certificado-registro.css', array(), wp_get_theme()->get('Version'), 'print' );
    }

    /**
     * Display the custom column
     */
    function museu_certificado_column($column_name) {
        global $post;

        if ( $column_name != 'museu_certificado' )
            return;

        if ( get_post_status( $post ) !== 'publish' ) : ?>
            <p>O certificado poderá ser impresso assim que o registro for aprovado.</p>
        <?php else :
            $this->render_certificado_button($post);
        endif;
    }
    
    public static function render_certificado_button($post, $classes = 'wp-button button', $icon = null ) {
    ?>
        <a 
            class="<?php echo $classes; ?>"
            style="cursor: pointer;"
            onclick="
                var iframe = document.createElement('iframe');
                iframe.className='pdfIframe'
                document.body.appendChild(iframe);
                iframe.style.display = 'none';
                iframe.onload = function () {
                    setTimeout(function () {
                        iframe.focus();
                        iframe.contentWindow.print();
                        window.URL.revokeObjectURL('<?php echo admin_url( 'admin.php?page=certificado-registro&id=' . $post->ID ); ?>')
                        document.body.removeChild(iframe)
                    }, 1);
                };
                iframe.src = '<?php echo admin_url( 'admin.php?page=certificado-registro&id=' . $post->ID ); ?>';
                
            ">
            <?php if ( $icon ) : ?>
                <span class="icon"><?php echo $icon; ?></span>
            <?php endif; ?>
            <span>Imprimir certificado</span>
        </a>
    <?php
    }

    /**
     * Display the page
     *
     * @return Void
     */
    public function render_certificado_page() {

        if ( !isset($_GET['id']) ) {
            ?>  
                <div class="wrap">
                    <h1>Certificado</h1>
                    <p>ID do registro não informado.</p>
                </div>
            <?php
            
            return; 
        }

        $this->id_certificado = $_GET['id'];
        
        $registro = get_post( $this->id_certificado );
        if ( !$registro || $registro->post_type !== 'registro' ) {
            ?>  
                <div class="wrap">
                    <h1>Certificado</h1>
                    <p>Registro não encontrado.</p>
                </div>
            <?php
            
            return; 
        }

        $selected_museu_id = get_post_meta($registro->ID, 'registro_museu_id', true);

        if ( !$selected_museu_id || empty($selected_museu_id) ) {
            ?>  
                <div class="wrap">
                    <h1>Certificado</h1>
                    <p>Registro sem museu vinculado.</p>
                </div>
            <?php
            
            return; 
        }

        $museu = get_post($selected_museu_id);

        if ( !$museu || $museu->post_type !== museusbr_get_museus_collection_post_type() ) {
            ?>  
                <div class="wrap">
                    <h1>Certificado</h1>
                    <p>Museu vinculado ao registro não encontrado.</p>
                </div>
            <?php
            
            return; 
        }

        $item = tainacan_get_item( $museu->ID );

        $codigo_identificador_ibram = '';
			
        try {
            // O metadado do código de identificação do Ibram
            $codigo_metadatum = new \Tainacan\Entities\Metadatum( museusbr_get_codigo_identificador_ibram_metadatum_id() );

            if ( $codigo_metadatum instanceof \Tainacan\Entities\Metadatum ) {
                
                $codigo_item_metadatum = new \Tainacan\Entities\Item_Metadata_Entity( $item, $codigo_metadatum );
        
                if ( $codigo_item_metadatum->has_value() )
                    $codigo_identificador_ibram = $codigo_item_metadatum->get_value();
            }

        } catch (Exception $e) {
            error_log('Erro ao tentar acessar o metadado do Código de Identificação do Ibram: ' . $e->getMessage());
        }
        $codigo_identificador_ibram = 'a';
        if ( !$codigo_identificador_ibram || empty($codigo_identificador_ibram) ) {
            ?>
                <div class="wrap">
                   <h1>Código identificador do Museu não encontrado</h1>
                </div>
            <?php 

            return;
        }

        ?>
            <div class="wrap">

                <div class="certificado-registro-conteudo">
                    <h1><strong>CERTIFICADO DE MUSEU REGISTRADO</strong></h1>

                    <p>O Ministério da Cultura, por meio do Instituto Brasileiro de Museus, reconhece a instituição <strong><?php echo get_the_title($museu->ID); ?></strong>, código identificador <strong><?php echo $codigo_identificador_ibram; ?></strong> a partir dos critérios estabelecidos no art. 1º do Estatuto de Museus, Lei nº 11.904, de 14 de janeiro de 2009. Informações verificadas pelo Cadastro Nacional de Museus.</p>
                    
                    <p>Este documento certifica que esta instituição contribui para o desenvolvimento e monitoramento da Política Nacional de Museus.</p>

                    <p>
                        <strong>Válido até:</strong>
                    <?php 
                        $date = new DateTime(get_post_modified_time('Y-m-d H:i:s', false, $this->id_certificado));
                        $date->add(new DateInterval('P5Y')); // Adiciona 5 anos
                        echo $date->format('d/m/Y');
                    ?>
                    </p>
                </div>
                
                <?php 
                    $regua_de_logos = get_theme_mod('museusbr_certificado_registro_regua_de_logos', null);
                    $regua_de_logos_url = $regua_de_logos && !empty($regua_de_logos) && isset($regua_de_logos['attachment_id']) ? wp_get_attachment_image_src($regua_de_logos['attachment_id'], 'full') : null;

                if ( $regua_de_logos_url && isset($regua_de_logos_url[0])) : ?>
                    <img src="<?php echo $regua_de_logos_url[0]; ?>" class="regua-de-logos" />
                <?php endif; ?>
            </div>
        <?php
    }
    
}
