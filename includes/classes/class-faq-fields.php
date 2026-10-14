<?php
/**
 * FAQ Fields.
 *
 * Adds a repeatable, sortable FAQs meta box (a question and a formatted
 * answer, which can include images) to posts and the Tour Operator post
 * types, and a "TO FAQs" variation of the Yoast FAQ block
 * (`yoast/faq-block`) that renders those fields on the front end.
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
	 * Maximum nesting depth when looking for the variation in templates.
	 *
	 * @var int
	 */
	const MAX_DEPTH = 5;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		add_action( 'cmb2_admin_init', [ $this, 'register_fields' ], 100 );
		add_filter( 'register_block_type_args', [ $this, 'extend_block_type' ], 10, 2 );
		add_filter( 'render_block_' . self::BLOCK, [ $this, 'render_block' ], 10, 3 );
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
	 * Registers the FAQs meta box.
	 *
	 * Uses CMB2 in the same way as the Tour Operator meta boxes
	 * (`lsx\admin\Setup::register_cmb2_fields()`). The answer is a WYSIWYG
	 * field like the itinerary description, so it supports basic formatting and
	 * images through Add Media. Rows can be reordered with the up and down
	 * arrows.
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

		if ( empty( $post_types ) ) {
			return;
		}

		$cmb = new_cmb2_box(
			[
				'id'           => self::METABOX,
				'title'        => esc_html__( 'FAQs', 'to-faq' ),
				'object_types' => $post_types,
				'context'      => 'normal',
				'priority'     => 'high',
				'show_names'   => true,
			]
		);

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

		$args['uses_context'] = array_values(
			array_unique( array_merge( $args['uses_context'] ?? [], [ 'postId', 'postType' ] ) )
		);

		return $args;
	}

	/**
	 * Renders the "TO FAQs" variation from the post's FAQ fields.
	 *
	 * Standard Yoast FAQ blocks are returned unchanged. The variation renders
	 * nothing when the post has no FAQs.
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

		$post_id = 0;
		if ( $instance instanceof \WP_Block && ! empty( $instance->context['postId'] ) ) {
			$post_id = (int) $instance->context['postId'];
		}
		if ( ! $post_id ) {
			$post_id = (int) get_the_ID();
		}

		$questions = $this->get_questions( $post_id );

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
		if ( ! is_object( $context ) || empty( $context->indexable ) || 'post' !== $context->indexable->object_type ) {
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

		$rows = get_post_meta( $post_id, self::FIELD, true );

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
				'id'           => 'faq-question-' . $post_id . '-' . $position,
				'question'     => $question,
				'answer'       => $this->format_answer( $answer ),
				'jsonQuestion' => $question,
				'jsonAnswer'   => trim( wp_kses( strip_shortcodes( $answer ), $schema_html ) ),
				'jsonImageSrc' => $this->get_first_image_src( $answer ),
				'images'       => [],
			];
		}

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
	 * patterns, and synced patterns it references.
	 *
	 * @since 0.1.0
	 * @return bool
	 */
	protected function template_has_variation() {
		global $_wp_current_template_content;

		if ( empty( $_wp_current_template_content ) ) {
			return false;
		}

		return $this->blocks_have_variation( parse_blocks( $_wp_current_template_content ), 0 );
	}

	/**
	 * Recursively checks parsed blocks for the variation.
	 *
	 * @since 0.1.0
	 *
	 * @param array $blocks Parsed blocks.
	 * @param int   $depth  Current nesting depth of referenced content.
	 * @return bool
	 */
	protected function blocks_have_variation( $blocks, $depth ) {
		if ( $depth > self::MAX_DEPTH ) {
			return false;
		}

		foreach ( $blocks as $block ) {
			$name = $block['blockName'] ?? '';

			if ( self::BLOCK === $name && ! empty( $block['attrs'][ self::ATTRIBUTE ] ) ) {
				return true;
			}

			$referenced = $this->get_referenced_content( $block );
			if ( '' !== $referenced && $this->blocks_have_variation( parse_blocks( $referenced ), $depth + 1 ) ) {
				return true;
			}

			if ( ! empty( $block['innerBlocks'] ) && $this->blocks_have_variation( $block['innerBlocks'], $depth ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns the content of a template part, pattern, or synced pattern block.
	 *
	 * @since 0.1.0
	 *
	 * @param array $block Parsed block.
	 * @return string
	 */
	protected function get_referenced_content( $block ) {
		$name  = $block['blockName'] ?? '';
		$attrs = $block['attrs'] ?? [];

		if ( 'core/template-part' === $name && ! empty( $attrs['slug'] ) ) {
			$theme = ! empty( $attrs['theme'] ) ? $attrs['theme'] : get_stylesheet();
			$part  = get_block_template( $theme . '//' . $attrs['slug'], 'wp_template_part' );
			return $part && ! empty( $part->content ) ? $part->content : '';
		}

		if ( 'core/pattern' === $name && ! empty( $attrs['slug'] ) ) {
			$pattern = \WP_Block_Patterns_Registry::get_instance()->get_registered( $attrs['slug'] );
			return $pattern && ! empty( $pattern['content'] ) ? $pattern['content'] : '';
		}

		if ( 'core/block' === $name && ! empty( $attrs['ref'] ) ) {
			$synced = get_post( (int) $attrs['ref'] );
			return $synced && 'wp_block' === $synced->post_type ? $synced->post_content : '';
		}

		return '';
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
