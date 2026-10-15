import { expect, test } from '@playwright/test';
import { readFaqSchema, visibleQuestions } from './support/schema';

/**
 * FAQ schema for Yoast FAQ blocks outside post content (Template_Faq_Schema),
 * and the "TO FAQs" schema after the Block_References refactor (Faq_Fields).
 *
 * Fixture pages default to the ASC site; override them for another site:
 * - FAQ_IN_CONTENT_PART_URL  A page with a template part holding a Yoast FAQ block in its content.
 * - FAQ_IN_TEMPLATE_URL      A page whose block template includes Yoast FAQ blocks.
 * - TO_FAQ_POST_URL          A post with FAQ fields and the "TO FAQs" block in its template.
 * - TO_FAQ_EMPTY_POST_URL    A post using the same template, without FAQ fields.
 * - FAQ_ARCHIVE_URL          An archive whose template shows a Yoast FAQ block.
 *
 * Traceability: ASD-67 acceptance criteria
 * - AC2 FAQ schema on a template-part FAQ page matches the visible questions.
 * - AC3 Per-post "TO FAQs" schema is unchanged.
 * PRD v0.8 §6: visible FAQ content and schema output must match.
 */

const url = ( name: string, fallback: string ): string => process.env[ name ] || fallback;

const MATCHING_PAGES = [
	{ name: 'a template part in the post content', path: url( 'FAQ_IN_CONTENT_PART_URL', '/contact/' ) },
	{ name: 'template parts in the block template', path: url( 'FAQ_IN_TEMPLATE_URL', '/faq/' ) },
	{ name: 'the "TO FAQs" block in the template', path: url( 'TO_FAQ_POST_URL', '/south-africa/' ) },
];

for ( const fixture of MATCHING_PAGES ) {
	test.describe( `FAQ schema from ${ fixture.name }`, () => {
		// ASD-67 AC2, AC3
		test( 'outputs FAQPage with every visible question exactly once', async ( { page } ) => {
			await page.goto( fixture.path );

			const visible = await visibleQuestions( page );
			const schema = await readFaqSchema( page );

			expect( visible.length, 'fixture page shows FAQs' ).toBeGreaterThan( 0 );
			expect( schema.pageTypes ).toContain( 'FAQPage' );
			expect( [ ...schema.questions ].sort() ).toEqual( [ ...visible ].sort() );
			expect( new Set( schema.questions ).size ).toBe( schema.questions.length );
		} );
	} );
}

test.describe( 'Pages that must not get FAQ schema', () => {
	// ASD-67 AC3: the "TO FAQs" block renders nothing, and adds no schema, without FAQ fields.
	test( 'a post without FAQ fields', async ( { page } ) => {
		await page.goto( url( 'TO_FAQ_EMPTY_POST_URL', '/botswana/' ) );

		expect( await visibleQuestions( page ) ).toHaveLength( 0 );
		expect( ( await readFaqSchema( page ) ).pageTypes ).not.toContain( 'FAQPage' );
	} );

	// Template_Faq_Schema only runs on singular pages; Yoast has no FAQ schema for archives.
	test( 'an archive that shows FAQs', async ( { page } ) => {
		await page.goto( url( 'FAQ_ARCHIVE_URL', '/team/' ) );

		expect( ( await visibleQuestions( page ) ).length ).toBeGreaterThan( 0 );
		const schema = await readFaqSchema( page );
		expect( schema.pageTypes ).not.toContain( 'FAQPage' );
		expect( schema.questions ).toHaveLength( 0 );
	} );
} );
