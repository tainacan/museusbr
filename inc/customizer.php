<?php

/**
 * Adiciona opções para o menu Personalizar.
 *
 */
function museusbr_options_panel($options) {

    $collections_repository = \Tainacan\Repositories\Collections::get_instance();
    $collections_options = [];
    $collections = $collections_repository->fetch()->posts;

    foreach($collections as $collection) {
        $collections_options[$collection->ID] = $collection->post_title;
    }

    $metadata_sections_options = [];
    
    if ( get_theme_mod( 'museusbr_collection', 0 ) !== 0 ) {
        $metadata_sections_repository = \Tainacan\Repositories\Metadata_Sections::get_instance();
        $metadata_sections = $metadata_sections_repository->fetch_by_collection(
            tainacan_get_collection(
                [
                    'collection_id' => get_theme_mod( 'museusbr_collection', 0 )
                ]
            )
        );

        foreach($metadata_sections as $metadata_section) {
            $metadata_sections_options[$metadata_section->get_id()] = $metadata_section->get_name();
        }
    }
    
    $museusbr_extra_options = [
        'title' => __('Configurações do MuseusBR', 'museusbr'),
        'container' => [ 'priority' => 8 ],
        'options' => [
            'museusbr_list_section_options' => [
                'type' => 'ct-options',
                'setting' => [ 'transport' => 'postMessage' ],
                'inner-options' => [
                    'museusbr_collection' => [
                        'label' => __( 'Coleção de Museus', 'museusbr' ),
                        'type' => 'ct-select',
                        'value' => 208,
                        'view' => 'text',
                        'design' => 'inline',
                        'sync' => '',
                        'choices' => blocksy_ordered_keys(
                            $collections_options
                        )
                    ],
                    'museusbr_localization_metadata_section' => [
                        'label' => __( 'Seção de metadados da localização', 'museusbr' ),
                        'type' => 'ct-select',
                        'value' => 0,
                        'view' => 'text',
                        'design' => 'inline',
                        'sync' => '',
                        'choices' => blocksy_ordered_keys(
                            $metadata_sections_options
                        )
                    ],
                ]
            ]
        ]
    ];

    $options['museusbr_list'] = $museusbr_extra_options;

    return $options;
}
add_filter( 'blocksy_extensions_customizer_options', 'museusbr_options_panel', 10, 1 );

function tainacan_blocksy_render_document_instead_of_featured_image() {
    $prefix = blocksy_manager()->screen->get_prefix();

    if ( str_contains($prefix, 'tnc_col_') && str_contains($prefix, '_item_single') ) {

        $page_hero_section_style = get_theme_mod($prefix . '_hero_section' , get_theme_mod($prefix . '_page_header_background_style', 'type-1'));
        if ( $page_hero_section_style === 'type-2' ) {

            add_filter( 'blocksy:hero:type-2:image:attachment_id', function() {
                if ( tainacan_get_the_document_type() === 'attachment' );
                    return tainacan_get_the_document_raw();
            }, 10 );
        }
    }
}
add_action( 'blocksy:hero:before', 'tainacan_blocksy_render_document_instead_of_featured_image');
