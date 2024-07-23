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
        if ( isset( $_GET['page'] ) && $_GET['page'] === 'certificado' )
            $admin_title = __( 'Certificado de Registro no MuseusBR', 'museusbr' );
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
        wp_enqueue_style( 'museusbr-certificado-style', get_stylesheet_directory_uri() . '/assets/css/certificado.css', array(), wp_get_theme()->get('Version'), 'print' );
    }

    /**
     * Display the custom column
     */
    function museu_certificado_column() {
        global $post;
        if ( $post->ID == 124523 ) : ?>
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
        <?php endif;
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
                   <h1><?php  _e('Museu não encontrado', 'museusbr'); ?></h1>
                </div>
            <?php 

            return;
        }

        $certificado_items->the_post();

        ?>
            <div class="wrap">
                
                <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td>&nbsp;</td>
                        <td colspan="4">&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td width="10%" height="91">&nbsp;</td>
                        <td colspan="4" align="center" valign="middle"><h1>CERTIFICADO</h1></td>
                        <td width="10%">&nbsp;</td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td colspan="4" align="center" valign="middle"><p>Certificamos que a instituição <br />
                        <strong><?php the_title(); ?></strong><br />foi registrado(a) no Cadastro Nacional de Museus através da plataforma MuseusBR.</p></td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td colspan="4" align="center" valign="middle">&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td colspan="4" align="center" valign="middle"><p>Brasília, DF, <?php echo date(get_option('date_format')); ?></p></td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td colspan="4" align="center" valign="middle">&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td colspan="4" align="center" valign="middle"><img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/img-assinatura.png" alt="" width="282" height="65" /></td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td colspan="4" align="center" valign="middle">Imagem assinatura de alguem do Ibram</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td colspan="4" align="center" valign="middle">&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td colspan="4" align="center" valign="middle"><hr /></td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td align="center" valign="middle"><img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/logo-sbm.png" class="logo" /></td>
                        <td align="center" valign="middle"><img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/logo-ibram.png" class="logo" /></td>
                        <td align="center" valign="middle"><img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/logo-gov.png" class="logo" /></td>
                        <td align="center" valign="middle"><img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/logo-museu.png" class="logo" /></td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td colspan="4" align="center" valign="middle">&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                </table>
                
            </div>
        <?php
    }
    
}
