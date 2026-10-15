<?php
/**
 * Template FAQ Schema.
 *
 * Yoast SEO builds its FAQPage schema from the Yoast FAQ blocks written in a
 * post's own content. FAQ blocks that reach the page another way, such as a
 * template part inserted in the content or a block template, render on the
 * page but are left out of the schema.
 *
 * This class finds those blocks and passes them into Yoast's schema context,
 * so the structured data matches the FAQs visitors can see. Yoast keeps
 * generating the schema; this class only supplies the blocks.
 *
 * @package TO_FAQ
 * @since   0.1.0
 */

namespace lsx\to_faq;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Template_Faq_Schema
 *
 * @since 0.1.0
 */
class Template_Faq_Schema {

	/**
	 * The Yoast FAQ block name.
	 *
	 * @var string
	 */
	const BLOCK = 'yoast/faq-block';

	/**
	 * Constructor.
	 *
	 * Runs after Faq_Fields::add_questions_to_schema(), so the "TO FAQs"
	 * questions are already in the context when duplicates are checked.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		add_filter( 'wpseo_schema_graph_pieces', [ $this, 'add_template_faqs_to_schema' ], 20, 2 );
	}

	/**
	 * Adds Yoast FAQ blocks from the template and referenced content to Yoast's schema context.
	 *
	 * @since 0.1.0
	 *
	 * @param array  $pieces  Schema graph pieces.
	 * @param object $context Yoast meta tags context.
	 * @return array Unchanged schema graph pieces.
	 */
	public function add_template_faqs_to_schema( $pieces, $context ) {
		if ( ! is_object( $context ) || empty( $context->indexable ) || 'post' !== $context->indexable->object_type ) {
			return $pieces;
		}

		$post_id = (int) $context->indexable->object_id;

		if ( ! is_singular() || get_queried_object_id() !== $post_id ) {
			return $pieces;
		}

		/**
		 * Filters whether Yoast FAQ blocks from templates and template parts are added to the FAQ schema.
		 *
		 * @since 0.1.0
		 *
		 * @param bool $add     Whether to add them. Default true.
		 * @param int  $post_id The post ID.
		 */
		if ( ! apply_filters( 'lsx_to_faq_add_template_schema', true, $post_id ) ) {
			return $pieces;
		}

		$is_static_faq = function ( $block ) {
			return self::BLOCK === $block['blockName']
				&& empty( $block['attrs'][ Faq_Fields::ATTRIBUTE ] )
				&& ! empty( $block['attrs']['questions'] )
				&& is_array( $block['attrs']['questions'] );
		};

		// The template's blocks never reach Yoast; in post content only referenced blocks are missing.
		$found = array_merge(
			Block_References::find( Block_References::current_template_blocks(), $is_static_faq ),
			Block_References::find( parse_blocks( (string) get_post_field( 'post_content', $post_id ) ), $is_static_faq, false )
		);

		if ( empty( $found ) ) {
			return $pieces;
		}

		$blocks    = is_array( $context->blocks ) ? $context->blocks : [];
		$faq_block = $blocks[ self::BLOCK ] ?? [];
		$seen      = $this->get_question_ids( $faq_block );

		foreach ( $found as $block ) {
			$questions = [];

			// Skip questions already in the schema, for example a part placed twice.
			foreach ( $block['attrs']['questions'] as $question ) {
				$id = $question['id'] ?? '';
				if ( '' === $id || isset( $seen[ $id ] ) ) {
					continue;
				}
				$seen[ $id ] = true;
				$questions[] = $question;
			}

			if ( ! empty( $questions ) ) {
				$block['attrs']['questions'] = $questions;
				$faq_block[]                 = $block;
			}
		}

		if ( ! empty( $faq_block ) ) {
			$blocks[ self::BLOCK ] = array_values( $faq_block );
			$context->blocks       = $blocks;
		}

		return $pieces;
	}

	/**
	 * Returns the question IDs already in a list of FAQ blocks.
	 *
	 * @since 0.1.0
	 *
	 * @param array[] $faq_blocks Parsed Yoast FAQ blocks.
	 * @return array<string, bool> Question ID => true.
	 */
	protected function get_question_ids( $faq_blocks ) {
		$ids = [];

		foreach ( $faq_blocks as $block ) {
			foreach ( (array) ( $block['attrs']['questions'] ?? [] ) as $question ) {
				if ( ! empty( $question['id'] ) ) {
					$ids[ $question['id'] ] = true;
				}
			}
		}

		return $ids;
	}
}
