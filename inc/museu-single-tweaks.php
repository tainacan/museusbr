<?php

/**
 * Alterações que impactam o template de página de museu (museu-single-page.php)
 */

/**
 * Adiciona Thumbnail do item na página do museu
 */
function museusbr_museu_single_page_hero_custom_meta_before() {

	if ( get_post_type() == museusbr_get_collection_post_type() ) {
		
		$item = tainacan_get_item();

		if ($item instanceof \Tainacan\Entities\Item) {

			function museusbr_single_museu_banner_pre_get_post( $query ) {
				if ( is_admin() )
					return;
				if ( in_array($query->query_vars['post_type'], ['tainacan-metadatum', 'tainacan-metasection']) ) {
					if ( museusbr_user_is_gestor() ) {
						$query->set( 'post_status', 'publish' );
					}
				}
			}
			add_action( 'pre_get_posts', 'museusbr_single_museu_banner_pre_get_post' );			

			add_filter( 'tainacan-get-item-metadatum-as-html-before-value', function($metadatum_value_before, $item_metadatum) {

				$metadatum_id = $item_metadatum->get_metadatum()->get_id();

				// Metadados de Email
				if ( $metadatum_id == 1213 || $metadatum_id == 1216 )
					$metadatum_value_before .= '<i class="las la-envelope"></i>';

				// Metadados de Telefone
				if ( $metadatum_id == 1219 || $metadatum_id == 1222 )
					$metadatum_value_before .= '<i class="las la-phone"></i>';
				
				// Metadado de Contato Extra
				if ( $metadatum_id == 14629 )
					$metadatum_value_before .= '<i class="las la-id-card"></i>';
			
				return $metadatum_value_before;
			}, 10, 2 );

            add_filter( 'tainacan-get-item-metadatum-as-html-before-value', function($metadatum_value_before, $item_metadatum) {

                // Metadatado do REGISTRADO
                if ( $item_metadatum->get_metadatum()->get_id() == 15161 && $item_metadatum->get_value() == 'Museu Registrado' )
                    $metadatum_value_before = '<img src="' . get_stylesheet_directory_uri() . '/assets/images/selo_museu_registrado.png" alt="Museu registrado" style="width: 200px; height: auto;">';

                // Metadatado do CADASTRADO
                if ( $item_metadatum->get_metadatum()->get_id() == 40026 && $item_metadatum->get_value() == 'Museu Cadastrado' )
                    $metadatum_value_before = '<img src="' . get_stylesheet_directory_uri() . '/assets/images/selo_museu_cadastrado.png" alt="Museu cadastrado" style="width: 200px; height: auto;">';

                return $metadatum_value_before;
            }, 10, 2 );

			?>
			<div class="museu-item-thumbnail-container"> 
				<?php the_post_thumbnail('tainacan-medium', array('class' => 'museu-item-thumbnail')); ?>
			
				<div class="museu-item-other-metadata">
					<?php
						$sections_args = array(
							'metadata_section' => get_theme_mod( 'museusbr_internal_data_for_banner_metadata_section', 0 ),
							'hide_name'	=> true,
							'before' => '',
							'after' => '',
							'metadata_list_args' => array(
								'exclude_core' => true,
								'display_slug_as_class' => true
							)
						);

						tainacan_the_metadata_sections($sections_args);
					?>
				</div>
			</div>

			<div class="museu-item-extra-container">
				<div class="museu-item-other-metadata">
					<?php
						$sections_args = array(
							'metadata_section' => \Tainacan\Entities\Metadata_Section::$default_section_slug,
							'hide_name'	=> true,
							'before' => '',
							'after' => '',
							'metadata_list_args' => array(
								'exclude_core' => true,
								'display_slug_as_class' => true
							)
						);

						tainacan_the_metadata_sections($sections_args);
					?>
				</div>
				<div class="museu-item-description-and-meta-wrapper"> <!-- Is closed in the museusbr_museu_single_page_hero_custom_meta_after function -->
					<div class="museu-item-description"><?php echo $item->get_description(); ?></div>
				<?php
		}

	}
}
add_action('blocksy:hero:custom_meta:before', 'museusbr_museu_single_page_hero_custom_meta_before');


/**
 * Adiciona navegação entre seções no cabeçalho
 */
function museusbr_museu_single_page_hero_custom_meta_after() {

	if ( get_post_type() == museusbr_get_collection_post_type() ) {
		?>	
				</div> <!-- Close the "museu-item-description-and-meta-wrapper" div -->
			</div> <!-- Close the "museu-item-extra-container" div -->
			<nav class="museu-item-sections-navigator">
				<ol>
					<li>
						<a href="#tainacan-item-metadata-label">
							<span class="navigator-icon">
								<i class="las la-info-circle"></i>
							</span>
							<span class="navigator-text"><?php _e( 'Informações', 'museusbr'); ?></span>
						</a>
					</li>
					<li id="tainacan-item-documents-label-nav">
						<a href="#tainacan-item-documents-label">
							<span class="navigator-icon">
								<i class="las la-image"></i>
							</span>
							<span class="navigator-text"><?php _e( 'Galeria de Fotos', 'museusbr'); ?></span>
						</a>
					</li>
					<li>
						<a href="#metadata-section-localizacao">
							<span class="navigator-icon">
								<i class="las la-map"></i>
							</span>
							<span class="navigator-text"><?php _e( 'Localização', 'museusbr'); ?></span>
						</a>
					</li>
				</ol>
			</nav>
		<?php
	}
}
add_action('blocksy:hero:custom_meta:after', 'museusbr_museu_single_page_hero_custom_meta_after');

/**
 * Sobrescreve o conteúdo da single do museu
 */
function museusbr_museu_single_page_content( $content ) {

	if ( ! is_singular( museusbr_get_collection_post_type() ) )
		return $content;
	
	ob_start();
	include( get_stylesheet_directory() . '/tainacan/museu-single-page.php' );
	$new_content = ob_get_contents();
	ob_end_clean();

	return $new_content;
}
add_filter( 'the_content', 'museusbr_museu_single_page_content', 12, 1);