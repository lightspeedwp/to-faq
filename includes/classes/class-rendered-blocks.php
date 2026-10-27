<?php
/**
 * Rendered Blocks.
 *
 * Records which Yoast FAQ blocks actually reach the page. A block found in
 * the template or a referenced part can still be hidden when it renders, for
 * example by a visibility rule on the block or on a parent group, and FAQ
 * structured data must match the FAQs visitors can see.
 *
 * Block templates render before `wp_head`, so by the time Yoast SEO builds
 * its schema every block on the page has rendered. Each block is tracked from
 * `render_block_data` to its final `render_block` output; FAQ blocks inside a
 * block whose output ends up empty are discarded with it.
 *
 * @package TO_FAQ
 * @since   0.1.0
 */

namespace lsx\to_faq;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Rendered_Blocks
 *
 * @since 0.1.0
 */
class Rendered_Blocks {

	/**
	 * The Yoast FAQ block name.
	 *
	 * @var string
	 */
	const BLOCK = 'yoast/faq-block';

	/**
	 * Parsed block key that pairs `render_block_data` with `render_block`.
	 *
	 * @var string
	 */
	const KEY = 'lsxToFaqRenderId';

	/**
	 * Blocks being rendered, outermost first. Each frame holds the block's
	 * render ID and the keys of the visible FAQ blocks rendered inside it.
	 *
	 * @var array[]
	 */
	protected static $stack = [];

	/**
	 * Keys of the FAQ blocks that rendered visibly on the page.
	 *
	 * @var array<string, bool>
	 */
	protected static $visible = [];

	/**
	 * Last render ID handed out.
	 *
	 * @var int
	 */
	protected static $next_id = 0;

	/**
	 * Whether a top-level block has finished rendering.
	 *
	 * @var bool
	 */
	protected static $rendered = false;

	/**
	 * Registers the hook that starts tracking on singular and taxonomy archive requests.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function init() {
		if ( ! has_action( 'template_redirect', [ __CLASS__, 'start' ] ) ) {
			add_action( 'template_redirect', [ __CLASS__, 'start' ] );
		}
	}

	/**
	 * Starts tracking block renders.
	 *
	 * Runs last on both filters, so the render ID survives other
	 * `render_block_data` filters and visibility is judged on final output.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function start() {
		if ( ! is_singular() && ! is_tax() ) {
			return;
		}

		add_filter( 'render_block_data', [ __CLASS__, 'open' ], PHP_INT_MAX, 2 );
		add_filter( 'render_block', [ __CLASS__, 'close' ], PHP_INT_MAX, 2 );
	}

	/**
	 * Opens a frame for a block that is about to render.
	 *
	 * @since 0.1.0
	 *
	 * @param array $parsed_block Parsed block, after other filters.
	 * @param array $source_block Parsed block, as written in the content.
	 * @return array Parsed block with its render ID.
	 */
	public static function open( $parsed_block, $source_block = [] ) {
		$id                        = ++self::$next_id;
		$parsed_block[ self::KEY ] = $id;

		$name = $source_block['blockName'] ?? ( $parsed_block['blockName'] ?? '' );

		self::$stack[] = [
			'id'      => $id,
			'key'     => self::BLOCK === $name ? self::get_key( $source_block ? $source_block : $parsed_block ) : '',
			'visible' => [],
		];

		return $parsed_block;
	}

	/**
	 * Closes a block's frame once its final output is known.
	 *
	 * An empty output drops the FAQ blocks rendered inside it. Otherwise they
	 * pass up to the parent frame, or count as visible at the top level.
	 *
	 * @since 0.1.0
	 *
	 * @param string $block_content Rendered block content.
	 * @param array  $block         Parsed block.
	 * @return string Unchanged block content.
	 */
	public static function close( $block_content, $block ) {
		$id = $block[ self::KEY ] ?? 0;
		if ( ! $id ) {
			return $block_content;
		}

		$ids = array_column( self::$stack, 'id' );
		$at  = array_search( $id, $ids, true );
		if ( false === $at ) {
			return $block_content;
		}

		// Fold in frames left open by blocks that never reached render_block.
		$frame = self::$stack[ $at ];
		foreach ( array_slice( self::$stack, $at + 1 ) as $orphan ) {
			$frame['visible'] += $orphan['visible'];
		}
		self::$stack = array_slice( self::$stack, 0, $at );

		if ( '' === trim( (string) $block_content ) ) {
			$frame['visible'] = [];
		} elseif ( '' !== $frame['key'] ) {
			$frame['visible'][ $frame['key'] ] = true;
		}

		if ( empty( self::$stack ) ) {
			self::$visible += $frame['visible'];
			self::$rendered = true;
		} else {
			$parent = count( self::$stack ) - 1;

			self::$stack[ $parent ]['visible'] += $frame['visible'];
		}

		return $block_content;
	}

	/**
	 * Checks whether a FAQ block rendered visibly on the page.
	 *
	 * Before any block has rendered, for example in a classic theme where
	 * content renders after `wp_head`, nothing can be judged yet and every
	 * block counts as visible.
	 *
	 * @since 0.1.0
	 *
	 * @param array $block Parsed Yoast FAQ block.
	 * @return bool
	 */
	public static function is_visible( $block ) {
		if ( ! self::$rendered ) {
			return true;
		}

		return isset( self::$visible[ self::get_key( $block ) ] );
	}

	/**
	 * Returns a key that identifies a FAQ block by its attributes.
	 *
	 * @since 0.1.0
	 *
	 * @param array $block Parsed block.
	 * @return string
	 */
	protected static function get_key( $block ) {
		return md5( (string) wp_json_encode( $block['attrs'] ?? [] ) );
	}
}
