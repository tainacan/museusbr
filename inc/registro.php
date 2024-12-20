<?php
if ( is_admin() && MUSEUSBR_ENABLE_REGISTRO ) {
    new MUSEUSBR_Registro_Form_Page();
}

/**
 * Esta action precisa ser rodada aqui por que o init ocorre em um contexto onde a classe não está disponível
 */
function museusbr_registro_init() {
    add_action('museusbr_registro_cron_hook', array('MUSEUSBR_Registro_Form_Page', 'museusbr_registro_cron_exec'));
}
add_action('init', 'museusbr_registro_init');

/**
 * MUSEUSBR_Registro_Form_Page classe para criar e exibir a página de formulário de registro
 */
class MUSEUSBR_Registro_Form_Page {

    /**
     * O construtor criará o item de menu
     */
    public function __construct() {
        add_action( 'admin_init', array($this, 'create_registro_auto_draft'));
        add_action( 'admin_menu', array($this, 'add_menu_registro_form_page'));
        add_action( 'admin_post_save_registro_form', array($this, 'save_registro_form'));
        add_action( 'admin_enqueue_scripts', array($this, 'admin_enqueue_styles'));
        add_action( 'admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action( 'wp_ajax_save_comment', array($this, 'save_comment'));
        add_action( 'wp_ajax_nopriv_save_comment', array($this, 'save_comment'));
        add_action( 'wp_ajax_remove_attachment', array($this, 'remove_attachment'));
        add_action( 'wp_ajax_nopriv_remove_attachment', array($this, 'remove_attachment'));
        add_filter( 'wp_handle_upload', array($this, 'handle_media_upload'));
        add_filter( 'wp_handle_upload_prefilter', array($this, 'restrict_media_upload_to_pdf'));
        add_filter( 'manage_registro_posts_columns', array($this, 'set_custom_registro_column'));
        add_action( 'manage_registro_posts_custom_column', array($this, 'registro_column'), 10, 2);
        add_filter( 'manage_' . museusbr_get_museus_collection_post_type() . '_posts_columns', array($this, 'set_custom_museu_registro_column'));
        add_action( 'manage_' . museusbr_get_museus_collection_post_type() . '_posts_custom_column', array($this, 'museu_registro_column'), 10, 2);
        add_filter( 'post_row_actions', array($this, 'remove_quick_edit'), 10, 2);
        add_action( 'private_registro', array($this, 'private_registro_send_email'), 10, 3);
        add_action( 'transition_post_status', array($this, 'update_registro_logs'), 10, 3 );

        if ( !wp_next_scheduled('museusbr_registro_cron_hook') )
            wp_schedule_event(time(), 'daily', 'museusbr_registro_cron_hook');
    }

    /**
     * O item de menu nos permitirá carregar a página para exibir o formulário
     */
    public function add_menu_registro_form_page() {
        add_submenu_page(
            'edit.php?post_type=registro', // Slug do pai
            'Formulário de Registro',
            'Formulário de Registro',
            'edit_registros',
            'registro',
            array($this, 'render_registro_form_page')
        );
    }

    /**
     * Adiciona estilos personalizados
     */
    public function admin_enqueue_styles()
    {
        if (isset($_GET['page']) && $_GET['page'] === 'registro') {
            wp_enqueue_style('museusbr-registro-bulma', 'https://cdn.jsdelivr.net/npm/bulma@1.0.2/css/versions/bulma-no-dark-mode.min.css');
            wp_enqueue_style('registro-form-styles', get_stylesheet_directory_uri() . '/assets/css/registro.css');
        }
    }


    /**
     * Enfileira o scripts do formulário de registro
     */
    public function admin_enqueue_scripts()
    {
        if (isset($_GET['page']) && $_GET['page'] === 'registro') {
            wp_enqueue_media();
            wp_enqueue_script('registro-form-scripts', get_stylesheet_directory_uri() . '/assets/js/registro-form-scripts.js', array(), null, true);

            $registro_script_settings = array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'wp_nonce' => wp_create_nonce('wp_rest'),
                'save_comments_nonce' => wp_create_nonce('save_comment_nonce'),
                'remove_attachment_nonce' => wp_create_nonce('remove_attachment_nonce'),
                'save_registro_form_nonce' => wp_create_nonce('save_registro_form_nonce'),
                'theme_uri' => get_stylesheet_directory_uri()
            );
            wp_localize_script('registro-form-scripts', 'registro_script_settings', $registro_script_settings);
        }
    }

    /**
     * Cria um rascunho automático ao acessar a página
     */
    public function create_registro_auto_draft() {
        if (isset($_GET['page']) && $_GET['page'] === 'registro') { // Verifique se estamos na página correta
            if (!isset($_GET['post']) || empty($_GET['post'])) {
                // Criar um novo rascunho automático
                $post_id = wp_insert_post(array(
                    'post_title' => 'Novo pedido de Registro',
                    'post_type' => 'registro',
                    'post_status' => 'auto-draft'
                ));

                // Redirecionar para a mesma página com o post_id do novo rascunho
                if ($post_id) {
                    wp_redirect(add_query_arg('post', $post_id, get_permalink()));
                    exit;
                }
            }
        }
    }

