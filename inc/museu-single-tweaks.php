<?php

/**
 * Alterações que impactam o template de página de museu (museu-single-page.php)
 */

/**
 * Adiciona Thumbnail do item na página do museu e modifica outros metadados
 */
function museusbr_museu_single_page_hero_custom_meta_before() {

	if ( get_post_type() == museusbr_get_museus_collection_post_type() || get_post_type() == museusbr_get_pontos_de_memoria_collection_post_type() ) {
		
		$item = tainacan_get_item();

		if ($item instanceof \Tainacan\Entities\Item) {

			function museusbr_single_museu_banner_pre_get_post( $query ) {
				if ( is_admin() )
					return;
				if ( in_array($query->query_vars['post_type'], ['tainacan-metadatum', 'tainacan-metasection']) ) {
					if ( museusbr_user_is_gestor_or_parceiro() ) {
						$query->set( 'post_status', 'publish' );
					}
				}
			}
			add_action( 'pre_get_posts', 'museusbr_single_museu_banner_pre_get_post' );			

			// Coloca informação do autor do item e opção de reivindicar cadastro antes do metadado de Esfera Administrativa (ID 259)
			add_filter( 'tainacan-get-item-metadatum-as-html-after--id-259', function($after, $item_metadatum ) {

				$link_pagina_reinvindicacao = get_theme_mod( 'museusbr_reinvidicacao_cadastro_link', '' );

				$gestor_responsavel = '<div class="metadata-slug-gestor-responsavel tainacan-item-section__metadatum">
					<h4 class="tainacan-metadata-label">Cadastrado por</h4>' .
					(
						!empty( $link_pagina_reinvindicacao ) ? (
							'<p class="tainacan-metadata-value" style="margin-block-end: 4px;">' . get_the_author_meta( 'display_name', get_the_author_ID() ) . '</p>' . 
							'<div class="wp-block-buttons is-layout-flex wp-block-buttons-is-layout-flex" style="margin-block-end: var(--theme-content-spacing);">
								<div class="wp-block-button">
									<a class="wp-block-button__link wp-element-button" href="' . get_theme_mod( 'museusbr_reinvidicacao_cadastro_link', '') . '">
										<i class="las la-gavel" style="font-size: 1.5em;"></i>&nbsp;<span style="text-transform: capitalize;">Reivindicar cadastro</span>
									</a>
								</div>
							</div>'
						) : '<p class="tainacan-metadata-value" >' . get_author_name() . '</p>'
					) .
				'</div>';

				return $after . $gestor_responsavel;
			}, 10, 2 );

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
			
				<?php
				$extra_metadata_from_banner_section = get_theme_mod( 'museusbr_internal_data_for_banner_metadata_section', 0 );

				if ( $extra_metadata_from_banner_section ) : ?>
					
					<div class="museu-item-other-metadata">
						<?php
							$sections_args = array(
								'metadata_section' => $extra_metadata_from_banner_section,
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
				<?php endif; ?>
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

	if ( get_post_type() == museusbr_get_museus_collection_post_type() ) {
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
							<span class="navigator-text">Informações</span>
						</a>
					</li>
					<li id="tainacan-item-documents-label-nav">
						<a href="#tainacan-item-documents-label">
							<span class="navigator-icon">
								<i class="las la-image"></i>
							</span>
							<span class="navigator-text">Galeria de Fotos</span>
						</a>
					</li>
					<li>
						<a href="#metadata-section-localizacao">
							<span class="navigator-icon">
								<i class="las la-map"></i>
							</span>
							<span class="navigator-text">Localização</span>
						</a>
					</li>
				</ol>
			</nav>
		<?php
	} else if ( get_post_type() == museusbr_get_pontos_de_memoria_collection_post_type() ) {
		?>
		</div> <!-- Close the "museu-item-description-and-meta-wrapper" div -->
			</div> <!-- Close the "museu-item-extra-container" div -->
		<?php
	}
}
add_action('blocksy:hero:custom_meta:after', 'museusbr_museu_single_page_hero_custom_meta_after');

/**
 * Sobrescreve o conteúdo da single do museu e dos pontos de memória
 */
function museusbr_museu_single_page_content( $content ) {

	if ( ! is_singular( museusbr_get_museus_collection_post_type() ) && ! is_singular( museusbr_get_pontos_de_memoria_collection_post_type() ) )
		return $content;
	
	ob_start();
	include( get_stylesheet_directory() . '/tainacan/museu-single-page.php' );
	$new_content = ob_get_contents();
	ob_end_clean();

	return $new_content;
}
add_filter( 'the_content', 'museusbr_museu_single_page_content', 12, 1);