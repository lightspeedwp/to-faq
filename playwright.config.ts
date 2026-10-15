import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright configuration for the to-faq end-to-end tests.
 *
 * The tests run against a WordPress site with this plugin, Tour Operator and
 * Yoast SEO active, set with WP_BASE_URL (default: the ASC Studio site
 * http://asc.local). See tests/e2e/README.md for the fixture pages.
 *
 * Set PW_CHANNEL=chrome to use an installed Chrome instead of Playwright's
 * bundled Chromium.
 */
export default defineConfig( {
	testDir: './tests/e2e',
	fullyParallel: true,
	forbidOnly: !! process.env.CI,
	retries: process.env.CI ? 2 : 0,
	workers: process.env.CI ? 2 : 4,
	reporter: process.env.CI ? [ [ 'list' ], [ 'html', { open: 'never' } ] ] : 'list',
	timeout: 60_000,
	use: {
		baseURL: process.env.WP_BASE_URL || 'http://asc.local',
		trace: 'retain-on-failure',
		screenshot: 'only-on-failure',
	},
	projects: [
		{
			name: 'desktop',
			use: { ...devices[ 'Desktop Chrome' ], channel: process.env.PW_CHANNEL || undefined },
		},
	],
} );
