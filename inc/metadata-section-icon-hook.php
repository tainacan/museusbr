<?php

class MUSEUSBRMetadataSectionIconHook {
	use Singleton;

	public $icon_field = 'museusbr_metadata_section_icon';

	protected function init() {
		add_action( 'tainacan-register-admin-hooks', array( $this, 'register_hook' ) );
		add_action( 'tainacan-insert-tainacan-metasection', array( $this, 'save_data' ) );
		add_filter( 'tainacan-api-response-metadata-section-meta', array( $this, 'add_meta_to_response' ), 10, 2 );
	}

	function register_hook() {
		if ( function_exists( 'tainacan_register_admin_hook' ) )
			tainacan_register_admin_hook( 'metadataSection', array( $this, 'form' ) );
	}

	function save_data( $object ) {
		if ( !function_exists( 'tainacan_get_api_postdata' ) )
			return;
		
		$post = tainacan_get_api_postdata();

		if ( $object->can_edit() && isset( $post->{$this->icon_field} ))
			update_post_meta( $object->get_id(), $this->icon_field, $post->{$this->icon_field} );
	}

	function add_meta_to_response( $extra_meta, $request ) {
		$extra_meta = array(
			$this->icon_field,
		);
		return $extra_meta;
	}

	function form() {
		if ( !function_exists( 'tainacan_get_api_postdata' ) )
			return '';

		ob_start();
		?>
		<div class="tainacan-museusbr-metadata-section-icon"> 
			<div class="field tainacan-collection--section-header">
				<h4><?php _e( 'Opções do MuseusBR', 'museusbr' ); ?></h4>
				<hr>
			</div>
			<div class="field">
				<label class="label"><?php _e('Ícone da seção:', 'museusbr'); ?></label>
				<div class="control has-icons-right">
					<input
							class="input"
							type="text"
							placeholder="<?php _e('las la-university', 'museusbr'); ?>"
							name="<?php echo $this->icon_field; ?>"
							oninput="
								var icon = event.target.nextElementSibling.children[0];
								icon.className = event.target.value;
							">
					<span class="icon is-right">
						<i id="museusbr-metadata-section-icon" class="las"></i>
					</span>
				</div>
				<p class="help"><?php _e('Digite as classes necessárias para formar o ícone desejado. Mais informações ', 'museusbr'); ?><a href="https://icons8.com/line-awesome" target="_blank"><?php _e('na documentação da LineAwesome', 'museusbr'); ?></a>.</p>
			</div>
		</div>
		<?php
        
		return ob_get_clean();
	}
}

MUSEUSBRMetadataSectionIconHook::get_instance();

