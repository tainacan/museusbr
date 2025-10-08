<?php

/**
 * Este arquivo contém alterações feitas ao fluxo de edição e à interface administrativa
 * da coleção de museus.
 */

 /**
 * Altera o link de edição de posts da coleção dos museus
 */
function museusbr_museus_collection_edit_post_link( $url, $post_ID) {

	if ( get_post_type($post_ID) == museusbr_get_museus_collection_post_type() ) 
		$url = admin_url( 'admin.php?page=tainacan_admin#/collections/' . museusbr_get_museus_collection_id() . '/items/' . $post_ID . '/edit' );

    return $url;
}
add_filter( 'get_edit_post_link', 'museusbr_museus_collection_edit_post_link', 10, 2 );

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

/**
 * Define o tipo de conteúdo do email como HTML
 */
function museusbr_set_html_mail_content_type() {
    return 'text/html';
}

/** Envia email para o Administrador quando um cadastro e Museu é colocado em Análise */
function museusbr_send_email_on_item_pending($post_id, $post, $old_status) {
    if ( $old_status !== 'pending' ) {

        add_filter('wp_mail_content_type', 'museusbr_set_html_mail_content_type');

        $to = get_bloginfo('admin_email');

        $subject =  'MuseusBr: Uma solicitação de cadastro de Museu está aguardando análise';
        $body = 'Caro Administrador,<br>

            Uma solicitação de cadastro de museu foi iniciada e está aguardando análise. Visite <a target="_blank" name="MuseusBr: Edição de Cadastro" href="' . get_edit_post_link($post_id) . '">a página do cadastro</a> na Plataforma MuseusBr para realizar a revisão.<br>
            
            Atenciosamente, Equipe CPAI - MuseusBr';
        $headers = array('Content-Type: text/html; charset=UTF-8');

        wp_mail($to, $subject, $body, $headers);

        error_log('Email enviado para ' . $to . ' sobre o cadastro ' . $post_id . ' estar aguardando análise.');

        remove_filter('wp_mail_content_type', 'museusbr_set_html_mail_content_type');
    }
}
add_action('pending_' . museusbr_get_museus_collection_post_type(), 'museusbr_send_email_on_item_pending', 10, 3);

/** Envia um email para o Gestor do Museu quando o cadastro do museu dele é publicado */
function museusbr_send_email_on_item_publish($post_id, $post, $old_status) {
    if ( $old_status !== 'publish' ) {

        add_filter('wp_mail_content_type', 'museusbr_set_html_mail_content_type');

        $author = $post->post_author;

        $author_name = get_the_author_meta('display_name', $author);
        $author_email = get_the_author_meta('user_email', $author);

        $to = $author_email ? sprintf('%s <%s>', $author_name, $author_email) : get_bloginfo('admin_email');

        $subject =  'A solicitação de cadastro do seu Museu foi aprovada e a página foi publicada';
        $body = 'Caro gestor,<br>

            Uma solicitação de cadastro de museu iniciada por você estava em análise e acaba de ser aprovada. A página do museu foi publicada. Você e os demais visitantes do site podem ver os dados em <a name="Página pública do museu" href="' . get_post_permalink( $post_id ) . '">página pública do Museu</a>.<br>
            Caso queira, visite <a target="_blank" name="MuseusBr: Edição de Cadastro" href="' . get_edit_post_link($post_id) . '">a página do cadastro</a> na Plataforma MuseusBr para realizar alterações.<br>
            
            Atenciosamente, Equipe CPAI - MuseusBr';
        $headers = array('Content-Type: text/html; charset=UTF-8');

        wp_mail($to, $subject, $body, $headers);

        error_log('Email enviado para ' . $to . ' sobre o cadastro ' . $post_id . ' ter sido publicado.');

        remove_filter('wp_mail_content_type', 'museusbr_set_html_mail_content_type');
    }
}
add_action('publish_' . museusbr_get_museus_collection_post_type(), 'museusbr_send_email_on_item_publish', 10, 3);