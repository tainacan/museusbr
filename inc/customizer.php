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

    $metadata_sections_options_pontos_de_memoria = [];
    
    if ( get_theme_mod( 'museusbr_pontos_de_memoria_collection', 179824 ) !== 0 ) {
        $metadata_sections_repository = \Tainacan\Repositories\Metadata_Sections::get_instance();
        $metadata_sections = $metadata_sections_repository->fetch_by_collection(
            tainacan_get_collection(
                [
                    'collection_id' => get_theme_mod( 'museusbr_pontos_de_memoria_collection', 179824 )
                ]
            )
        );

        foreach($metadata_sections as $metadata_section) {
            $metadata_sections_options_pontos_de_memoria[$metadata_section->get_id()] = $metadata_section->get_name();
        }
    }

    $metadata_repository = \Tainacan\Repositories\Metadata::get_instance();
    $metadata_options = [];
    $metadata = $metadata_repository->fetch(array( 
        'meta_query' => array(
            array(
            'key'     => 'collection_id',
            'value'   => get_theme_mod( 'museusbr_collection', 208 ),
            'compare' => '='
        ))))->posts;

    foreach($metadata as $metadatum) {
        $metadata_options[$metadatum->ID] = $metadatum->post_title;
    }
    
    
    $museusbr_extra_options = [
        'title' => 'Configurações do MuseusBr',
        'container' => [ 'priority' => 8 ],
        'options' => [
            'museusbr_list_section_options' => [
                'type' => 'ct-options',
                'setting' => [ 'transport' => 'postMessage' ],
                'inner-options' => [
                    'museusbr_collection' => [
                        'label' =>  'Coleção de Museus',
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
                        'label' =>  'Seção de metadados da localização',
                        'type' => 'ct-select',
                        'value' => 0,
                        'view' => 'text',
                        'design' => 'inline',
                        'sync' => '',
                        'choices' => blocksy_ordered_keys(
                            $metadata_sections_options
                        )
                    ],
                    'museusbr_internal_data_for_banner_metadata_section' => [
                        'label' =>  'Seção de metadados internos para banner',
                        'type' => 'ct-select',
                        'value' => 0,
                        'view' => 'text',
                        'design' => 'inline',
                        'sync' => '',
                        'choices' => blocksy_ordered_keys(
                            $metadata_sections_options
                        )
                    ],
                    'museusbr_codigo_identificador_ibram_metadatum' => [
                        'label' => 'Metadado do código identificador do IBRAM',
                        'type' => 'ct-select',
                        'value' => museusbr_get_codigo_identificador_ibram_metadatum_id(),
                        'view' => 'text',
                        'design' => 'inline',
                        'sync' => '',
                        'choices' => blocksy_ordered_keys(
                            $metadata_options
                        )
                    ],
                    blocksy_rand_md5() => [
                        'type' => 'ct-divider',
                    ],
                    'museusbr_registro_termo_solicitacao_link' => [
                        'label' =>  'Link para solicitação de termo de registro',
                        'type' => 'text',
                        'value' => '',
                        'design' => 'inline',
                        'sync' => ''
                    ],
                    blocksy_rand_md5() => [
                        'type' => 'ct-divider',
                    ],
                    'museusbr_pontos_de_memoria_collection' => [
                        'label' =>  'Coleção de Pontos de Memória',
                        'type' => 'ct-select',
                        'value' => 179824,
                        'view' => 'text',
                        'design' => 'inline',
                        'sync' => '',
                        'choices' => blocksy_ordered_keys(
                            $collections_options
                        )
                    ],
                    'museusbr_localization_metadata_section_pontos_de_memoria' => [
                        'label' =>  'Seção de metadados da localização dos pontos de memória',
                        'type' => 'ct-select',
                        'value' => 0,
                        'view' => 'text',
                        'design' => 'inline',
                        'sync' => '',
                        'choices' => blocksy_ordered_keys(
                            $metadata_sections_options_pontos_de_memoria
                        )
                    ],
                    'museusbr_formularios_de_visitacao_collection' => [
                        'label' =>  'Coleção de Formulário de Visitação',
                        'type' => 'ct-select',
                        'value' => 219857,
                        'view' => 'text',
                        'design' => 'inline',
                        'sync' => '',
                        'choices' => blocksy_ordered_keys(
                            $collections_options
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

    if ( ( str_contains($prefix, museusbr_get_museus_collection_post_type() ) || str_contains($prefix, museusbr_get_pontos_de_memoria_collection_post_type() )) && str_contains($prefix, '_item_single') ) {

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

function museusbr_add_search_bar_on_title_banners() {
    echo do_shortcode( "[wp_reusable_render id='215351']" );
}
add_action( 'blocksy:hero:title:before', 'museusbr_add_search_bar_on_title_banners' );
