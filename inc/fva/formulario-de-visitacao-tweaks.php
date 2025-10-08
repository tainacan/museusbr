<?php
/**
 * Funções do tema MuseusBr relacionados à funcionalidade do Formulário de Visitação
 */
/**
 * Pré-preenche o metadado do Código de Itentificação do Ibram com o ID do item
 */
function museusbr_preset_formulario_de_visitacao($item) {

	if ( MUSEUSBR_ENABLE_FORMULARIO_VISITACAO && $item instanceof \Tainacan\Entities\Item ) {
		$collection_id = $item->get_collection_id();

	 	if ( $collection_id == museusbr_get_formularios_de_visitacao_collection_id() ) {

            // Se tiver sido passado 'from-museu' na url, o metadado do Museu deve vir pré-preenchido
            $referer_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

			if ( !empty($referer_url) ) {
				
				$query_string = parse_url($referer_url, PHP_URL_QUERY);
				parse_str($query_string, $params);
				$from_museu = isset($params['from-museu']) ? $params['from-museu'] : false;

                if ( $from_museu && is_numeric($from_museu) ) {
                
                    try {
                        $museu_metadatum = new \Tainacan\Entities\Metadatum( museusbr_get_formulario_de_visitacao_museu_metadatum_id() );

                        if ( $museu_metadatum instanceof \Tainacan\Entities\Metadatum ) {
                            
                            $new_museu_item_metadatum = new \Tainacan\Entities\Item_Metadata_Entity( $item, $museu_metadatum );
                    
                            if ( !$new_museu_item_metadatum->has_value() ) {
                                $new_museu_item_metadatum->set_value( $from_museu );
                    
                                if ( $new_museu_item_metadatum->validate() )
                                    \Tainacan\Repositories\Item_Metadata::get_instance()->insert( $new_museu_item_metadatum );
                            }
                        }
                    } catch (Exception $e) {
                        error_log('Erro ao tentar pré-preencher o metadado do Código de Identificação do Ibram: ' . $e->getMessage());
                    }
                }
            }

            // O metadado do ano deve vir pré-preenchido
            try {
                $ano_metadatum = new \Tainacan\Entities\Metadatum( museusbr_get_formulario_de_visitacao_ano_metadatum_id() );
                $ano_atual = museusbr_get_formulario_de_visitacao_ano_atual();

                $query_string = parse_url($referer_url, PHP_URL_QUERY);
				parse_str($query_string, $params);
				$from_ano = isset($params['from-ano']) ? $params['from-ano'] : false;
                
                if ( $ano_metadatum instanceof \Tainacan\Entities\Metadatum && ( $ano_atual !== 'none' || $from_ano ) ) {
                    
                    $new_ano_item_metadatum = new \Tainacan\Entities\Item_Metadata_Entity( $item, $ano_metadatum );
            
                    if ( !$new_ano_item_metadatum->has_value() ) {
                        $new_ano_item_metadatum->set_value( $from_ano ? $from_ano : $ano_atual );
            
                        if ( $new_ano_item_metadatum->validate() )
                            \Tainacan\Repositories\Item_Metadata::get_instance()->insert( $new_ano_item_metadatum );
                    }
                }
            } catch (Exception $e) {
                error_log('Erro ao tentar pré-preencher o metadado do Ano: ' . $e->getMessage());
            }

            // O título do item deve vir pré-preenchido
            try {
                $titulo_metadatum = new \Tainacan\Entities\Metadatum( museusbr_get_formulario_de_visitacao_titulo_metadatum_id() );

                if ( $titulo_metadatum instanceof \Tainacan\Entities\Metadatum ) {
                    
                    $new_titulo_item_metadatum = new \Tainacan\Entities\Item_Metadata_Entity( $item, $titulo_metadatum );
            
                    if ( !$new_titulo_item_metadatum->has_value() ) {
                        $new_titulo_item_metadatum->set_value( 'FVA ' . ( isset($from_ano) && $from_ano ? $from_ano : ( $ano_atual && $ano_atual !== 'none' ? $ano_atual : '' ) ) );
            
                        if ( $new_titulo_item_metadatum->validate() )
                            \Tainacan\Repositories\Item_Metadata::get_instance()->insert( $new_titulo_item_metadatum );
                    }
                }
            } catch (Exception $e) {
                error_log('Erro ao tentar pré-preencher o metadado do Título: ' . $e->getMessage());
            }
		}
	}
};
add_action('tainacan-insert', 'museusbr_preset_formulario_de_visitacao', 10, 1);

/**
 * Oculta alguns metadados do formulário de visitação
 */
function museusbr_customize_collection_admin_css() {

    if (  !museusbr_user_is_gestor() ) {
        return;
    }

	$fva_collection_id = museusbr_get_formularios_de_visitacao_collection_id();
    $fva_titulo_metadatum_id = museusbr_get_formulario_de_visitacao_titulo_metadatum_id();
    $fva_museu_metadatum_id = museusbr_get_formulario_de_visitacao_museu_metadatum_id();
    $fva_ano_metadatum_id = museusbr_get_formulario_de_visitacao_ano_metadatum_id();
	
	$css = '
    #collection-page-container[collection-id="' . $fva_collection_id . '"]>.tainacan-form>.columns>.column:first-of-type .tainacan-metadatum-id--' . $fva_titulo_metadatum_id . ' {
        display: none;
        visibility: hidden;
    }
    #collection-page-container[collection-id="' . $fva_collection_id . '"]>.tainacan-form>.columns>.column:first-of-type .tainacan-metadatum-id--' . $fva_museu_metadatum_id . ' .tabs,
    #collection-page-container[collection-id="' . $fva_collection_id . '"]>.tainacan-form>.columns>.column:first-of-type .tainacan-metadatum-id--' . $fva_museu_metadatum_id . ' .relationship-value-button--edit,
    #collection-page-container[collection-id="' . $fva_collection_id . '"]>.tainacan-form>.columns>.column:first-of-type .tainacan-metadatum-id--' . $fva_museu_metadatum_id . ' .relationship-value-button--remove {
        display: none;
        visibility: hidden;
    }
    #collection-page-container[collection-id="' . $fva_collection_id . '"]>.tainacan-form>.columns>.column:first-of-type .tainacan-metadatum-id--' . $fva_ano_metadatum_id . ' .select:not(.is-multiple):not(.is-loading)::after {
        display: none;
        visibility: hidden;
    }
    #collection-page-container[collection-id="' . $fva_collection_id . '"]>.tainacan-form>.columns>.column:first-of-type .tainacan-metadatum-id--' . $fva_titulo_metadatum_id . ',
    #collection-page-container[collection-id="' . $fva_collection_id . '"]>.tainacan-form>.columns>.column:first-of-type .tainacan-metadatum-id--' . $fva_museu_metadatum_id . ',
    #collection-page-container[collection-id="' . $fva_collection_id . '"]>.tainacan-form>.columns>.column:first-of-type .tainacan-metadatum-id--' . $fva_ano_metadatum_id . ' {
        pointer-events: none;
        --tainacan-input-border-color: transparent;
        --tainacan-input-background-color: var(--tainacan-gray0);
    }
    ';

    echo '<style type="text/css" id="tainacan-museusbr-fva-style">' . sprintf( $css ) . '</style>';
}
add_action('admin_head', 'museusbr_customize_collection_admin_css');
