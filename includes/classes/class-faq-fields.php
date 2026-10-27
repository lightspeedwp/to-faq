<?php
/**
 * FAQ Fields.
 *
 * Adds a repeatable, sortable FAQs meta box (a question and a formatted
 * answer, which can include images) to posts, the Tour Operator post types
 * and the Tour Operator taxonomies, and a "TO FAQs" variation of the Yoast
 * FAQ block (`yoast/faq-block`) that renders those fields on the front end.
 * On a taxonomy archive, a "TO FAQs" block outside a Query Loop shows the
 * term's FAQs.
 *
 * Yoast SEO only builds its FAQPage schema from FAQ blocks it finds in the
 * post content, so the field questions are also passed into Yoast's schema
 * context. That way Yoast keeps generating and maintaining the structured
 * data, and this class only supplies the questions.
 *
 * Requires Yoast SEO (for the FAQ block and schema) and Tour Operator. The
 * meta box uses the CMB2 library bundled with Tour Operator, registered the
 * same way as the Tour Operator meta boxes.
 *
 * @package TO_FAQ
 * @since   0.1.0
 */

namespace lsx\to_faq;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Faq_Fields
 *
 * @since 0.1.0
 */
class Faq_Fields {

	/**
	 * The Yoast FAQ block name.
	 *
	 * @var string
	 */
	const BLOCK = 'yoast/faq-block';

	/**
	 * The block attribute that marks a Yoast FAQ block as the "TO FAQs" variation.
	 *
	 * @var string
	 */
	const ATTRIBUTE = 'lsxToPostFaqs';

	/**
	 * The CMB2 group field ID, also used as the meta key.
	 *
	 * @var string
	 */
	const FIELD = 'lsx_to_faqs';

	/**
	 * The CMB2 meta box ID.
	 *
	 * @var string
	 */
	const METABOX = 'lsx_to_faq_metabox';

	/**
	 * The CMB2 term meta box ID.
	 *
	 * @var string
	 */
	const TERM_METABOX = 'lsx_to_faq_term_metabox';

	/**
	 * Script handle for the editor variation.
	 *
	 * @var string
	 */
	const SCRIPT = 'lsx-to-faq-fields';

	/**
	 * Yoast's FAQ block editor script handle.
	 *
	 * @var string
	 */
	const YOAST_SCRIPT = 'yoast-seo-faq-block';