    /**
     * Renderiza a página personalizada com um formulário
     */
    public function render_registro_form_page() {

        // Verificar se um post_id foi passado na URL
        if (!isset($_GET['post']) || empty($_GET['post'])) {
            return; // Se não houver post_id, algo deu errado no redirecionamento
        } else {
            $post_id = intval($_GET['post']);
        }

        $post = get_post($post_id);
        $post_status_slug = get_post_status($post);
        $post_status_label = museusbr_get_registro_status_label($post_status_slug);

        // O campo deve ser editável somente se o usuário poder editar e se, caso não seja admin (não pode publicar) esteja em um status de em preenchimento ou pendente
        $post_status_slug = get_post_status($post_id);
        $is_editable = current_user_can('edit_posts', $post_id) &&
            (
                current_user_can('publish_posts', $post_id) ||
                ( $post_status_slug === 'auto-draft' || $post_status_slug === 'draft' || $post_status_slug === 'private' )
            );

        $museu_id = isset($_GET['museu_id']) ? intval($_GET['museu_id']) : false;

        $missing_required_fields = isset( $_GET['missing_required_fields'] ) ? explode(',', $_GET['missing_required_fields']) : [];

        $timestamp = wp_next_scheduled('museusbr_registro_cron_hook');
        wp_unschedule_event($timestamp, 'museusbr_registro_cron_hook');
    ?>
        <div class="wrap">
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">

                <div class="container py-4">

                    <h1 class="title is-1">
                        <?php echo ($post && $post->post_title ? $post->post_title : 'Formulário de Registro'); ?>
                        <?php if ( current_user_can('publish_posts', $post_id) ) : ?>
                            <button 
                                    type="button"
                                    class="button log-preview-modal-trigger"
                                    data-target="log-preview-modal"
                                    title="Análise de log">
                                Ver histórico
                            </button>
                        <?php endif; ?>
                        <span class="tag is-medium is-pulled-left mr-3 has-text-white <?php echo 'is-' . $this->get_color_class_by_status($post_status_slug); ?>">
                            <strong><?php echo $post_status_label ? $post_status_label : 'Novo registro'; ?></strong>
                        </span>
                    </h1>

                    <div class="museusbr-admin-inner-content">

                        <input type="hidden" name="action" value="save_registro_form">
                        <input type="hidden" name="post_id" value="<?php echo esc_attr($post_id); ?>">

                        <?php wp_nonce_field('save_registro_form_nonce', 'save_registro_form_nonce'); ?>
                        <?php wp_nonce_field('remove_attachment_nonce', 'remove_attachment_nonce'); ?>

                        <div class="box">

                            <div class="columns is-multiline">

                                <div class="column" style="min-width: 360px">
                                    <?php
                                    $selected_museu_id = $museu_id ? $museu_id : get_post_meta($post_id, 'registro_museu_id', true);
                                    
                                    if ( $selected_museu_id ) : $museu = get_post($selected_museu_id); ?>
                                        <h2 class="title is-2 has-text-dark m-0">
                                            <span class="icon">
                                                <i>
                                                    <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24" viewBox="0 0 24 24" width="24" fill="currentColor">
                                                        <g>
                                                            <rect fill="none" height="24" width="24" />
                                                        </g>
                                                        <g>
                                                            <g>
                                                                <rect height="7" width="3" x="4" y="10" />
                                                                <rect height="7" width="3" x="10.5" y="10" />
                                                                <rect height="3" width="20" x="2" y="19" />
                                                                <rect height="7" width="3" x="17" y="10" />
                                                                <polygon points="12,1 2,6 2,8 22,8 22,6" />
                                                            </g>
                                                        </g>
                                                    </svg>
                                                </i>
                                            </span>
                                            <span>Museu cadastrado: <?php echo esc_html($museu->post_title); ?></span>
                                        </h2>
                                        <input type="hidden" name="registro_museu_id" value="<?php echo esc_attr($selected_museu_id); ?>">
                                    <?php else : ?>
                                        <div class="field">
                                            <label class="label" for="registro_museu_id">
                                                <span class="icon" style="bottom: -2px; position: relative;">
                                                    <i>
                                                        <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24" viewBox="0 0 24 24" width="24" fill="#505253">
                                                            <g>
                                                                <rect fill="none" height="24" width="24" />
                                                            </g>
                                                            <g>
                                                                <g>
                                                                    <rect height="7" width="3" x="4" y="10" />
                                                                    <rect height="7" width="3" x="10.5" y="10" />
                                                                    <rect height="3" width="20" x="2" y="19" />
                                                                    <rect height="7" width="3" x="17" y="10" />
                                                                    <polygon points="12,1 2,6 2,8 22,8 22,6" />
                                                                </g>
                                                            </g>
                                                        </svg>
                                                    </i>
                                                </span>
                                                <span>Museu cadastrado</span>
                                            </label>
                                            <div class="control">
                                                <select required name="registro_museu_id" id="registro_museu_id" class="select" <?php echo ( !$is_editable ? 'disabled' : ''); ?>>
                                                    <option value="" selected="<?php echo ($selected_museu_id ? 'false' : 'true'); ?>">Selecione um museu cadastrado</option>
                                                    <?php
                                                    $museus = museusbr_get_meus_museus_cadastrados(); // Função para obter a lista de museus da coleção do Tainacan
                                                    $selected_museu_id = get_post_meta($post_id, 'registro_museu_id', true);
                                                    foreach ($museus as $museu) {
                                                        echo '<option value="' . esc_attr($museu->get_ID()) . '"' . selected($selected_museu_id, $museu->get_ID(), false) . '>' . esc_html($museu->get_title()) . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="field">
                                        <label class="checkbox">
                                            <input type="checkbox" name="aderir_sbm" value="1" <?php checked(get_post_meta($post_id, 'aderir_sbm', true), 1); ?>  <?php echo ( !$is_editable ? 'disabled' : ''); ?>>
                                            Deseja aderir ao Sistema Brasileiro de Museus?
                                        </label>
                                    </div>
                                </div>

                                <div class="column content">

                                    <?php if ( current_user_can('publish_posts', $post_id) ) : ?>
                                        <div class="ml-auto field is-grouped">
                                            <?php if (current_user_can('delete_posts', $post_id)) : ?>
                                                <input type="submit" name="save_trash" class="button is-fullwidth is-danger is-light" value="<?php echo ($post_status_slug === 'trash' ? 'Manter como rejeitado' : 'Definir como rejeitado'); ?>">
                                            <?php endif; ?>
                                                <input type="submit" name="save_pending" class="button is-fullwidth is-link is-light" value="<?php echo ($post_status_slug === 'pending' ? 'Manter como em análise' : 'Retornar para análise'); ?>">
                                            <?php if (current_user_can('publish_posts', $post_id)) : ?>
                                                <input type="submit" name="save_private" class="button is-fullwidth is-warning is-light" value="<?php echo ($post_status_slug === 'private' ? 'Manter como pendente' : 'Definir como pendente'); ?>">
                                                <input type="submit" name="save_publish" class="button is-fullwidth is-success has-text-white" value="<?php echo ($post_status_slug === 'publish' ? 'Salvar mantendo aprovação' : 'Aprovar registro'); ?>">
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ( !current_user_can('publish_posts', $post_id) && $is_editable ) : ?>
                                        <div class="ml-auto field is-grouped">
                                            <?php if ( $post_status_slug === 'auto-draft' ) : ?>
                                                <div class="ml-auto control">
                                                    <input type="submit" class="button is-light" value="Cancelar">
                                                </div>
                                            <?php endif; ?>
                                            <div class="control">
                                                <input type="submit" name="<?php echo ( $post_status_slug === 'private' ? 'save_private' : 'save_draft'); ?>" class="button is-link is-light" value="<?php echo ($post_status_slug === 'draft' ? 'Atualizar pedido' : 'Salvar pedido'); ?>">
                                            </div>
                                            <div class="control">
                                                <input type="submit" name="save_pending" class="button is-link" value="Enviar para análise">
                                            </div>
                                        </div>
                                    <?php endif; ?>
                            
                                </div>

                            </div>

                        </div>

                        <div class="columns">

                            <div class="column is-8">
                              
                                <div class="box">
                                    <div class="columns">
                                    <?php

                                    $dados_do_registro = array(
                                        'dados_da_instituicao' => array(
                                            'label' => 'Documentos da Instituição',
                                            'fields' => array(
                                                'cnpj' => 'CNPJ do museu ou da instituição mantenedora',
                                                'instrumento_criacao' => 'Instrumento de criação do museu'
                                            )
                                        ),
                                        'dados_do_responsavel' => array(
                                            'label' => 'Documentos do Responsável pela Instituição',
                                            'fields' => array(
                                                'cpf_rg' => 'CPF e RG do responsável pela instituição',
                                                'documentos_identidade' => 'Comprovantes da responsabilidade do titular do RG e CPF'
                                            )
                                        )
                                    );

                                    foreach ($dados_do_registro as $secao_do_registro) : ?>
                                        <div class="column">
                                            <fieldset class="is-flex is-flex-direction-column is-gap-1">
                                                <legend class="mb-4" style="width: 100%;">
                                                    <h2 class="title is-2 has-text-dark">
                                                        <!-- <span class="icon">
                                                            <i>
                                                                <svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24" fill="currentColor">
                                                                    <path d="M0 0h24v24H0z" fill="none" />
                                                                    <path d="M16.5 6v11.5c0 2.21-1.79 4-4 4s-4-1.79-4-4V5c0-1.38 1.12-2.5 2.5-2.5s2.5 1.12 2.5 2.5v10.5c0 .55-.45 1-1 1s-1-.45-1-1V6H10v9.5c0 1.38 1.12 2.5 2.5 2.5s2.5-1.12 2.5-2.5V5c0-2.21-1.79-4-4-4S7 2.79 7 5v12.5c0 3.04 2.46 5.5 5.5 5.5s5.5-2.46 5.5-5.5V6h-1.5z" />
                                                                </svg>
                                                            </i>
                                                        </span> -->
                                                        <span style="whitespace: nowrap;"><?php echo $secao_do_registro['label']; ?></span>
                                                    </h2>
                                                </legend>

                                                <?php 
                                                    foreach ($secao_do_registro['fields'] as $slug => $label) {
                                                        $is_missing = $missing_required_fields && in_array($slug, $missing_required_fields);
                                                        $this->render_file_input_field($slug, $label, $post_id, $is_editable, $is_missing);
                                                    }
                                                ?>
                                            </fieldset>
                                        </div>
                                    <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="box">
                                    <h2 class="title is-2 has-text-dark">
                                        <span style="whitespace: nowrap;">Termo de solicitação</span>
                                    </h2>

                                    <div class="columns">
                                        <div class="column is-3">
                                            <div class="field px-0">
                                                <label class="label">1: Faça download do termo:</label>
                                                <div class="crontol">
                                                    <a class="button" target="_blank" href="<?php echo get_theme_mod( 'museusbr_registro_termo_solicitacao_link', '' ); ?>" download="<?php echo get_theme_mod( 'museusbr_registro_termo_solicitacao_link', '' ); ?>">
                                                        <span class="icon">
                                                            <i>
                                                            <svg width="18" xmlns="http://www.w3.org/2000/svg" height="18" viewBox="2874 225.11 19 18.971" fill="none">
                                                                <path d="m2882.428 225.11-.022 9.409-1.338-1.276-1.341-1.295-.76.712-.703.762 5.255 5.242 5.217-5.189-.661-.807-.76-.716-1.341 1.295-1.33 1.272-.027-9.409h-2.189ZM2874 238.748v5.306l.03.027h18.94l.03-.027v-5.306h-1.71v3.619h-15.58v-3.619H2874Z" style="fill: rgb(0, 0, 0); fill-opacity: 1;" class="fills"/>
                                                            </svg>
                                                            </i>
                                                        </span>
                                                        <span>&nbsp;Baixar o termo</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="column is-3">
                                            <div class="field px-0">
                                                <label class="label">2: Assine o termo digitalmente usando uma ferramenta como a <a href="https://www.gov.br/governodigital/pt-br/identidade/assinatura-eletronica" name="Assinatura Eletrônica GovBR" target="_blank">Assinatura Eletrônica do GovBR</a>.</label>
                                            </div>
                                        </div>
                                        <div class="column is-6">
                                            <?php
                                                $is_missing = $missing_required_fields && in_array('termo_solicitacao', $missing_required_fields);
                                                $this->render_file_input_field('termo_solicitacao', '3. Anexe o termo assinado digitalmente.', $post_id, $is_editable, $is_missing);
                                            ?>
                                        </div>
                                    </div>    
                                </div>
                            </div>

                            <div class="column is-4">

                                <?php if ($post_id) : ?>

                                    <?php if ( $post_status_slug == 'publish' || $post_status_slug == 'trash' ) : ?>
                                        <div class="box">
                                            <h2 class="title is-2 has-text-dark">
                                                <span class="icon">
                                                    <i>
                                                        <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24" viewBox="0 0 24 24" width="24" fill="currentColor">
                                                            <rect fill="none" height="24" width="24" />
                                                            <path d="M11,7H2v2h9V7z M11,15H2v2h9V15z M16.34,11l-3.54-3.54l1.41-1.41l2.12,2.12l4.24-4.24L22,5.34L16.34,11z M16.34,19 l-3.54-3.54l1.41-1.41l2.12,2.12l4.24-4.24L22,13.34L16.34,19z" />
                                                        </svg>
                                                    </i>
                                                </span>
                                                <span>
                                                    <span>Registro </span>
                                                    <span class="mr-3 <?php echo 'is-lowercase has-text-' . $this->get_color_class_by_status($post_status_slug); ?>">
                                                        <strong><?php echo $post_status_label ? $post_status_label : 'inicializado'; ?></strong>
                                                    </span>
                                                </span>
                                            </h2>

                                            <!-- Campos do formulário que variam a depender do status. -->
                                            <?php if ( current_user_can( 'delete_posts', $post_id ) && $post_status_slug === 'trash' ) : ?>
                                                <div class="field">
                                                    <label class="label" for="justificativa_rejeite_texto">Justificativa para rejeite</label>
                                                    <div class="control">
                                                        <textarea id="justificativa_rejeite_texto" name="justificativa_rejeite_texto" class="textarea" rows="3"><?php echo esc_html( get_post_meta($post_id, 'justificativa_rejeite_texto', true) ); ?></textarea>
                                                    </div>
                                                    <p class="help">Caso seja necessário o envio de um documento com mais detalhes, use o campo abaixo.</p>
                                                </div>
                                            
                                            <?php endif;
                                                $status_based_fields = array();

                                                if ( current_user_can( 'publish_posts', $post_id ) && $post_status_slug === 'publish' )
                                                    $status_based_fields['certificado_registro'] = 'Certificado de Registro';

                                                if ( current_user_can( 'delete_posts', $post_id ) && $post_status_slug === 'trash' )
                                                    $status_based_fields['justificativa_rejeite_arquivo'] = 'Justificativa para rejeite';
                                            
                                                foreach ($status_based_fields as $slug => $label) {
                                                    $is_missing = $missing_required_fields && in_array($slug, $missing_required_fields);
                                                    $this->render_file_input_field($slug, $label, $post_id, $is_editable, $is_missing);
                                                }
                                            ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="box content">
                                        <h2 class="title is-2 has-text-dark">
                                            <span class="icon">
                                                <i>
                                                    <svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24" fill="currentColor">
                                                        <path d="M0 0h24v24H0z" fill="none" />
                                                        <path d="M21.99 4c0-1.1-.89-2-1.99-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14l4 4-.01-18zM18 14H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z" />
                                                    </svg>
                                                </i>
                                            </span>
                                            <span>Observações</span>
                                        </h2>

                                        <?php if ($post_id) {
                                            // Obter os comentários associados ao post
                                            $comments = get_comments(array(
                                                'post_id' => $post_id,
                                                'status' => 'approve',
                                                'meta_query' => array(
                                                    array(
                                                        'key' => 'registro-log',
                                                        'compare' => 'NOT EXISTS'
                                                    )
                                                )
                                            ));

                                            if ( $comments && count($comments) ) : ?>
                                                <ul class="comment-list">
                                                    <?php foreach ($comments as $comment) : ?>
                                                        <li class="comment media">
                                                                <div class="comment-content media-content">
                                                                    <?php echo get_comment_text($comment); ?>
                                                                    <p class="comment-author">
                                                                        <strong><?php echo get_comment_author($comment); ?></strong>
                                                                    </p>
                                                                </div>
                                                                <p class="comment-date media-left">
                                                                    <em><?php echo get_comment_date('', $comment); ?></em>
                                                                </p>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php else : ?>
                                                <ul class="comment-list">
                                                    <p id="empty-message-warning">Observações podem ser colocadas nesta área pela equipe que está analisando o pedido de registro.</p>
                                                </ul>
                                            <?php endif;
                                        } else {
                                            ?>
                                                <ul class="comment-list">
                                                    <p id="empty-message-warning">Observações podem ser colocadas nesta área pela equipe que está analisando o pedido de registro.</p>
                                                </ul>
                                            <?php 
                                        }
                                        
                                        if (current_user_can('moderate_comments', $post_id)) : ?>
                                            <div id="comment-form-container">
                                                <div id="comment-form">
                                                    <input type="hidden" name="post_id" value="<?php echo esc_attr($post_id); ?>">
                                                    <?php wp_nonce_field('save_comment_nonce', 'comment_form_nonce'); ?>
                                                    <div class="field">
                                                        <label class="label" for="comment">Adicionar observação</label>
                                                        <div class="control">
                                                            <textarea id="comment" name="comment" class="textarea" rows="5"></textarea>
                                                        </div>
                                                        <div class="mt-2 control has-text-right">
                                                            <button type="button" id="submit-comment" class="button is-link is-light">
                                                                <span class="icon">
                                                                    <i>
                                                                        <svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24" fill="currentColor">
                                                                            <path d="M0 0h24v24H0z" fill="none" />
                                                                            <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" />
                                                                        </svg>
                                                                    </i>
                                                                </span>
                                                                <span>
                                                                    Enviar
                                                                </span>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ( museusbr_user_is_gestor_or_parceiro() ) : ?>

                                    <div class="box content">
                                        <h2 class="title is-2 has-text-dark">
                                            <span class="icon">
                                                <i>
                                                    <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24" viewBox="0 0 24 24" width="24" fill="currentColor">
                                                        <rect fill="none" height="24" width="24" />
                                                        <path d="M11,7H2v2h9V7z M11,15H2v2h9V15z M16.34,11l-3.54-3.54l1.41-1.41l2.12,2.12l4.24-4.24L22,5.34L16.34,11z M16.34,19 l-3.54-3.54l1.41-1.41l2.12,2.12l4.24-4.24L22,13.34L16.34,19z" />
                                                    </svg>
                                                </i>
                                            </span>
                                            <span>Documentos necessários</span>

                                        </h2>
                                        <details class="ml-4 my-3">
                                            <summary class="title is-6 mb-0">Documentos da instituição</summary>
                                            <ul class="px-1 mb-4">
                                                <li>CNPJ do museu ou da instituição mantenedora.</li>
                                                <li>Instrumento de criação do museu: Documento oficial da instituição que comprove a criação e a vinculação do museu, como um ato ou instrumento de criação.</li>
                                                <li>Termo de Solicitação de Registro: O termo deve estar assinado conforme a assinatura nos documentos de identificação enviados.</li>
                                            </ul>
                                        </details>
                                        <details class="ml-4 my-3">
                                            <summary class="title is-6 mb-0">Documentos do solicitante</summary>
                                            <ul class="px-1">
                                                <li>CPF e RG do responsável pelo museu.</li>
                                                <li>Documento de comprovação de responsabilidade: termo de posse, ato de nomeação ou outro documento equivalente que confirme o vínculo do responsável ao museu. Caso não exista documento oficial que indique o responsável, será aceito um documento assinado pelo representante máximo da instituição mantenedora, declarando quem é o responsável pelo museu. Nesta situação, o representante máximo deverá enviar cópia de seu RG, CPF e documento oficial que comprove sua ocupação no cargo.</li>
                                            </ul>
                                        </details>
                                    </div>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                </div>

            </form>
        </div>
    <?php
    $this->render_file_preview_modal();
    $this->render_log_preview_modal($post_id);
    }

    /**
     * Salva os dados do formulário
     */
    public function save_registro_form() {

        if (!isset($_POST['save_registro_form_nonce']) || !wp_verify_nonce($_POST['save_registro_form_nonce'], 'save_registro_form_nonce'))
            return;

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $post_status = museusbr_user_is_gestor_or_parceiro() ? 'draft' : 'private';

        if (isset($_POST['save_private']))
            $post_status = 'private';
        elseif (isset($_POST['save_publish']))
            $post_status = 'publish';
        elseif (isset($_POST['save_draft']))
            $post_status = 'draft';
        elseif (isset($_POST['save_pending']))
            $post_status = 'pending';
        elseif (isset($_POST['save_trash']))
            $post_status = 'trash';
        elseif (isset($_POST['Cancelar'])) {

            if ( museusbr_user_is_gestor_or_parceiro() )
                wp_redirect( admin_url('edit.php?post_type=' . museusbr_get_museus_collection_post_type()) );
            else
                wp_redirect( admin_url('edit.php?post_type=registro&status=canceled') );
            exit;
        }

        // Se o post_id for 0, criar um novo post
        if ( $post_id === 0 ) {
            $post_id = wp_insert_post(array(
                'post_title' => 'Novo pedido de Registro',
                'post_type' => 'registro',
                'post_status' => $post_status
            ));
        } else {
            // Atualizar o post existente
            wp_update_post(array(
                'post_title' => $post_status === 'publish' ? 'Registro #' . $post_id : 'Pedido de Registro #' . $post_id,
                'ID' => $post_id,
                'post_status' => $post_status
            ));
        }

        if ( $post_id ) {

            // Atualizar o meta do post
            update_post_meta( $post_id, 'registro_museu_id', intval($_POST['registro_museu_id']) );
            update_post_meta( $post_id, 'aderir_sbm', isset($_POST['aderir_sbm']) && $_POST['aderir_sbm'] ? 1 : 0 );
            update_post_meta( $post_id, 'justificativa_rejeite_texto', sanitize_text_field($_POST['justificativa_rejeite_texto']) );

            $attachment_fields = array(
                'cnpj',
                'instrumento_criacao',
                'cpf_rg',
                'documentos_identidade',
                'termo_solicitacao',
                'certificado_registro',
                'justificativa_rejeite_arquivo',
            );

            foreach ($attachment_fields as $meta_key) {
                if ( isset($_POST[$meta_key]) && $_POST[$meta_key] ) {
                    $attachment_id = sanitize_text_field($_POST[$meta_key]);
                    update_post_meta($post_id, $meta_key, $attachment_id);

                    // Anexar o arquivo ao post de registro
                    wp_update_post(array(
                        'ID' => $attachment_id,
                        'post_parent' => $post_id
                    ));
                } else {
                    delete_post_meta($post_id, $meta_key);
                }
            }

            /**
             * Verifica se todos os campos obrigatórios foram preenchidos
             */
            if ( $post_status === 'publish' || $post_status === 'pending' || $post_status == 'private' ) {
                $required_fields = array(
                    'registro_museu_id',
                    'cnpj',
                    'instrumento_criacao',
                    'cpf_rg',
                    'documentos_identidade',
                    'termo_solicitacao'
                );
                $missing_required_fields = array();
                foreach ($required_fields as $field) {
                    if ( !get_post_meta($post_id, $field, true) )
                        $missing_required_fields[] = $field;
                }
                if ( count($missing_required_fields) ) {
                    $missing_required_fields = implode(',', $missing_required_fields);

                    // Retona o registro para o status de rascunho
                    wp_update_post(array(
                        'post_title' => 'Pedido de Registro #' . $post_id,
                        'ID' => $post_id,
                        'post_status' => 'private'
                    ));
                    wp_redirect( admin_url('admin.php?page=registro&post=' . $post_id . '&missing_required_fields=' . $missing_required_fields) );

                    exit;
                }
            }
        }

        if ( museusbr_user_is_gestor_or_parceiro() )
            wp_redirect(admin_url('edit.php?post_type=' . museusbr_get_museus_collection_post_type()));
        else
            wp_redirect(admin_url('admin.php?page=registro&post=' . $post_id));

        exit;
    }

    /**
     * Tratamento do lado do servidor para que as mídias enviadas
     * pela página de registro sejam anexadas ao post de registro
     */
    function handle_media_upload($upload) {

        if (!isset($_POST['post_id']))
            return $upload;

        $post_id = intval($_POST['post_id']);

        if (get_post_type($post_id) !== 'registro' || !isset($_POST['attachment_id']))
            return $upload;

        $attachment_id = $upload['attachment_id'];

        // Anexar o arquivo ao post de registro
        wp_update_post(array(
            'ID' => $attachment_id,
            'post_parent' => $post_id
        ));
    }

    /** 
     * Pré processa arquivos enviados para garantir que só sejam permitidos PDFs
     */
    function restrict_media_upload_to_pdf($file) {

        // Verifica se o post type é 'registro'
        if (isset($_POST['post_id']) && get_post_type($_POST['post_id']) === 'registro') {
            $filetype = wp_check_filetype($file['name']);
            $allowed = array('pdf' => 'application/pdf');

            if (!in_array($filetype['type'], $allowed))
                $file['error'] = 'Você só pode fazer o envio de arquivos em formato PDF. Por favor tente novamente.';
        }

        return $file;
    }

    /**
     * Renderiza um input de arquivo customizado
     */
    function render_file_input_field($slug, $label, $post_id, $is_editable = false, $is_missing = false) {
        $valor = esc_attr( get_post_meta($post_id, $slug, true) );
        $is_required = ( $slug !== 'justificativa_rejeite_arquivo' && $slug !== 'certificado_registro' );
    ?>
        <div class="field">
            <label class="label <?php echo ($is_missing ? 'might-have-text-warning' : ''); ?>">
                <?php echo $label . ( $is_required ? ' *' : '' ); ?>
            </label>
            <div class="control">
                <input type="hidden" <?php echo ( $is_required ? 'required' : '' ); ?> id="<?php echo $slug; ?>" name="<?php echo $slug; ?>" value="<?php echo $valor; ?>" />
                <div class="media">
                    <?php if ($valor) : ?>
                        <?php
                        $attachment_url = add_query_arg('file_id', $valor, get_stylesheet_directory_uri() . '/inc/registro-serve-file.php');
                        $attachment_title = get_the_title($valor);
                        ?>
                        <div class="media-content">
                            <div class="content attachment-edit-buttons">
                                <button 
                                        type="button"
                                        class="button file-preview-modal-trigger"
                                        data-target="file-preview-modal"
                                        data-file-field="<?php echo $label; ?>"
                                        data-file-url="<?php echo $attachment_url; ?>"
                                        title="<?php echo $attachment_title ? $attachment_title : $attachment_url; ?>">
                                    <span class="icon">
                                        <i>
                                            <svg width="18" xmlns="http://www.w3.org/2000/svg" height="18" viewBox="2885 -149.35 18 18" fill="none">
                                                <path d="m2898.013-149.35-3.532 3.526 1.076 1.073 2.456-2.452 3.838 3.831-6.035 6.026-2.548-2.544-1.074 1.073 3.622 3.617 8.184-8.172Zm-4.829 4.821-8.184 8.172 5.987 5.979 3.532-3.527-1.075-1.073-2.457 2.453-3.837-3.832 6.034-6.025 2.548 2.543 1.075-1.073Z" style="fill: rgb(0, 0, 0); fill-opacity: 1;" class="fills" data-testid="svg-path"/>
                                            </svg>
                                        </i>
                                    </span>
                                    <span>Ver arquivo</span>
                                </button>
                                <?php if ( $is_editable ) : ?>
                                    <button type="button" class="button is-danger is-light delete-file-button" data-target-label="<?php echo $label; ?>" data-target="<?php echo $slug; ?>" data-post-id="<?php echo $post_id; ?>">
                                        <span class="icon">
                                            <i>
                                                <svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24" fill="currentColor">
                                                    <path d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z" />
                                                </svg>
                                            </i>
                                        </span>
                                        <span>Deletar</span>
                                    </button>
                                <?php endif; ?>
                            </div>
                            <?php if ( $is_editable ) : ?>
                                <div class="attachment-edit-buttons">
                                    <div class="file <?php echo ($is_missing ? 'is-warning' : 'is-link'); ?> is-light">
                                        <label class="file-label" style="width: 100%">
                                            <input class="file-input" type="file" accept="application/pdf,.pdf" name="<?php echo $slug; ?>-file-input" data-target-label="<?php echo $label; ?>" data-target="<?php echo $slug; ?>" data-post-id="<?php echo $post_id; ?>" />
                                            <span class="file-cta">
                                                <span class="file-icon icon">
                                                    <i>
                                                        <svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24" fill="currentColor">
                                                            <path d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z" />
                                                        </svg>
                                                    </i>
                                                </span>
                                                <span class="file-label">Substituir arquivo... </span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                    <?php else: ?>
                        <?php if ( $is_editable ) : ?>
                            <div class="file is-boxed <?php echo ($is_missing ? 'is-warning' : 'is-link'); ?> is-light">
                                <label class="file-label" style="width: 100%">
                                    <input class="file-input" type="file" accept="application/pdf,.pdf" name="<?php echo $slug; ?>-file-input" data-target-label="<?php echo $label; ?>" data-target="<?php echo $slug; ?>" data-post-id="<?php echo $post_id; ?>" />
                                    <span class="file-cta">
                                        <span class="file-icon icon">
                                            <i>
                                                <svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24" fill="currentColor">
                                                    <path d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z" />
                                                </svg>
                                            </i>
                                        </span>
                                        <span class="file-label"> Escolha um arquivo... </span>
                                    </span>
                                </label>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php
    }

    /**
     * Renderiza um modal com prévia de arquivos
     */
    function render_file_preview_modal() {
    ?>
        <div id="file-preview-modal" class="modal">
            <div class="modal-background"></div>
            <div class="modal-card">
                <header class="modal-card-head">
                    <h2 class="modal-card-title">Pré-visualização</h2>
                    <button class="delete" aria-label="close"></button>
                </header>
                <section class="modal-card-body">
                    Nenhum arquivo selecionado...
                </section>
            </div>
        </div>
        <?php
    }


    /**
     * Renderiza um modal com prévia do log
     */
    function render_log_preview_modal($post_id) {
        ?>
            <div id="log-preview-modal" class="modal">
                <div class="modal-background"></div>
                <div class="modal-card">
                    <header class="modal-card-head">
                        <h2 class="modal-card-title">Histórico de status</h2>
                        <button class="delete" aria-label="close"></button>
                    </header>
                    <section class="modal-card-body">
                        <h3>Acompanhe o fluxo do pedido de registro passo-a-passo.</h3>
                        <br>
                    <?php
                        // Obter os comentários associados ao post
                        $comments = get_comments(array(
                            'post_id' => $post_id,
                            'status' => 'approve',
                            'order' => 'ASC',
                            'meta_query' => array(
                                array(
                                    'key' => 'registro-log',
                                    'compare' => 'EXISTS'
                                )
                            )
                        ));

                        if ($comments) : ?>
                            <ul class="log-list">
                                <?php foreach ($comments as $comment) : $log_status = get_comment_meta($comment->comment_ID, 'registro-log', true); ?>
                                    <li class="log-item">
                                        <div class="log-status">
                                            <span class="status-bullet has-background-<?php echo $this->get_color_class_by_status($log_status); ?>"></span>
                                            <p class="is-uppercase has-text-<?php echo $this->get_color_class_by_status($log_status); ?>"><?php echo $this->get_label_by_status($log_status) ?></p>
                                        </div>
                                        <p class="log-date">
                                            <em><?php echo get_comment_date('', $comment); ?></em>
                                        </p>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else : ?>
                            <p><em>Nenhum histórico guardado ainda neste pedido de registro.</em></p>
                        <?php endif;
                    ?>
                    </section>
                </div>
            </div>
        <?php
    }    

    /**
     * Salva o comentário do pedido de registro
     */
    function save_comment() {
        check_ajax_referer('save_comment_nonce', '_wpnonce');

        $comment_data = array(
            'comment_post_ID' => intval($_POST['post_id']),
            'comment_content' => sanitize_text_field($_POST['comment']),
            'user_id' => get_current_user_id(),
            'comment_author' => wp_get_current_user()->display_name,
            'comment_author_email' => wp_get_current_user()->user_email,
        );

        $comment_id = wp_insert_comment($comment_data);

        if ($comment_id) {
            $comment = get_comment($comment_id);
            wp_send_json_success(array(
                'author' => get_comment_author($comment),
                'date' => get_comment_date('', $comment),
                'content' => get_comment_text($comment),
            ));
        } else {
            wp_send_json_error(array('message' => 'Erro ao salvar o comentário.'));
        }
    }

    /**
     * Toda vez que o status é atualizado, criamos um comentário.
     *
     * @param string  $new_status Novo status.
     * @param string  $old_status Status anterior.
     * @param WP_Post $post       Objeto de post.
     */
    function update_registro_logs( $new_status, $old_status, $post ) {
        if ( $old_status == $new_status || $post->post_type !== 'registro' )
            return;

        $comment_data = array(
            'comment_post_ID' => $post->ID,
            'comment_content' => sprintf( 'Status alterado de %s para %s.', $old_status, $new_status ),
            'user_id' => get_current_user_id(),
            'comment_author' => wp_get_current_user()->display_name,
            'comment_author_email' => wp_get_current_user()->user_email,
            'comment_meta' => array(
                'registro-log' => $new_status
            )
        );

        $comment_id = wp_insert_comment($comment_data);

        if ( !$comment_id )
            error_log( 'Erro ao salvar o comentário de registro.' );
    }

    /**
     * Processa a remoção de um anexo inicializada pelo botão de remover
     */
    function remove_attachment() {
        check_ajax_referer('remove_attachment_nonce', '_wpnonce');

        if (!isset($_POST['post_id']) || !isset($_POST['attachment_id']) || !isset($_POST['field_id']))
            wp_send_json_error(array('message' => 'Dados faltantes ao tentar remover anexo.'));

        $post_id = intval($_POST['post_id']);
        $field_id = sanitize_text_field($_POST['field_id']);
        $attachment_id = intval($_POST['attachment_id']);

        delete_post_meta($post_id, $field_id);

        // Desanexar o arquivo do post de registro
        wp_update_post(array(
            'ID' => $attachment_id,
            'post_parent' => 0
        ));

        if (wp_delete_attachment($attachment_id, true)) {
            wp_send_json_success();
        } else {
            wp_send_json_error(array('message' => 'Erro ao excluir o anexo.'));
        }
    }

    /**
     * Adiciona a coluna do museu na tabela do registro
     */
    function set_custom_registro_column($columns) {

        $comments_column = $columns['comments'];
        unset($columns['comments']);

        $date_column = $columns['date'];
        unset($columns['date']);

        $author_column = $columns['author'];
        unset($columns['author']);

        $columns['registro_museu'] = 'Museu';
        $columns['author'] = $author_column;
        $columns['comments'] = $comments_column;
        $columns['date'] = $date_column;

        return $columns;
    }

    /**
     * Adiciona a coluna do registro na tabela do museu
     */
    function set_custom_museu_registro_column($columns) {

        $date_column = $columns['date'];
        unset($columns['date']);

        $columns['museu_registro'] = 'Registro';
        $columns['date'] = $date_column;

        return $columns;
    }


    /**
     * Renderiza o conteúdo da coluna do museu na tabela de registros
     */
    function registro_column($column_name, $post_id) {
        global $post;

        if ($column_name != 'registro_museu')
            return;

        $museu_id = get_post_meta($post_id, 'registro_museu_id', true);

        if ($museu_id) : $museu = get_post($museu_id); ?>
            <p><strong><?php echo esc_html($museu->post_title); ?></strong></p>
        <?php else : ?>
            <p><em>Nenhum museu cadastrado vinculado.</em></p>
        <?php endif;
    }

    /**
     * Renderiza o conteúdo da coluna do registro na tabela de museus
     */
    function museu_registro_column($column_name, $post_id) {
        global $post;

        if ($column_name != 'museu_registro')
            return;

        $query_params = array(
            'post_type' => 'registro',
            'post_status' => array( 'trash', 'pending', 'draft', 'private', 'publish', 'auto-draft' ),
            'posts_per_page' => 1,
            'orderby' => 'modified',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key' => 'registro_museu_id',
                    'value' => $post_id,
                    'compare' => '='
                )
            )
        );

