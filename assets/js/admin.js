if (wp && wp.hooks) {
    function tainacanItemEditionItemLoaded (collection, item) {
 
        if ( collection.id == museusbr_theme.museus_collection_id) {
            tainacan_plugin.i18n.label_ready_to_create_item = 'Pronto para cadastrar este museu?';
            tainacan_plugin.i18n.instruction_create_item_select_status = 'Selecione um status para a visibilidade do museu no site. Você poderá alterar no futuro.';
            tainacan_plugin.i18n.helpers_label.items.document.description = 'Uma imagem represente o museu.';
            tainacan_plugin.i18n.info_edit_attachments = 'Adicione imagens ou vídeos que representem o museu. Você pode adicionar mais de um arquivo.';
            tainacan_plugin.i18n.title_create_item_collection = 'Cadastrar museu';
            tainacan_plugin.i18n.title_edit_item = 'Editar museu';
            tainacan_plugin.i18n.info_item_draft = 'Este museu está em rascunho e será visível apenas pelos editores com as permissões necessárias. Não é realizada nenhuma validação de campos obrigatórios neste estado.';
            tainacan_plugin.i18n.info_item_not_saved = 'Atenção, o museu ainda não foi salvo.';
            tainacan_plugin.i18n.info_item_private = 'Este museu está publicado de forma privada e será visível apenas para os editores com as permissões necessárias.';
            tainacan_plugin.i18n.info_item_publish = 'Este museu está publicada de forma pública e será visível para todos os visitantes do site.';
        }
        tainacan_plugin.i18n.label_create_new_term = 'Adicionar';
        tainacan_plugin.i18n.label_add_value = 'Adicionar';
        tainacan_plugin.i18n.instruction_click_error_to_go_to_metadata = 'Clique no erro para ir até o campo.';
        tainacan_plugin.i18n.label_all_terms = 'Todas as opções';
        tainacan_plugin.i18n.label_all_metadatum_values = 'Todas as opções';
        tainacan_plugin.i18n.info_no_terms_found = 'Nenhuma opção encontrada';
        tainacan_plugin.i18n.label_create_new_term = 'Adicionar';
        tainacan_plugin.i18n.label_root_terms = 'Opções iniciais';
        tainacan_plugin.i18n.label_children_terms = 'opções derivadas';
        tainacan_plugin.i18n.label_nothing_selected = 'Nada selecionado';
        tainacan_plugin.i18n.label_no_terms_selected = 'Nenhuma opção selecionada';
        tainacan_plugin.i18n.label_selected_terms = 'Opções selecionadas';
        tainacan_plugin.i18n.label_selected_metadatum_values = 'Opções selecionadas';
        tainacan_plugin.i18n.info_metadata_section_hidden_conditional = 'Área desabilidata devido a um valor selecionado anterioremente.';
    }
    wp.hooks.addAction('tainacan_item_edition_item_loaded', 'tainacan-hooks', tainacanItemEditionItemLoaded);
}