import { expect, test } from '@playwright/test';
import { readFaqSchema, visibleQuestions } from './support/schema';

/**
 * FAQ fields on the Tour Operator taxonomies (Faq_Fields): the "TO FAQs" block
 * in a taxonomy archive template, outside a Query Loop, shows the term's FAQs
 * and Yoast outputs them as FAQ schema.
 *
 * The ASC site has no default fixtures, so each test is skipped until its
 * variable is set:
 * - TO_FAQ_TERM_URL        An archive of accommodation-brand, accommodation-type, travel-style
 *                          or continent, for a term with FAQ fields, whose template shows the
 *                          "TO FAQs" block outside a Query Loop.
 * - TO_FAQ_EMPTY_TERM_URL  An archive on the same template, for a term without FAQ fields.
 *
 * PRD v0.8 §6: visible FAQ content and schema output must match.
 */

const TERM_URL = process.env.TO_FAQ_TERM_URL;
const EMPTY_TERM_URL = process.env.TO_FAQ_EMPTY_TERM_URL;

test.describe( 'Term FAQ fields on a taxonomy archive', () => {
	test( 'outputs FAQPage with every visible term question exactly once', async ( { page } ) => {
		test.skip( ! TERM_URL, 'Set TO_FAQ_TERM_URL to a taxonomy archive for a term with FAQ fields.' );

		await page.goto( TERM_URL as string );

		const visible = await visibleQuestions( page );
		const schema = await readFaqSchema( page );

		expect( visible.length, 'fixture archive shows FAQs' ).toBeGreaterThan( 0 );
		expect( schema.pageTypes ).toContain( 'FAQPage' );
		expect( [ ...schema.questions ].sort() ).toEqual( [ ...visible ].sort() );
		expect( new Set( schema.questions ).size ).toBe( schema.questions.length );
	} );

	test( 'a term without FAQ fields gets no FAQs or FAQ schema', async ( { page } ) => {
		test.skip( ! EMPTY_TERM_URL, 'Set TO_FAQ_EMPTY_TERM_URL to a taxonomy archive for a term without FAQ fields.' );

		await page.goto( EMPTY_TERM_URL as string );

		expect( await page.locator( '.is-lsx-to-post-faqs' ).count() ).toBe( 0 );
		expect( ( await readFaqSchema( page ) ).pageTypes ).not.toContain( 'FAQPage' );
	} );
} );