        $query = new WP_Query($query_params);
        $meta_results = $query->posts;

        if ( !empty($meta_results) ) {
        ?>
            <div class="registro-info" style="display: flex; gap: 0.75em 1.5em; flex-wrap: wrap; align-items: center;">
                <?php
                foreach ($meta_results as $registro) {
                    echo '<div class="registro-info-main">';
                    echo 'Solicitação #' . $registro->ID;

                    $registro_status = get_post_status($registro);
                    echo '<p style="text-transform: uppercase; color: ' . $this->get_color_by_status($registro_status) . ';">' . $this->get_label_by_status($registro_status) . '</p>';
                    echo '</div>';
                    
                    switch ($registro_status) {
                        case 'draft':
                            echo '<a class="wp-button button" style="white-space: wrap" href="' . admin_url('admin.php?page=registro&post=' . $registro->ID) . '">Continuar preenchendo</a>';
                            break;

                        case 'private':
                            echo '<a class="wp-button button" style="white-space: wrap" href="' . admin_url('admin.php?page=registro&post=' . $registro->ID) . '">Atualizar pedido</a>';
                            break;

                        case 'publish':
                            $certificado_registro = get_post_meta($registro->ID, 'certificado_registro', true);

                            if ( $certificado_registro )
                                echo '<a class="wp-button button" style="white-space: wrap" download="' . add_query_arg('file_id', $certificado_registro, get_stylesheet_directory_uri() . '/inc/registro-serve-file.php') . '" href="' . add_query_arg('file_id', $certificado_registro, get_stylesheet_directory_uri() . '/inc/registro-serve-file.php') . '">Baixar certificado</a>';
                            else
                                echo '<p><em>Certificado ainda não disponível.</em></p>';
                            break;
                        case 'trash':
                            $justificativa_rejeite_arquivo = get_post_meta($registro->ID, 'justificativa_rejeite_arquivo', true);

                            if ( $justificativa_rejeite_arquivo )
                                echo '<a class="wp-button button" style="white-space: wrap" download="' . add_query_arg('file_id', $justificativa_rejeite_arquivo, get_stylesheet_directory_uri() . '/inc/registro-serve-file.php') . '" href="' . add_query_arg('file_id', $justificativa_rejeite_arquivo, get_stylesheet_directory_uri() . '/inc/registro-serve-file.php') . '">Justificativa do refeite</a>';
                            else
                                echo '<p><em>Justificativa de arquivamento ainda não disponível.</em></p>';
                            break;
                        default:
                            break;
                    }
                }
                ?>
            </div>
        <?php

        } else {

            $has_all_required_for_registro = $this->check_if_has_all_required_for_registro($post_id);

            if ( $has_all_required_for_registro ) : ?>
                <a class="wp-button button wp-button-has-icon" href="<?php echo admin_url('admin.php?page=registro&museu_id=' . $post_id); ?>">
                    <svg width="22" xmlns="http://www.w3.org/2000/svg" height="22" viewBox="1032 -188 22 22" fill="none">
                        <path d="M1034.359-186.821c0-.654-.526-1.179-1.18-1.179-.653 0-1.179.525-1.179 1.179v19.642c0 .654.526 1.179 1.179 1.179.654 0 1.18-.525 1.18-1.179v-16.293l.614-.118c.821-.157 1.548-.418 2.172-.643.182-.064.349-.128.511-.182.747-.256 1.494-.447 2.516-.447 1.258 0 2.113.295 3.12.653l.025.01c1.027.363 2.201.781 3.887.781 1.587 0 2.639-.334 4.447-.963v11.648l-.467.162c-2.039.708-2.712.943-3.98.943-1.263 0-2.118-.294-3.125-.653l-.03-.01c-1.022-.363-2.201-.776-3.882-.776-.825 0-1.523.099-2.157.246a1.183 1.183 0 0 0-.88 1.419 1.182 1.182 0 0 0 1.42.879 7.064 7.064 0 0 1 1.617-.181c1.258 0 2.113.294 3.12.653l.025.009c1.027.364 2.202.781 3.887.781 1.69 0 2.772-.378 4.806-1.09l1.199-.417.796-.27v-16.648l-1.548.536c-.477.162-.894.309-1.263.437-2.039.707-2.712.942-3.98.942-1.263 0-2.118-.294-3.125-.653l-.03-.009c-1.022-.364-2.201-.776-3.882-.776-1.366 0-2.383.27-3.273.569-.241.084-.457.162-.668.236-.585.211-1.081.388-1.705.506l-.167.029v-.952Z" style="fill: rgb(0, 0, 0); fill-opacity: 1;" class="fills"/>
                    </svg>
                    <span>Iniciar registro</span>
                </a>
            <?php else : ?>
                <p><em>Para iniciar o registro, certifique-se de estão preenchidos:</em></p>
                <details style="margin-top: -0.5rem;">
                    <summary> Campos obrigatórios </summary>
                    <ul style="list-style: disc; margin-left: 1rem; column-count: 2;">
                        <li><em>Tipo do Museu</em></li>
                        <li><em>E-mail para divulgação</em></li>
                        <li><em>Telefone para divulgação</em></li>
                        <li><em>Esfera administrativa</em></li>
                        <li><em>CNPJ</em></li>
                        <li><em>Temática do Museu</em></li>
                        <li><em>Com relação à PROPRIEDADE do acervo</em></li>
                        <li><em>Status do Museu</em></li>
                    </ul>
                </details>
            <?php endif;
        
        }
        
