<?php
/**
 * Tour Operator FAQ - Template Parts.
 *
 * Registers the FAQ pattern and creates the FAQ template part post if it
 * doesn't exist, similar to how WordPress core handles default template parts.
 *
 * The `faq` template part area itself is registered by Tour Operator core.
 *
 * @package TO_FAQ
 * @since   0.1.0
 */

namespace lsx\to_faq;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Template_Parts
 *
 * The FAQ pattern and template part depend on the Yoast SEO FAQ block
 * (`yoast/faq-block`) and are only registered while Yoast SEO is active.
 *
 * @since 0.1.0
 */
class Template_Parts {

	/**
	 * Initialize the class by registering patterns and template parts.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_patterns' ], 10 );
		add_action( 'init', [ $this, 'create_template_parts' ], 20 );
	}

	/**
	 * Checks whether Yoast SEO is active.
	 *
	 * @since 0.1.0
	 * @return bool
	 */
	public static function is_yoast_active() {
		return defined( 'WPSEO_VERSION' );
	}

	/**
	 * Registers the block patterns called by our template parts.
	 *
	 * Patterns are loaded from the plugin's patterns/ directory. Unlike themes,
	 * plugins don't have their patterns/ directory registered automatically.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register_patterns() {
		if ( ! self::is_yoast_active() ) {
			return;
		}

		$file = LSX_TO_FAQ_PATH . 'patterns/faq.php';

		if ( ! file_exists( $file ) ) {
			return;
		}

		ob_start();
		include $file;
		$content = ob_get_clean();

		register_block_pattern(
			'to-faq/faq',
			[
				'title'       => __( 'FAQ', 'to-faq' ),
				'description' => __( 'A heading followed by a Yoast FAQ block with structured data.', 'to-faq' ),
				'categories'  => [ 'text' ],
				'keywords'    => [ 'faq', 'questions', 'yoast' ],
				'blockTypes'  => [ 'core/template-part/faq' ],
				'content'     => $content,
			]
		);
	}

	/**
	 * Creates template part posts if they don't exist.
	 *
	 * Template parts are created as wp_template_part posts with the content
	 * from the parts/ directory. This approach ensures they're available in
	 * the editor and can be managed like other template parts.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function create_template_parts() {
		/**
		 * Define template parts with metadata.
		 *
		 * Each template part requires:
		 * - title: Translatable display name
		 * - description: Translatable description of purpose
		 * - area: Template part area (header, footer, uncategorized, or a custom area)
		 */
		$template_parts = [];

		if ( self::is_yoast_active() ) {
			$template_parts['faq'] = [
				'title'       => __( 'FAQ', 'to-faq' ),
				'description' => __( 'Frequently asked questions section using the Yoast FAQ block.', 'to-faq' ),
				'area'        => 'faq',
			];
		}

		/**
		 * Filters the template parts to be created.
		 *
		 * Each slug must have a matching parts/{slug}.html file in this plugin.
		 *
		 * @since 0.1.0
		 *
		 * @param array $template_parts Array of template part configurations.
		 */
		$template_parts = apply_filters( 'lsx_to_faq_template_parts', $template_parts );

		$theme = get_stylesheet();

		foreach ( $template_parts as $slug => $args ) {
			$this->maybe_create_template_part( $slug, $args, $theme );
		}
	}

	/**
	 * Creates a template part post if it doesn't exist.
	 *
	 * @since 0.1.0
	 *
	 * @param string $slug  Template part slug.
	 * @param array  $args  Template part arguments.
	 * @param string $theme Theme slug.
	 * @return void
	 */
	protected function maybe_create_template_part( $slug, $args, $theme ) {
		// Get template content from file.
		$file = LSX_TO_FAQ_PATH . 'parts/' . $slug . '.html';

		// Check if template part file exists.
		if ( ! file_exists( $file ) ) {
			return;
		}

		// Read template content.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$content = file_get_contents( $file );

		// Check if template part already exists.
		// Note: We must use tax_query WITHOUT the 'name' parameter because
		// WordPress ignores tax_query when 'name' is present in the query args.
		$query = new \WP_Query(
			[
				'post_type'      => 'wp_template_part',
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'no_found_rows'  => true,
				'fields'         => 'ids',
				'tax_query'      => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					[
						'taxonomy' => 'wp_theme',
						'field'    => 'slug',
						'terms'    => $theme,
					],
				],
			]
		);

		// Filter by slug manually since we can't use 'name' with tax_query.
		$existing = array_filter(
			$query->posts,
			function ( $post_id ) use ( $slug ) {
				$post = get_post( $post_id );
				return $post && $post->post_name === $slug;
			}
		);

		// If template part doesn't exist, create it.
		if ( empty( $existing ) ) {
			$template_part_id = wp_insert_post(
				[
					'post_type'    => 'wp_template_part',
					'post_status'  => 'publish',
					'post_title'   => $args['title'],
					'post_excerpt' => $args['description'] ?? '',
					'post_name'    => $slug,
					'post_content' => $content,
					'meta_input'   => [
						'origin' => 'plugin',
					],
				]
			);

			if ( ! is_wp_error( $template_part_id ) ) {
				// Set theme taxonomy term.
				wp_set_post_terms( $template_part_id, [ $theme ], 'wp_theme' );

				// Set area taxonomy term.
				if ( isset( $args['area'] ) ) {
					wp_set_post_terms( $template_part_id, [ $args['area'] ], 'wp_template_part_area' );
				}
			}
		}
	}
}
