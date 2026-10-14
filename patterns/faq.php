<?php
/**
 * Pattern: TO FAQ
 *
 * A heading followed by a Yoast FAQ block. Registered as `to-faq/faq` by
 * lsx\to_faq\Template_Parts, only while Yoast SEO is active, and called from
 * the parts/faq.html template part.
 *
 * The block attributes and saved HTML are generated from the same array so
 * they always match, which keeps the Yoast block valid in the editor.
 *
 * @package TO_FAQ
 * @since   0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lsx_to_faq_questions = [
	[
		'id'       => 'faq-question-1',
		'question' => __( 'What is your first frequently asked question?', 'to-faq' ),
		'answer'   => __( 'Replace this text with the answer to your first question.', 'to-faq' ),
	],
	[
		'id'       => 'faq-question-2',
		'question' => __( 'What is your second frequently asked question?', 'to-faq' ),
		'answer'   => __( 'Replace this text with the answer to your second question.', 'to-faq' ),
	],
	[
		'id'       => 'faq-question-3',
		'question' => __( 'What is your third frequently asked question?', 'to-faq' ),
		'answer'   => __( 'Replace this text with the answer to your third question.', 'to-faq' ),
	],
];

$lsx_to_faq_attributes = [
	'questions' => array_map(
		function ( $item ) {
			return [
				'id'           => $item['id'],
				'question'     => esc_html( $item['question'] ),
				'answer'       => esc_html( $item['answer'] ),
				'jsonQuestion' => esc_html( $item['question'] ),
				'jsonAnswer'   => esc_html( $item['answer'] ),
				'images'       => [],
			];
		},
		$lsx_to_faq_questions
	),
];
?>
<!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'FAQ', 'to-faq' ); ?>"},"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:heading -->
<h2 class="wp-block-heading"><?php echo esc_html__( 'Frequently asked questions', 'to-faq' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:yoast/faq-block <?php echo serialize_block_attributes( $lsx_to_faq_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- serialize_block_attributes() escapes for block comment delimiters. ?> -->
<div class="schema-faq wp-block-yoast-faq-block"><?php foreach ( $lsx_to_faq_attributes['questions'] as $lsx_to_faq_item ) : ?><div class="schema-faq-section" id="<?php echo esc_attr( $lsx_to_faq_item['id'] ); ?>"><strong class="schema-faq-question"><?php echo $lsx_to_faq_item['question']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?></strong> <p class="schema-faq-answer"><?php echo $lsx_to_faq_item['answer']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?></p> </div> <?php endforeach; ?></div>
<!-- /wp:yoast/faq-block --></div>
<!-- /wp:group -->