        return;
    }

    /**
     * Remove a opção de edição rápida para o post type 'registro'
     */
    function remove_quick_edit($actions, $post) {
        if ($post->post_type == 'registro') {
            unset($actions['inline hide-if-no-js']);
            unset($actions['view']);
        }

        return $actions;
    }

    /**
     * Obter rótulo amigável associado ao status
     */
    function get_label_by_status($post_status_slug) {
        switch ($post_status_slug) {
            case 'auto-draft':
                return 'Iniciado';
            case 'draft':
                return 'Em preenchimento';
            case 'pending':
                return 'Em análise';
            case 'private':
                return 'Pendente';
            case 'publish':
                return 'Aprovado';
            case 'trash':
                return 'Rejeitado';
            default:
                return 'Iniciado';
        }
    }

    /**
     * Obter classe de cor associada ao status
     */
    function get_color_class_by_status($post_status_slug) {
        switch ($post_status_slug) {
            case 'auto-draft':
            case 'draft':
                return 'info';
            case 'pending':
                return 'link';
            case 'private':
                return 'warning';
            case 'publish':
                return 'success';
            case 'trash':
                return 'danger';
            default:
                return 'info';
        }
    }

    /**
     * Obter cor associada ao status
     */
    function get_color_by_status($post_status_slug) {
        switch ($post_status_slug) {
            case 'auto-draft':
            case 'draft':
                return 'rgb(115, 130, 130)';
            case 'pending':
                return 'rgb(0, 122, 184)';
            case 'private':
                return 'rgb(160, 101, 34)';
            case 'publish':
                return 'rgb(26, 116, 92)';
            case 'trash':
                return 'rgb(155, 54, 54)';
            default:
                return 'rgb(115, 130, 130)';
        }
    }

    /**
     * Checa se todos os metadados esperados para o registro estão preenchidos
     */
    function check_if_has_all_required_for_registro($post_id) {
        global $wpdb;

        // Lista de IDs de metadados
        $meta_keys = [
            274,  // 1.2 - Tipo do Museu
            1213, // 1.8 - E-mail para divulgação
            1219, // 1.10 - Telefone
            259,  // 4.1 - Esfera administrativa
            1430, // 4.2 - CNPJ
            279,  // 7.3 - Temática do Museu
            2150, // 8.2 - Com relação à PROPRIEDADE do acervo
            269,  // 9.1 Status do Museu    
        ];

        $meta_keys_placeholder = implode(',', array_fill(0, count($meta_keys), '%s'));
        $query = $wpdb->prepare(
            "SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id = %d AND meta_key IN ($meta_keys_placeholder)",
            array_merge([$post_id], $meta_keys)
        );

        $results = $wpdb->get_results($query, OBJECT_K);
        
        foreach ( $meta_keys as $meta_key ) {
            if (
                !isset( $results[$meta_key] ) ||
                empty( $results[$meta_key]->meta_value ) &&
                $results[$meta_key]->meta_value !== '0'
            )
                return false; // Retorna falso se qualquer valor for vazio ou não definido
        }
    
        return true; // Todos os meta_keys possuem valores
    }

    /**
     * Função que executa o cron a cada dia
     */
    public static function museusbr_registro_cron_exec() {

        /** 
         * Consulta todos os registros Pendentes há mais de 6 meses
         */
        $args_pendentes = array(
            'post_type' => 'registro',
            'post_status' => 'private',
            'posts_per_page' => -1,
            'date_query' => array(
                array(
                    'column' => 'post_modified_gmt',
                    'before' => '6 months ago',
                ),
            )
        );
        $registros_pendentes = get_posts($args_pendentes);

        /**
         * Rejeita os registros Pendentes modificados há mais de 6 meses.
         */
        if ($registros_pendentes) {
            foreach ($registros_pendentes as $registro_pendente) {
                wp_update_post(array(
                    'ID' => $registro_pendente->ID,
                    'post_status' => 'trash'
                ));
                error_log('Registro ' . $registro_pendente->ID . ' rejeitado por estar pendente há mais de 6 meses.');
            }
        }

        /** 
         * Consulta todos os registros Aprovados modificados há mais de 5 anos
         */
        $args_aprovados = array(
            'post_type' => 'registro',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'date_query' => array(
                array(
                    'column' => 'post_modified_gmt',
                    'before' => '5 years ago',
                ),
            )
        );
        $registros_aprovados = get_posts($args_aprovados);

        /**
         * Rejeita\ os registros Pendentes há mais de 6 meses.
         */
        if ($registros_aprovados) {
            foreach ($registros_aprovados as $registro_aprovado) {
                wp_update_post(array(
                    'ID' => $registro_aprovado->ID,
                    'post_status' => 'private'
                ));

                /**
                 * Envia um email notificando o author do pedido de registro
                 */
                add_filter('wp_mail_content_type', array( $this, 'museusbr_set_html_mail_content_type' ));

                $author_name = get_the_author_meta('display_name', $registro_aprovado->post_author);
                $author_email = get_the_author_meta('user_email', $registro_aprovado->post_author);

                $to = $author_email ? sprintf('%s <%s>', $author_name, $author_email) : get_bloginfo('admin_email');

                $subject = 'MuseusBr: Registro retornado ao status pendente';
                $body = 'Caro gestor,<br>

                        O registro de seu museu foi classificado como "pendente" por não ter sido atualizado nos últimos 5 anos.<br>
                        Este é um procedimento padrão, visto que o Registro de Museus tem validade de 5 anos.<br>
                        
                        Assim, recomendamos a atualização periódica dos seus dados para garantir a validade do Registro. <br>
                        Atualize as informações do pedido <a target="_blank" name="MuseusBr: Edição de Registro" href="' . get_edit_post_link($registro_aprovado->ID) . '">neste link</a> ou acesse a Plataforma MuseusBr para editá-lo.<br>
                        
                        Atenciosamente, Equipe CPAI - MuseusBr';
                $headers = array('Content-Type: text/html; charset=UTF-8');

                wp_mail($to, $subject, $body, $headers);

                remove_filter('wp_mail_content_type', array($this, 'museusbr_set_html_mail_content_type'));

                error_log('Registro ' . $registro_pendente->ID . ' retornado ao status pendente por não ser atualizado há mais de 6 meses.');
            }
        }
    }

    /**
     * Define o tipo de conteúdo do email como HTML
     */
    public static function museusbr_set_html_mail_content_type() {
        return 'text/html';
    }

    /**
     * Adiciona a ação de enviar emails para diferentes condições do registro
     */
    function private_registro_send_email($post_id, $post, $old_status){

        add_filter('wp_mail_content_type', array($this,'museusbr_set_html_mail_content_type'));

        $author = $post->post_author;

        $author_name = get_the_author_meta('display_name', $author);
        $author_email = get_the_author_meta('user_email', $author);

        $to = $author_email ? sprintf('%s <%s>', $author_name, $author_email) : get_bloginfo('admin_email');

        $subject =  'MuseusBr: Registro pendente de documentos';
        $body = 'Caro gestor,<br>

            Uma solicitação de Registro de seu museu encontra-se pendente por falta de documentos necessários à análise ou incorreção de algum dado.<br>
            Visite a <a target="_blank" name="MuseusBr: Edição de Registro" href="' . get_edit_post_link($post_id) . '">a página do pedido</a> na Plataforma MuseusBr para ver as observações relacionadas feitas pela equipe e completar sua solicitação.<br>
            
            Atenciosamente, Equipe CPAI - MuseusBr';
        $headers = array('Content-Type: text/html; charset=UTF-8');

        wp_mail($to, $subject, $body, $headers);

        error_log('Email enviado para ' . $to . ' sobre o registro ' . $post_id . ' estar pendente de documentos.');

        remove_filter('wp_mail_content_type', array($this,'museusbr_set_html_mail_content_type'));
    }

}
