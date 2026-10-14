<?php
/**
 * FAQ Fields.
 *
 * Adds a repeatable set of FAQ fields (question, answer, and an optional
 * image) to supported post types, and a "Post FAQs" variation of the Yoast
 * FAQ block (`yoast/faq-block`) that renders those fields on the front end.
 *
 * Yoast SEO only builds its FAQPage schema from FAQ blocks it finds in the
 * post content, so the field questions are also passed into Yoast's schema
 * context. That way Yoast keeps generating and maintaining the structured
 * data, and this class only supplies the questions.
 *
 * Requires Yoast SEO (for the FAQ block and schema) and Secure Custom Fields
 * (for the repeater fields).
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
	 * The block attribute that marks a Yoast FAQ block as the "Post FAQs" variation.
	 *
	 * @var string
	 */
	const ATTRIBUTE = 'lsxToPostFaqs';

	/**
	 * The repeater field name, also used as the meta key.
	 *
	 * @var string
	 */
	const FIELD = 'lsx_to_faqs';

	/**
	 * The repeater field key.
	 *
	 * Values are read by key because SCF can only resolve a field by name once
	 * its reference meta has been saved for the post.
	 *
	 * @var string
	 */
	const FIELD_KEY = 'field_lsx_to_faqs';

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
		add_action( 'acf/include_fields', [ $this, 'register_fields' ] );
		add_filter( 'register_block_type_args', [ $this, 'extend_block_type' ], 10, 2 );
		add_filter( 'render_block_' . self::BLOCK, [ $this, 'render_block' ], 10, 3 );
		add_filter( 'wpseo_schema_graph_pieces', [ $this, 'add_questions_to_schema' ], 10, 2 );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ], 5 );
	}

	/**
	 * Checks whether the plugins this feature depends on are active.
	 *
	 * @since 0.1.0
	 * @return bool
	 */
	public static function is_supported() {
		return defined( 'WPSEO_VERSION' ) && function_exists( 'acf_add_local_field_group' );
	}

	/**
	 * Returns the post types that get the FAQ fields.
	 *
	 * @since 0.1.0
	 * @return string[]
	 */
	public function get_post_types() {
		/**
		 * Filters the post types that get the FAQ fields.
		 *
		 * Other plugins can add their post types here, for example:
		 *
		 *     add_filter( 'lsx_to_faq_post_types', function ( $post_types ) {
		 *         return array_merge( $post_types, [ 'post', 'page' ] );
		 *     } );
		 *
		 * @since 0.1.0
		 *
		 * @param string[] $post_types Post type slugs. Default `[ 'tour', 'accommodation', 'destination' ]`.
		 */
		$post_types = apply_filters( 'lsx_to_faq_post_types', [ 'tour', 'accommodation', 'destination' ] );

		return array_values( array_unique( array_filter( (array) $post_types, 'is_string' ) ) );
	}

	/**
	 * Registers the FAQ repeater field group.
	 *
	 * Registered in PHP rather than Local JSON so the location rules can follow
	 * the `lsx_to_faq_post_types` filter.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register_fields() {
		$location = [];

		foreach ( $this->get_post_types() as $post_type ) {
			$location[] = [
				[
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => $post_type,
				],
			];
		}

		if ( empty( $location ) ) {
			return;
		}

		acf_add_local_field_group(
			[
				'key'         => 'group_lsx_to_faqs',
				'title'       => __( 'FAQs', 'to-faq' ),
				'fields'      => [
					[
						'key'          => self::FIELD_KEY,
						'label'        => __( 'Frequently asked questions', 'to-faq' ),
						'name'         => self::FIELD,
						'type'         => 'repeater',
						'instructions' => __( 'Shown by the "Post FAQs" block, and output as FAQ structured data by Yoast SEO.', 'to-faq' ),
						'layout'       => 'block',
						'button_label' => __( 'Add question', 'to-faq' ),
						'collapsed'    => 'field_lsx_to_faqs_question',
						'sub_fields'   => [
							[
								'key'      => 'field_lsx_to_faqs_question',
								'label'    => __( 'Question', 'to-faq' ),
								'name'     => 'question',
								'type'     => 'text',
								'required' => 1,
							],
							[
								'key'          => 'field_lsx_to_faqs_answer',
								'label'        => __( 'Answer', 'to-faq' ),
								'name'         => 'answer',
								'type'         => 'textarea',
								'instructions' => __( 'Links, bold, and italic HTML are allowed.', 'to-faq' ),
								'required'     => 1,
								'rows'         => 4,
								'new_lines'    => 'br',
							],
							[
								'key'           => 'field_lsx_to_faqs_image',
								'label'         => __( 'Image', 'to-faq' ),
								'name'          => 'image',
								'type'          => 'image',
								'instructions'  => __( 'Optional. Shown below the answer.', 'to-faq' ),
								'return_format' => 'id',
								'preview_size'  => 'medium',
								'library'       => 'all',
							],
						],
					],
				],
				'location'    => $location,
				'position'    => 'normal',
				'style'       => 'default',
				'menu_order'  => 20,
				'active'      => true,
				'description' => __( 'Repeatable FAQ fields used by the Yoast FAQ block "Post FAQs" variation.', 'to-faq' ),
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
	 * Renders the "Post FAQs" variation from the post's FAQ fields.
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
				'<div class="schema-faq-section" id="%1$s"><strong class="schema-faq-question">%2$s</strong> <p class="schema-faq-answer">%3$s</p> </div> ',
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
		 * Defaults to true only when the "Post FAQs" block is on the page, because
		 * FAQ structured data should match content that is visible to visitors.
		 *
		 * @since 0.1.0
		 *
		 * @param bool $on_page Whether the "Post FAQs" block is on the page.
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

		$rows = get_field( self::FIELD_KEY, $post_id );

		if ( empty( $rows ) || ! is_array( $rows ) ) {
			return [];
		}

		$allowed_html = [
			'a'      => [
				'href'   => true,
				'target' => true,
				'rel'    => true,
			],
			'br'     => [],
			'em'     => [],
			'i'      => [],
			'strong' => [],
			'b'      => [],
		];

		/**
		 * Filters the image size used for FAQ answer images.
		 *
		 * @since 0.1.0
		 *
		 * @param string $size    Image size name. Default 'medium_large'.
		 * @param int    $post_id The post ID.
		 */
		$image_size = apply_filters( 'lsx_to_faq_image_size', 'medium_large', $post_id );

		$questions = [];
		$position  = 0;

		foreach ( $rows as $row ) {
			$question = isset( $row['question'] ) ? esc_html( trim( (string) $row['question'] ) ) : '';
			$answer   = isset( $row['answer'] ) ? trim( wp_kses( (string) $row['answer'], $allowed_html ) ) : '';

			if ( '' === $question || '' === $answer ) {
				continue;
			}

			++$position;

			$image_html = '';
			$image_src  = '';
			if ( ! empty( $row['image'] ) ) {
				$image_id   = (int) $row['image'];
				$image_html = wp_get_attachment_image( $image_id, $image_size, false, [ 'class' => 'schema-faq-answer-image' ] );
				$image_src  = (string) wp_get_attachment_image_url( $image_id, 'full' );
			}

			$questions[] = [
				'id'           => 'faq-question-' . $post_id . '-' . $position,
				'question'     => $question,
				'answer'       => $image_html ? $answer . '<br />' . $image_html : $answer,
				'jsonQuestion' => $question,
				'jsonAnswer'   => $answer,
				'jsonImageSrc' => $image_src,
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
	 * Enqueues the editor script that registers the "Post FAQs" variation.
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
