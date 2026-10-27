import type { Page } from '@playwright/test';

/**
 * Normalises question text so visible copy (typeset by wptexturize, with curly
 * quotes and entities) can be compared with the plain text in the schema.
 *
 * @param text Question text.
 * @return Normalised text.
 */
export function normaliseText( text: string ): string {
	return text
		.replace( /&#0?39;|&#8217;|&#8216;|&rsquo;|&lsquo;|[‘’]/g, "'" )
		.replace( /&#8220;|&#8221;|&quot;|[“”]/g, '"' )
		.replace( /&#8211;|&ndash;|–/g, '-' )
		.replace( /&amp;|&#038;/g, '&' )
		.replace( /\s+/g, ' ' )
		.trim();
}

/**
 * Returns every visible FAQ question on the page, from all Yoast FAQ blocks
 * (including the "TO FAQs" variation), normalised.
 *
 * `.schema-faq-question` is the Yoast FAQ block's own output class.
 *
 * @param page Page.
 * @return Question texts.
 */
export async function visibleQuestions( page: Page ): Promise< string[] > {
	return ( await page.locator( '.schema-faq-question' ).allTextContents() ).map( normaliseText );
}

export interface FaqSchema {
	/** The page node's types, e.g. [ 'WebPage', 'FAQPage' ] or [ 'CollectionPage', 'FAQPage' ] on an archive. */
	pageTypes: string[];
	/** The Question names in schema order, normalised. */
	questions: string[];
}

/**
 * Reads Yoast SEO's schema graph and returns its FAQ data.
 *
 * @param page Page.
 * @return Page types and question names.
 */
export async function readFaqSchema( page: Page ): Promise< FaqSchema > {
	const json = await page.locator( 'script.yoast-schema-graph' ).first().textContent();
	const graph: Array< Record< string, unknown > > = JSON.parse( json ?? '{}' )[ '@graph' ] ?? [];

	const typesOf = ( node: Record< string, unknown > ): string[] =>
		Array.isArray( node[ '@type' ] ) ? ( node[ '@type' ] as string[] ) : [ String( node[ '@type' ] ) ];

	// Yoast types archives as CollectionPage instead of WebPage.
	const webPage = graph.find( ( node ) =>
		typesOf( node ).some( ( type ) => type === 'WebPage' || type === 'CollectionPage' )
	);

	return {
		pageTypes: webPage ? typesOf( webPage ) : [],
		questions: graph
			.filter( ( node ) => node[ '@type' ] === 'Question' )
			.map( ( node ) => normaliseText( String( node.name ) ) ),
	};
}
