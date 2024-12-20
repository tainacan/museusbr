<?php

if ( function_exists('register_block_style') ) {
    /**
     * Registra estilos de blocos do tema
     *
     * @since 0.0.1
     *
     * @return void
     */
    function museusbr_register_block_styles() {

        register_block_style(
            'core/column',
            array(
                'name'  => 'museusbr-clickable-card',
                'label' =>  'Cartão clicável',
                'is_default' => false            
            )
        );

        register_block_style(
            'core/columns',
            array(
                'name'  => 'museusbr-clickable-card',
                'label' =>  'Cartão clicável',
                'is_default' => false            
            )
        );

        register_block_style(
            'core/group',
            array(
                'name'  => 'museusbr-clickable-card',
                'label' =>  'Cartão clicável',
                'is_default' => false            
            )
        );

    }
    add_action('init', 'museusbr_register_block_styles');
}
