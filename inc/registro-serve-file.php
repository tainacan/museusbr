<?php

/**
 * Este arquivo serve para servir arquivos anexados à posts do tipo registro.
 * Ela contém a lógica para proteger o acesso aos arquivos, permitindo apenas
 * que o autor do post ou administradores possam baixar os arquivos.
 */
require_once('../../../../wp-load.php');

if (!is_user_logged_in()) {
    wp_redirect(home_url());
    exit;
}

$file_id = isset($_GET['file_id']) ? intval($_GET['file_id']) : 0;

// Checa se o post do anexo foi anexado à um post do tipo registro
$post_parent = get_post_parent( $file_id );
if ( !$post_parent )
    wp_die('Arquivo não anexado.');

$post_parent_type = get_post_type( $post_parent );
if ( $post_parent_type !== 'registro' )
    wp_die('Arquivo não anexado a um registro');

$file = get_post_meta( $file_id, '_wp_attached_file', true );   
$file_path = WP_CONTENT_DIR . '/uploads/museusbr-registro/' . $post_parent->ID . '/' . $file;
echo $file_path;
if (!$file_path || !file_exists($file_path)) {
    wp_die('Arquivo não encontrado.');
}

$current_user = wp_get_current_user();
$file_owner_id = get_post_field('post_author', $post_parent);

if ($current_user->ID != $file_owner_id && !current_user_can('edit_registros')) {
    wp_die('Você não tem permissões para ler este arquivo.');
}

header('Content-Type: ' . get_post_mime_type($file_id));
header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
readfile($file_path);

exit;