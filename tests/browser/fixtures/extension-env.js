// Phase 3E-H-D — env fragments for restarting the harness server with the
// test-only Extension SDK fixtures registered (see tests/Support/BrowserExtension).
// Never a production default — only ever set on the isolated harness server
// process, via BROWSER_TEST_EXTENSION_PROVIDERS (config/theme_builder_extensions.php).

export const DEMO_SUITE_PROVIDER = 'Tests\\Support\\BrowserExtension\\DemoSuiteProvider';
export const THROWING_SUITE_PROVIDER = 'Tests\\Support\\BrowserExtension\\ThrowingSuiteProvider';

export const WITH_DEMO_SUITE = { BROWSER_TEST_EXTENSION_PROVIDERS: DEMO_SUITE_PROVIDER };
export const WITH_THROWING_SUITE = { BROWSER_TEST_EXTENSION_PROVIDERS: THROWING_SUITE_PROVIDER };
export const WITH_BOTH_SUITES = { BROWSER_TEST_EXTENSION_PROVIDERS: `${DEMO_SUITE_PROVIDER},${THROWING_SUITE_PROVIDER}` };
export const WITH_NO_EXTENSIONS = { BROWSER_TEST_EXTENSION_PROVIDERS: '' };
