<?php
/**
 * Block References.
 *
 * Finds blocks in parsed block trees, following the template parts, patterns
 * and synced patterns they reference. Yoast SEO only reads the blocks written
 * in a post's own content, so the FAQ classes use this to find FAQ blocks that
 * reach the page through the block template or a referenced part.
 *
 * @package TO_FAQ
 * @since   0.1.0
 */

namespace lsx\to_faq;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Block_References
 *
 * @since 0.1.0
 */
class Block_References {

	/**
	 * Maximum nesting depth of referenced content.
	 *
	 * @var int
	 */
	const MAX_DEPTH = 5;

	/**
	 * Returns the parsed blocks of the block template being rendered.
	 *
	 * @since 0.1.0
	 * @return array[]
	 */
	public static function current_template_blocks() {
		global $_wp_current_template_content;

		if ( empty( $_wp_current_template_content ) ) {
			return [];
		}

		return parse_blocks( $_wp_current_template_content );
	}

	/**
	 * Finds the blocks that match a callback.
	 *
	 * Inner blocks are searched, and so is the content of any template part,
	 * pattern or synced pattern a block references. With `$include_direct`
	 * false, only blocks reached through a reference are returned, which skips
	 * the blocks Yoast SEO already collects from post content.
	 *
	 * @since 0.1.0
	 *
	 * @param array[]  $blocks         Parsed blocks.
	 * @param callable $matches        Receives a parsed block and returns whether it matches.
	 * @param bool     $include_direct Whether to return matches outside referenced content.
	 * @param int      $depth          Current nesting depth of referenced content.
	 * @return array[] Matching parsed blocks.
	 */
	public static function find( $blocks, callable $matches, $include_direct = true, $depth = 0 ) {
		if ( $depth > self::MAX_DEPTH ) {
			return [];
		}

		$found = [];

		foreach ( $blocks as $block ) {
			if ( empty( $block['blockName'] ) ) {
				continue;
			}

			if ( $include_direct && $matches( $block ) ) {
				$found[] = $block;
			}

			$referenced = self::get_referenced_content( $block );
			if ( '' !== $referenced ) {
				$found = array_merge( $found, self::find( parse_blocks( $referenced ), $matches, true, $depth + 1 ) );
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				$found = array_merge( $found, self::find( $block['innerBlocks'], $matches, $include_direct, $depth ) );
			}
		}

		return $found;
	}

	/**
	 * Returns the content of a template part, pattern or synced pattern block.
	 *
	 * @since 0.1.0
	 *
	 * @param array $block Parsed block.
	 * @return string
	 */
	public static function get_referenced_content( $block ) {
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
}