	/**
	 * How many core/post-template blocks are rendering, so blocks in a Query
	 * Loop's items can be recognised.
	 *
	 * @var int
	 */
	protected $post_template_depth = 0;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		add_action( 'cmb2_admin_init', [ $this, 'register_fields' ], 100 );
		add_filter( 'register_block_type_args', [ $this, 'extend_block_type' ], 10, 2 );
		add_filter( 'render_block_' . self::BLOCK, [ $this, 'render_block' ], 10, 3 );
		add_filter( 'render_block_data', [ $this, 'enter_post_template' ] );
		add_filter( 'render_block_core/post-template', [ $this, 'leave_post_template' ] );
		add_filter( 'wpseo_schema_graph_pieces', [ $this, 'add_questions_to_schema' ], 10, 2 );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ], 5 );
	}

	/**
	 * Checks whether the plugins this feature depends on are active.
	 *
	 * The FAQ fields only feed the Yoast FAQ block, so Yoast SEO is required.
	 * CMB2 is bundled with Tour Operator, and the meta box is only registered
	 * when CMB2 fires `cmb2_admin_init`.
	 *
	 * @since 0.1.0
	 * @return bool
	 */
	public static function is_supported() {
		return defined( 'WPSEO_VERSION' );
	}

	/**
	 * Returns the post types that get the FAQ fields.
	 *
	 * @since 0.1.0
	 * @return string[]
	 */
	public function get_post_types() {
		$post_types = [ 'post' ];

		if ( function_exists( 'lsx_to_get_post_types' ) ) {
			$post_types = array_merge( $post_types, array_keys( (array) lsx_to_get_post_types() ) );
		}

		/**
		 * Filters the post types that get the FAQ fields.
		 *
		 * Other plugins can add their post types here, for example:
		 *
		 *     add_filter( 'lsx_to_faq_post_types', function ( $post_types ) {
		 *         $post_types[] = 'page';
		 *         return $post_types;
		 *     } );
		 *
		 * @since 0.1.0
		 *
		 * @param string[] $post_types Post type slugs. Default `post` plus the
		 *                             Tour Operator post types from `lsx_to_get_post_types()`.
		 */
		$post_types = apply_filters( 'lsx_to_faq_post_types', $post_types );

		return array_values( array_unique( array_filter( (array) $post_types, 'is_string' ) ) );
	}

	/**
	 * Returns the taxonomies that get the FAQ fields.
	 *
	 * @since 0.1.0
	 * @return string[]
	 */
	public function get_taxonomies() {
		$taxonomies = [ 'accommodation-brand', 'accommodation-type', 'travel-style', 'continent' ];

		/**
		 * Filters the taxonomies that get the FAQ fields.
		 *
		 * @since 0.1.0
		 *
		 * @param string[] $taxonomies Taxonomy slugs. Default `accommodation-brand`,
		 *                             `accommodation-type`, `travel-style` and `continent`.
		 */
		$taxonomies = apply_filters( 'lsx_to_faq_taxonomies', $taxonomies );

		return array_values( array_unique( array_filter( (array) $taxonomies, 'is_string' ) ) );
	}

	/**
	 * Registers the FAQs meta boxes.
	 *
	 * Uses CMB2 in the same way as the Tour Operator meta boxes
	 * (`lsx\admin\Setup::register_cmb2_fields()`). The answer is a WYSIWYG
	 * field like the itinerary description, so it supports basic formatting and
	 * images through Add Media. Rows can be reordered with the up and down
	 * arrows.
	 *
	 * Terms get the same fields, saved as term meta under the same key. They
	 * are only shown on the Edit term screen: the Add term form is submitted
	 * with Ajax, which doesn't save the WYSIWYG answers.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register_fields() {
		// CMB2 is bundled with Tour Operator, so it may not be loaded.
		if ( ! function_exists( 'new_cmb2_box' ) ) {
			return;
		}

		$post_types = $this->get_post_types();

		if ( ! empty( $post_types ) ) {
			$this->add_fields(
				new_cmb2_box(
					[
						'id'           => self::METABOX,
						'title'        => esc_html__( 'FAQs', 'to-faq' ),
						'object_types' => $post_types,
						'context'      => 'normal',
						'priority'     => 'high',
						'show_names'   => true,
					]
				)
			);
		}

		$taxonomies = $this->get_taxonomies();

		if ( ! empty( $taxonomies ) ) {
			$this->add_fields(
				new_cmb2_box(
					[
						'id'               => self::TERM_METABOX,
						'title'            => esc_html__( 'FAQs', 'to-faq' ),
						'object_types'     => [ 'term' ],
						'taxonomies'       => $taxonomies,
						'new_term_section' => false,
						'show_names'       => true,
					]
				)
			);
		}
	}

	/**
	 * Adds the repeatable FAQ group to a meta box.
	 *
	 * @since 0.1.0
	 *
	 * @param \CMB2 $cmb The meta box.
	 * @return void
	 */
	protected function add_fields( $cmb ) {
		$group_id = $cmb->add_field(
			[
				'id'         => self::FIELD,
				'name'       => '',
				'desc'       => esc_html__( 'Shown by the "TO FAQs" block, and output as FAQ structured data by Yoast SEO.', 'to-faq' ),
				'type'       => 'group',
				'repeatable' => true,
				'options'    => [
					// {#} is replaced by the row number.
					'group_title'   => esc_html__( 'Question {#}', 'to-faq' ),
					'add_button'    => esc_html__( 'Add question', 'to-faq' ),
					'remove_button' => esc_html__( 'Remove', 'to-faq' ),
					'sortable'      => true,
				],
			]
		);

		$cmb->add_group_field(
			$group_id,
			[
				'id'   => 'question',
				'name' => esc_html__( 'Question', 'to-faq' ),
				'type' => 'text',
			]
		);

		$cmb->add_group_field(
			$group_id,
			[
				'id'      => 'answer',
				'name'    => esc_html__( 'Answer', 'to-faq' ),
				'desc'    => esc_html__( 'Use Add Media to include an image.', 'to-faq' ),
				'type'    => 'wysiwyg',
				'options' => [
					'editor_height' => '100',
				],
			]
		);
	}

	/**
	 * Adds the variation attribute and post context to the Yoast FAQ block.
	 *
	 * @since 0.1.0
	 *
	 * @param array  $args       Block type arguments.
	 * @param string $block_type Block type name.
	 * @return array
	 */
	public function extend_block_type( $args, $block_type ) {
		if ( self::BLOCK !== $block_type ) {
			return $args;
		}

		$args['attributes'][ self::ATTRIBUTE ] = [
			'type'    => 'boolean',
			'default' => false,
		];

		// `query` is only provided by core/query, so it marks a block inside a Query Loop.
		$args['uses_context'] = array_values(
			array_unique( array_merge( $args['uses_context'] ?? [], [ 'postId', 'postType', 'query' ] ) )
		);

		return $args;
	}

	/**
	 * Renders the "TO FAQs" variation from the post's or term's FAQ fields.
	 *
	 * Standard Yoast FAQ blocks are returned unchanged. On an archive of one
	 * of the FAQ taxonomies, a variation outside a Query Loop shows the
	 * queried term's FAQs. The variation renders nothing when the post or
	 * term has no FAQs.
	 *
	 * The `postId` context can't tell the two apart: core's render_block()
	 * gives every top-level block the global post, which on an archive is the
	 * first post in the main query. Instead, a block counts as inside a Query
	 * Loop when it has the `query` context, which only core/query provides, or
	 * renders inside core/post-template. Post Template renders each item as a
	 * new block with only `postId` and `postType`, so its items don't get the
	 * `query` context.
	 *
	 * @since 0.1.0
	 *
	 * @param string         $block_content Rendered block content.
	 * @param array          $block         Parsed block.
	 * @param \WP_Block|null $instance      Block instance.
	 * @return string
	 */
	public function render_block( $block_content, $block, $instance = null ) {
		if ( empty( $block['attrs'][ self::ATTRIBUTE ] ) ) {
			return $block_content;
		}

		$post_id       = 0;
		$in_query_loop = false;
		if ( $instance instanceof \WP_Block ) {
			$post_id       = (int) ( $instance->context['postId'] ?? 0 );
			$in_query_loop = isset( $instance->context['query'] );
		}
		$in_query_loop = $in_query_loop || $this->post_template_depth > 0;

		if ( ! $in_query_loop && $this->is_faq_term_archive() ) {
			$questions = $this->get_term_questions( get_queried_object_id() );
		} else {
			$questions = $this->get_questions( $post_id ? $post_id : (int) get_the_ID() );
		}

		if ( empty( $questions ) ) {
			return '';
		}

		$classes = [ 'schema-faq', 'wp-block-yoast-faq-block', 'is-lsx-to-post-faqs' ];
		if ( ! empty( $block['attrs']['className'] ) ) {
			$classes[] = $block['attrs']['className'];
		}

		$sections = '';
		foreach ( $questions as $question ) {
			$sections .= sprintf(
				'<div class="schema-faq-section" id="%1$s"><strong class="schema-faq-question">%2$s</strong> <div class="schema-faq-answer">%3$s</div> </div> ',
				esc_attr( $question['id'] ),
				$question['question'],
				$question['answer']
			);
		}

		return sprintf(
			'<div class="%1$s">%2$s</div>',
			esc_attr( implode( ' ', $classes ) ),
			$sections
		);
	}

	/**
	 * Counts a core/post-template block that is about to render.
	 *
	 * @since 0.1.0
	 *
	 * @param array $parsed_block Parsed block.
	 * @return array Unchanged parsed block.
	 */
	public function enter_post_template( $parsed_block ) {
		if ( 'core/post-template' === ( $parsed_block['blockName'] ?? '' ) ) {
			++$this->post_template_depth;
		}

		return $parsed_block;
	}

	/**
	 * Counts a core/post-template block that has finished rendering.
	 *
	 * @since 0.1.0
	 *
	 * @param string $block_content Rendered block content.
	 * @return string Unchanged block content.
	 */
	public function leave_post_template( $block_content ) {
		$this->post_template_depth = max( 0, $this->post_template_depth - 1 );

		return $block_content;
	}

	/**
	 * Passes the field questions into Yoast's schema context.
	 *
	 * Yoast collects FAQ blocks from the post content only. When the variation
	 * is on the page (in the content, a template, or a template part), this
	 * replaces the variation's empty block in that collection with one holding
	 * the field questions, so Yoast's own FAQ schema piece outputs them.
	 *
	 * @since 0.1.0
	 *
	 * @param array  $pieces  Schema graph pieces.
	 * @param object $context Yoast meta tags context.
	 * @return array Unchanged schema graph pieces.
	 */
	public function add_questions_to_schema( $pieces, $context ) {
		if ( ! is_object( $context ) || empty( $context->indexable ) ) {
			return $pieces;
		}

		if ( 'term' === $context->indexable->object_type ) {
			return $this->add_term_questions_to_schema( $pieces, $context );
		}

		if ( 'post' !== $context->indexable->object_type ) {
			return $pieces;
		}

		$post_id = (int) $context->indexable->object_id;

		if ( ! is_singular() || get_queried_object_id() !== $post_id ) {
			return $pieces;
		}

		$blocks    = is_array( $context->blocks ) ? $context->blocks : [];
		$faq_block = $blocks[ self::BLOCK ] ?? [];

		$in_content = false;
		foreach ( $faq_block as $index => $block ) {
			if ( ! empty( $block['attrs'][ self::ATTRIBUTE ] ) ) {
				$in_content = true;
				unset( $faq_block[ $index ] );
			}
		}

		$on_page = $in_content || $this->template_has_variation();

		/**
		 * Filters whether the post's FAQ fields are added to the Yoast FAQ schema.
		 *
		 * Defaults to true only when the "TO FAQs" block is on the page, because
		 * FAQ structured data should match content that is visible to visitors.
		 *
		 * @since 0.1.0
		 *
		 * @param bool $on_page Whether the "TO FAQs" block is on the page.
		 * @param int  $post_id The post ID.
		 */
		if ( ! apply_filters( 'lsx_to_faq_add_schema', $on_page, $post_id ) ) {
			return $pieces;
		}

		$questions = $this->get_questions( $post_id );

		if ( ! empty( $questions ) ) {
			$faq_block[] = [
				'blockName'    => self::BLOCK,
				'attrs'        => [ 'questions' => $questions ],
				'innerBlocks'  => [],
				'innerHTML'    => '',
				'innerContent' => [],
			];
		}

		if ( empty( $faq_block ) ) {
			unset( $blocks[ self::BLOCK ] );
		} else {
			$blocks[ self::BLOCK ] = array_values( $faq_block );
		}

		$context->blocks = $blocks;

		return $pieces;
	}

	/**
	 * Passes the term's FAQ questions into Yoast's schema context.
	 *
	 * Yoast collects no blocks on term archives, so its FAQ schema piece only
	 * runs when this adds one. That happens on an archive of one of the FAQ
	 * taxonomies whose template shows the "TO FAQs" block outside a Query
	 * Loop, where it renders the term's FAQs.
	 *
	 * @since 0.1.0
	 *
	 * @param array  $pieces  Schema graph pieces.
	 * @param object $context Yoast meta tags context.
	 * @return array Unchanged schema graph pieces.
	 */
	protected function add_term_questions_to_schema( $pieces, $context ) {
		$term_id  = (int) $context->indexable->object_id;
		$taxonomy = (string) $context->indexable->object_sub_type;

		if ( ! $this->is_faq_term_archive() || ! is_tax( $taxonomy ) || get_queried_object_id() !== $term_id ) {
			return $pieces;
		}

		/**
		 * Filters whether the term's FAQ fields are added to the Yoast FAQ schema.
		 *
		 * Defaults to true only when the "TO FAQs" block shows the term's FAQs
		 * on the archive, because FAQ structured data should match content that
		 * is visible to visitors.
		 *
		 * @since 0.1.0
		 *
		 * @param bool   $on_page  Whether the "TO FAQs" block is on the archive, outside a Query Loop.
		 * @param int    $term_id  The term ID.
		 * @param string $taxonomy The taxonomy slug.
		 */
		if ( ! apply_filters( 'lsx_to_faq_add_term_schema', $this->template_has_variation( true ), $term_id, $taxonomy ) ) {
			return $pieces;
		}

		$questions = $this->get_term_questions( $term_id );

		if ( empty( $questions ) ) {
			return $pieces;
		}

		$blocks                = is_array( $context->blocks ) ? $context->blocks : [];
		$blocks[ self::BLOCK ] = array_merge(
			$blocks[ self::BLOCK ] ?? [],
			[
				[
					'blockName'    => self::BLOCK,
					'attrs'        => [ 'questions' => $questions ],
					'innerBlocks'  => [],
					'innerHTML'    => '',
					'innerContent' => [],
				],
			]
		);

		$context->blocks = $blocks;

		return $pieces;
	}

	/**
	 * Checks whether the current request is an archive of one of the FAQ taxonomies.
	 *
	 * @since 0.1.0
	 * @return bool
	 */
	protected function is_faq_term_archive() {
		$taxonomies = $this->get_taxonomies();

		return ! empty( $taxonomies ) && is_tax( $taxonomies );
	}

	/**
	 * Returns the post's FAQs in the Yoast FAQ block `questions` format.
	 *
	 * @since 0.1.0
	 *
	 * @param int $post_id Post ID.
	 * @return array[]
	 */
	public function get_questions( $post_id ) {
		if ( ! $post_id || ! in_array( get_post_type( $post_id ), $this->get_post_types(), true ) ) {
			return [];
		}

		$questions = $this->build_questions( get_post_meta( $post_id, self::FIELD, true ), 'faq-question-' . $post_id . '-' );

		/**
		 * Filters the FAQs built from a post's FAQ fields.
		 *
		 * @since 0.1.0
		 *
		 * @param array[] $questions Questions in the Yoast FAQ block format.
		 * @param int     $post_id   The post ID.
		 */
		return apply_filters( 'lsx_to_faq_questions', $questions, $post_id );
	}

	/**
	 * Returns the term's FAQs in the Yoast FAQ block `questions` format.
	 *
	 * @since 0.1.0
	 *
	 * @param int $term_id Term ID.
	 * @return array[]
	 */
	public function get_term_questions( $term_id ) {
		$term = $term_id ? get_term( $term_id ) : null;

		if ( ! $term instanceof \WP_Term || ! in_array( $term->taxonomy, $this->get_taxonomies(), true ) ) {
			return [];
		}

		$questions = $this->build_questions( get_term_meta( $term->term_id, self::FIELD, true ), 'faq-question-term-' . $term->term_id . '-' );

		/**
		 * Filters the FAQs built from a term's FAQ fields.
		 *
		 * @since 0.1.0
		 *
		 * @param array[]  $questions Questions in the Yoast FAQ block format.
		 * @param \WP_Term $term      The term.
		 */
		return apply_filters( 'lsx_to_faq_term_questions', $questions, $term );
	}

	/**
	 * Builds Yoast FAQ block questions from saved FAQ field rows.
	 *
	 * @since 0.1.0
	 *
	 * @param mixed  $rows      The saved group field value.
	 * @param string $id_prefix Prefix for the question IDs, followed by the position.
	 * @return array[]
	 */
	protected function build_questions( $rows, $id_prefix ) {
		if ( empty( $rows ) || ! is_array( $rows ) ) {
			return [];
		}

		// The tags Yoast SEO keeps in FAQ schema answers.
		$schema_html = [
			'a'      => [ 'href' => true ],
			'b'      => [],
			'br'     => [],
			'em'     => [],
			'h1'     => [],
			'h2'     => [],
			'h3'     => [],
			'h4'     => [],
			'h5'     => [],
			'h6'     => [],
			'i'      => [],
			'li'     => [],
			'ol'     => [],
			'p'      => [],
			'strong' => [],
			'ul'     => [],
		];

		$questions = [];
		$position  = 0;

		foreach ( $rows as $row ) {
			$question = isset( $row['question'] ) ? esc_html( trim( (string) $row['question'] ) ) : '';
			$answer   = isset( $row['answer'] ) ? trim( wp_kses_post( (string) $row['answer'] ) ) : '';

			if ( '' === $question || '' === $answer ) {
				continue;
			}

			++$position;

			$questions[] = [
				'id'           => $id_prefix . $position,
				'question'     => $question,
				'answer'       => $this->format_answer( $answer ),
				'jsonQuestion' => $question,
				'jsonAnswer'   => trim( wp_kses( strip_shortcodes( $answer ), $schema_html ) ),
				'jsonImageSrc' => $this->get_first_image_src( $answer ),
				'images'       => [],
			];
		}

		return $questions;
	}

	/**
	 * Formats a WYSIWYG answer for display, the way post content is formatted.
	 *
	 * @since 0.1.0
	 *
	 * @param string $answer Answer HTML, already passed through wp_kses_post().
	 * @return string
	 */
	protected function format_answer( $answer ) {
		$answer = wpautop( $answer );
		$answer = shortcode_unautop( $answer );
		$answer = do_shortcode( $answer );

		return wp_filter_content_tags( $answer );
	}

	/**
	 * Returns the URL of the first image in an answer, for the FAQ schema.
	 *
	 * @since 0.1.0
	 *
	 * @param string $answer Answer HTML.
	 * @return string
	 */
	protected function get_first_image_src( $answer ) {
		$tags = new \WP_HTML_Tag_Processor( $answer );

		if ( ! $tags->next_tag( 'img' ) ) {
			return '';
		}

		$src = $tags->get_attribute( 'src' );

		return is_string( $src ) ? esc_url_raw( $src ) : '';
	}

	/**
	 * Checks whether the current block template includes the variation.
	 *
	 * Looks through the resolved template, including any template parts,
	 * patterns, and synced patterns it references. A variation hidden when
	 * the page rendered, for example by a visibility rule, does not count.
	 *
	 * @since 0.1.0
	 *
	 * @param bool $outside_query_loops Whether to skip Query Loops, whose
	 *                                  variations show each post's FAQs, including
	 *                                  Query Loops inside referenced content.
	 * @return bool
	 */
	protected function template_has_variation( $outside_query_loops = false ) {
		$skip = null;

		if ( $outside_query_loops ) {
			$skip = function ( $block ) {
				return 'core/query' === $block['blockName'];
			};
		}

		$variations = Block_References::find(
			Block_References::current_template_blocks(),
			function ( $block ) {
				return self::BLOCK === $block['blockName']
					&& ! empty( $block['attrs'][ self::ATTRIBUTE ] )
					&& Rendered_Blocks::is_visible( $block );
			},
			true,
			0,
			[],
			$skip
		);

		return ! empty( $variations );
	}

	/**
	 * Enqueues the editor script that registers the "TO FAQs" variation.
	 *
	 * The script adds the variation attribute through the
	 * `blocks.registerBlockType` filter, so it must load before Yoast
	 * registers its FAQ block. It is added as a dependency of Yoast's script.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function enqueue_editor_assets() {
		$asset_path = LSX_TO_FAQ_PATH . 'build/js/faq-fields.asset.php';

		if ( ! file_exists( $asset_path ) ) {
			return;
		}

		$asset = include $asset_path;

		wp_enqueue_script(
			self::SCRIPT,
			LSX_TO_FAQ_URL . 'build/js/faq-fields.js',
			$asset['dependencies'] ?? array(),
			$asset['version'] ?? LSX_TO_FAQ_VER,
			true
		);

		wp_set_script_translations( self::SCRIPT, 'to-faq', LSX_TO_FAQ_PATH . 'languages' );

		$scripts = wp_scripts();
		if ( isset( $scripts->registered[ self::YOAST_SCRIPT ] ) && ! in_array( self::SCRIPT, $scripts->registered[ self::YOAST_SCRIPT ]->deps, true ) ) {
			$scripts->registered[ self::YOAST_SCRIPT ]->deps[] = self::SCRIPT;
		}
	}
}
