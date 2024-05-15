<?php

if ( is_admin() ) {
    new MUSEUSBR_Certificado_Page();
}

/**
 * MUSEUSBR_Certificado_Page classe para criar e exibir a página de uma certificado
 */
class MUSEUSBR_Certificado_Page {

    private $id_certificado = '';
    private $tainacan_items_repository = null;

    /**
     * Constructor will create the menu item
     */
    public function __construct() {
        add_action( 'admin_menu', array($this, 'add_menu_certificado_page' ));
        add_filter( 'manage_' . museusbr_get_collection_post_type() . '_posts_columns', array( $this, 'set_custom_museu_certificado_column' ));
        add_action( 'manage_' . museusbr_get_collection_post_type() . '_posts_custom_column' , array( $this, 'museu_certificado_column'), 10, 2 );
        add_action( 'admin_print_styles-admin_page_certificado', array( $this, 'admin_print_certificado_custom_css' ) );
        add_filter( 'admin_title', array( $this, 'certificado_admin_title' ), 10, 2);

        $this->tainacan_items_repository = \Tainacan\Repositories\Items::get_instance();
    }

    /**
     * Menu item will allow us to load the page to display the table
     */
    public function add_menu_certificado_page() {
        add_submenu_page(
            '', // Definindo o parent como nulo para não criar um menu, apenas registrar a página
            __('Certificado', 'museusbr'),
            __('Certificado', 'museusbr'),
            'read',
            'certificado',
            array($this, 'render_certificado_page')
        );
    }

    /**
     * Add the custom column to the table
     */
    function set_custom_museu_certificado_column($columns) {
        $columns['author'] = __( 'Autor', 'museusbr' );
        unset($columns['comments']);
        $columns['museu_certificado'] = __( 'Certificado', 'museusbr' );
       
        return $columns;
    }

    /**
     * Change the admin title
     */
    function certificado_admin_title($admin_title, $title) {
        return __( 'Certificado de Registro no MuseusBR', 'museusbr');
    }

    /**
     * Add the custom css to the page
     */
    function admin_print_certificado_custom_css() {
        wp_enqueue_style( 'museusbr-certificado-style', get_stylesheet_directory_uri() . '/certificado.css', array(), wp_get_theme()->get('Version'), 'print' );
    }

    /**
     * Display the custom column
     */
    function museu_certificado_column() {
        global $post;
        ?>
        <a 
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
                        window.URL.revokeObjectURL('<?php echo admin_url( 'admin.php?page=certificado&id=' . $post->ID ); ?>')
                        document.body.removeChild(iframe)
                    }, 1);
                };
                iframe.src = '<?php echo admin_url( 'admin.php?page=certificado&id=' . $post->ID ); ?>';
                
            ">
            <?php echo __('Imprimir certificado', 'museusbr'); ?>
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
                    <h1><?php _e( 'Certificado', 'museusbr'); ?></h1>
                    <p><?php _e( 'ID do museu certificado não informado.', 'museusbr' ); ?></p>
                </div>
            <?php
            
            return; 
        }

        $this->id_certificado = $_GET['id'];
        
        $certificado_items = $this->tainacan_items_repository->fetch( array( 'id' => $this->id_certificado ), museusbr_get_collection_id() );

        if ( !$certificado_items->have_posts() ) {
            ?>
                <div class="wrap">
                    <h1><?php _e( 'Certificado de Registro no MuseusBR', 'museusbr'); ?></h1>
                    <p><?php _e( 'Certificado não encontrado', 'museusbr' ); ?></p>
                </div>
            <?php 

            return;
        }

        $certificado_items->the_post();

        ?>
            <div class="wrap">
                
                <h1 
                        style="font-size: 0.75cm;"
                        class="wp-heading-inline">
                    <?php _e('Certificado de Registro no MuseusBR', 'museusbr'); ?>
                </h1>
                <p style="font-size: 0.5cm">
                    Certificamos que <strong><?php the_title(); ?></strong> foi registrado no Cadastro Nacional de Museus através da plataforma MuseusBR.
                </p>
                
            </div>
        <?php
    }
    
}
