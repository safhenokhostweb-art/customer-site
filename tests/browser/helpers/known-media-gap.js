/**
 * MASTER_THEME_MEDIA_LOCALIZATION_GAP (Phase 4 finding, confirmed with real
 * browser network evidence in Phase 5): the canonical universal-
 * multipurpose-qa product's own stock imagery (hero/about/gallery/team) is
 * never imported into the installing site's CustomerMediaService — it stays
 * a plain URL hosted on saas-platform's own storage domain forever (see
 * saas-platform's UniversalMultipurposeQaThemeSeeder::seedImages()). In this
 * isolated browser-test environment (deliberately no live saas-platform
 * dependency — see BrowserHarnessSetupCommand::loadCanonicalUniversalThemeExport())
 * that domain simply does not resolve, so every one of these images fails
 * to load — the same failure mode a real, independently-operating customer
 * site would hit if saas-platform's storage endpoint were ever unreachable,
 * renamed, or network-partitioned from that customer.
 *
 * Shared by every browser spec that installs the real canonical product, so
 * this exact, tracked, and evidenced pattern is allow-listed consistently —
 * never a blanket "ignore network errors" rule; every OTHER failed request
 * must still fail its test.
 */
export function isKnownMasterThemeMediaGapFailure(url) {
    return /saas-platform\.test\/storage\/theme-media\//.test(url);
}
