<?php
/**
 * Template para mostrar o Museu
 */

$prefix = blocksy_manager()->screen->get_prefix();

/**
 * Não mostra metadados e seções de metadados privados para usuários que não são gestores ou parceiros
 */
function museusbr_single_museu_pre_get_post( $query ) {
    if ( is_admin() )
        return;
    if ( in_array($query->query_vars['post_type'], ['tainacan-metadatum', 'tainacan-metasection']) ) {
        if ( museusbr_user_is_gestor_or_parceiro() ){
            $query->set( 'post_status', 'publish' );
        }
    }
}
add_action( 'pre_get_posts', 'museusbr_single_museu_pre_get_post' );

$localization_metadata_section = get_theme_mod( 'museusbr_localization_metadata_section', 0 );
$internal_data_for_banner_metadata_section = get_theme_mod( 'museusbr_internal_data_for_banner_metadata_section', 0 );

$page_structure_type = get_theme_mod( $prefix . '_page_structure_type', 'type-dam');
$template_columns_style = '';
$display_items_related_to_this = get_theme_mod( $prefix . '_display_items_related_to_this', 'no' ) === 'yes';
$metadata_list_structure_type = get_theme_mod($prefix . '_metadata_list_structure_type', 'metadata-type-1');

if ($page_structure_type == 'type-gm' || $page_structure_type == 'type-mg') {
    $column_documents_attachments_width = 60;
    $column_metadata_width = 40;

    $column_documents_attachments_width = intval(substr(get_theme_mod( $prefix . '_document_attachments_columns', '60%'), 0, -1));
    $column_metadata_width = 100 - $column_documents_attachments_width;

    if ($page_structure_type == 'type-gm') {
        $template_columns_style = 'grid-template-columns: ' . $column_documents_attachments_width . '% calc(' . $column_metadata_width . '% - 48px);';
    } else {
        $template_columns_style = 'grid-template-columns: ' . $column_metadata_width . '% calc(' . $column_documents_attachments_width . '% - 48px);';
    }
}

$item_id = get_the_ID();

if ( tainacan_has_document() && tainacan_get_the_document_type() === 'attachment' ) {
    add_filter( 'blocksy:hero:type-2:image:attachment_id', function() use($item_id) {
        return tainacan_get_the_document_raw($item_id);
    }, 10 );
}

$metadata_args = array(
    'display_slug_as_class' => true,
    'before' 				=> '<div class="tainacan-item-section__metadatum metadata-type-$type" id="$id">',
    'after' 				=> '</div>',
    'before_title' => '<h4 class="tainacan-metadata-label">',
    'after_title' => '</h4>',
    'before_value' => '<p class="tainacan-metadata-value">',
    'after_value' => '</p>',
    'exclude_title' => true
);

add_filter( 'tainacan-get-item-metadatum-as-html-before-value', function($metadatum_value_before, $item_metadatum) {
    
    // Metadatado do Valor da Entrada
    if ( $item_metadatum->get_metadatum()->get_id() == 1513 )
        $metadatum_value_before .= 'R$';

    // Metadados dos selos
    if ( $item_metadatum->get_metadatum()->get_metadata_section_id() == 127131 &&  $item_metadatum->get_metadatum()->get_metadata_type() == 'Tainacan\\Metadata_Types\\Taxonomy' ) {
        $values = is_array( $item_metadatum->get_value() ) ? $item_metadatum->get_value() : [ $item_metadatum->get_value() ];
        
        $metadatum_value_before .= '<div class="museu-selos">';
        foreach ($values as $value) {
            if ( $value instanceof Tainacan\Entities\Term ) {
                $metadatum_value_before .= '<a title="' . $value->get_description() . '" href="' . $value->get_url() . '"><img src="' . $value->get_header_image() . '" alt="' . $value->get_name() . '" style="width: 150px; height: auto;"></a>';
            }
        }
        $metadatum_value_before .= '</div><div class="screen-reader-text">';
    }

    return $metadatum_value_before;
}, 10, 2 );

add_filter( 'tainacan-get-item-metadatum-as-html-after-value', function($metadatum_value_after, $item_metadatum) {

    // Metadados dos selos
    if ( $item_metadatum->get_metadatum()->get_metadata_section_id() == 127131 &&  $item_metadatum->get_metadatum()->get_metadata_type() == 'Tainacan\\Metadata_Types\\Taxonomy' ) {
        $metadatum_value_after .= '</div>';
    }

    return $metadatum_value_after;
}, 10, 2 );

add_filter('tainacan-get-metadata-section-as-html-before-name--index-1', function($before, $metadata_section) {
    $output = str_replace('<input', '<input checked="checked"', $before);
    return $output;
}, 10, 2);


add_filter('tainacan-get-metadata-section-as-html-before-name', function($before, $metadata_section) {
    $output = str_replace('<h3', '<i style="float: left; font-size: 2.5rem; margin: 2px 0.5rem 2px 0.5rem;" class="' . get_post_meta($metadata_section->get_ID(), 'museusbr_metadata_section_icon', true) . '"></i><h3', $before);
    return $output;
}, 10, 2);


$sections_args = array(
    'metadata_sections__not_in' => [ $localization_metadata_section, $internal_data_for_banner_metadata_section, \Tainacan\Entities\Metadata_Section::$default_section_slug ],
    'before' => '',
    'after' => '',
    'before_name' => '<input name="tabs" type="radio" id="tab-section-$id" />
                <label for="tab-section-$id">
                    <h3 class="tainacan-single-item-section" id="metadata-section-$slug">',
    'after_name' => '</h3>
                </label>',
    'before_metadata_list' => '<section class="tainacan-item-section tainacan-item-section--metadata">' . do_action( 'tainacan-blocksy-single-item-metadata-begin' ) . '
            <div class="tainacan-item-section__metadata ' . $metadata_list_structure_type . '" aria-labelledby="metadata-section-$slug">',
    'after_metadata_list' => '</div>' . do_action( 'tainacan-blocksy-single-item-metadata-end' ) . '</section>',
    'metadata_list_args' => $metadata_args
);

do_action( 'tainacan-blocksy-single-item-top' ); 

do_action( 'tainacan-blocksy-single-item-after-title' );

?>

<div class="tainacan-item-section tainacan-item-section--metadata-sections">
    <h2 class="tainacan-single-item-section" id="tainacan-item-metadata-label">
        <?php echo esc_html( get_theme_mod($prefix . '_section_metadata_label', __( 'Navegue pelas informações', 'tainacan-blocksy' ) ) ); ?>
    </h2>
    <div class="metadata-section-layout--tabs">
        <?php tainacan_the_metadata_sections( $sections_args ); ?>
    </div>
</div>

<div class="tainacan-item-section tainacan-item-section--special-museusbr-gallery alignfull">
<?php

    tainacan_blocksy_get_template_part( 'template-parts/tainacan-item-single-document' );
    do_action( 'tainacan-blocksy-single-item-after-document' );  

    tainacan_blocksy_get_template_part( 'template-parts/tainacan-item-single-attachments' );
?>
</div>     
<div class="tainacan-item-section tainacan-item-section--special-museusbr-localization alignfull">
    <?php
    tainacan_the_metadata_sections( array(
        'metadata_section' => $localization_metadata_section,
        'metadata_list_args' => array(
            'display_slug_as_class' => true
        )
    ) );

    if ($display_items_related_to_this) {
        tainacan_blocksy_get_template_part( 'template-parts/tainacan-item-single-items-related-to-this' );
        do_action( 'tainacan-blocksy-single-item-after-items-related-to-this' );
    }
?>
</div> 
