<?php
/**
 * Admin Theme Page with settings.
 *
 * @since 0.1.0
 * 
 */

if ( ! class_exists( 'MuseusBR_Museus_Page' ) ) {
	/**
	 * Admin Page Settings.
	 *
	 * @since 0.1.0
	 */
	class MuseusBR_Museus_Page {

		private $page_slug = 'museusbr-museus';

		/**
		 * Constructor. Instantiate the object.
		 *
		 * @since 0.1.0
		 * @source tag 1
		 */
		public function __construct() {
			// Adiciona página e subpáginas ao menu
			add_action( 'admin_menu', array( $this, 'add_page_to_menu' ) );
			// Carrega scripts e css necessários
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts') );
			// Preparar ajax para página de configuração das funcionadades
			add_action( 'wp_ajax_theme_toggle_feature', array( $this, 'theme_toggle_feature_callback') );
		}

		/**
		 * Returns the page slug
		 * @var string
		 * 
		 * @since 0.1.0
		 */
		public function get_page_slug() {
			return $this->page_slug;
		}

		/**
		 * Adds the submenu with the page
		 *
		 * @since 0.1.0
		 *
		 * @return void
		 */
		function add_page_to_menu() {
			add_users_page(
				__( 'Museus Cadastrados', 'govbr'),
				'Museus Cadastrados',
				'manage_options',
				$this->get_page_slug() . '.php',
				array( $this, 'museusbr_museus_page' )
			); 
		}

		/**
		 * Enqueues necessary scripts and styles
		 *
		 * @since 0.1.0
		 *
		 * @return void
		 */
		function enqueue_admin_scripts() {
			//wp_enqueue_style( 'gov-br-admin-settings', get_template_directory_uri() . '/assets/css/style-admin-settings.css', array(), wp_get_theme()->get( 'Version' ) );
			//wp_enqueue_script( 'gov-br-admin-settings', get_template_directory_uri() . '/assets/js/admin-settings-features.js', array('jquery'), wp_get_theme()->get( 'Version' ) );
		}

		/**
		 * The theme settings page
		 *
		 * @since 0.1.0
		 *
		 * @return void
		 */
		function museusbr_museus_page() {
		?>

			<div class="govbr-theme-settings-header">
				<div class="govbr-theme-settings-title-section">
					<h1>Museus cadastrados</h1>
				</div>
				<p>Meus museus</p>
				</nav>
			</div>
			
			<hr class="wp-header-end">

			<div class="wrap govbr-theme-settings-body hide-if-no-js">
			
		<?php 
				global $current_user;
				
				wp_get_current_user();
				
				$author_query = array(
					'posts_per_page' => '-1',
					'author' => $current_user->ID
				);
				$author_posts = new WP_Query($author_query);
				
				while( $author_posts->have_posts() ) : $author_posts->the_post(); ?>

					<a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a>       
				
				<?php endwhile; ?>

			</div>

		<?php
		}

	
	}
}