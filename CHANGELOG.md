# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.0.4] - 2026-07-08

### Fixed

- **Catch-all route rules deadlocked verification.** The route gate also fired on
  its own `invisiblecaptcha/verify` endpoint, so with a rule broad enough to match
  it (e.g. `action_name regex .*`) the challenge page's token POST was answered
  with another 403 challenge and a visitor could never pass. `RequestChecker` now
  always exempts the verify path (same normalization as the router); a direct GET
  on the endpoint now yields a plain 404 instead of a challenge.

## [3.0.3] - 2026-07-08

### Fixed

- **Route-protection regex rules accept bare (Cloudflare-style) patterns.** The
  `regex` / `not_regex` operators passed the admin value straight to
  `preg_match()`, which requires PCRE delimiters — so a natural pattern like
  `.*` or `^catalog_.*` errored out ("invalid regex skipped" in the log) and the
  rule never fired. Values are now tried as delimited PCRE first (modifiers like
  `~…~i` keep working) and auto-wrapped in `~…~` on a parse error; only patterns
  invalid in both forms are skipped (still fail-safe, still logged).

### Changed

- **`migrate-recaptcha`: undecryptable values are reported, not silently skipped.**
  When a native encrypted value cannot be decrypted with this installation's crypt
  key (e.g. a DB imported from another environment), the row now appears in the
  change table with the new `skipped_undecryptable` status and the command prints
  a crypt-key warning with remediation steps, instead of omitting the row without
  a trace.

## [3.0.2] - 2026-07-08

### Fixed

- **Login-failure redirect never used the before-auth URL.** `RedirectUrl\BeforeAuthUrl`
  was typed against `SessionManagerInterface`, whose global preference is
  `Session\Generic` — a session object with the *default* storage namespace, while
  `before_auth_url` is written into the *customer* namespace. The provider therefore
  always fell back to the login URL. It now depends on `Magento\Customer\Model\Session`
  (Proxy-wired in `etc/frontend/di.xml`), so a failed login challenge redirects back
  to the customer's intended page again.
- Unit test suite runs green end-to-end on PHP 8.5 / PHPUnit 10 (598 tests): fixed a
  by-reference capture bug in `RouteGateTest`, seeded the global ObjectManager for the
  backend block in `ProtectionRulesTest`, avoided the undefined `BP` constant in
  `DebugTest`, and re-pointed `BeforeAuthUrlTest` at the concrete customer session
  (mocking the magic accessor on the interface fatally failed under PHPUnit 10).

## [3.0.1] - 2026-07-08

### Fixed

- **`migrate-recaptcha`: native site key was copied as ciphertext.** Magento's
  native reCAPTCHA stores `public_key` through `Config\Backend\Encrypted`, while
  this module keeps `site_key` in plain text — the migration now decrypts values
  carrying the Magento crypt envelope (`N:N:…`) and passes legacy plaintext keys
  through untouched; undecryptable values are skipped instead of written. Stores
  that migrated with the broken version can repair by re-running with `--force`.

### Added

- **`migrate-recaptcha`: status hand-over.** After copying, the command clears
  every migrated native `type_for/*` selector row (new `source_disabled` change
  status, previewed in `--dry-run`), so the built-in reCAPTCHA is disabled and
  this module is enabled in one run. Native credentials are left in place;
  physically removing the native modules becomes optional — which also avoids
  `setup:di:compile` breakage on installs whose bundled code (e.g. PayPal
  Braintree) compiles against the reCAPTCHA API modules.

## [3.0.0] - 2026-07-08

Major rewrite into a **multi-provider** captcha & bot-protection module. The
former `Hryvinskyi_TurnstileProtection` module is merged in and removed.

### Added

- **Five providers** behind one abstraction (`Api\Provider\ProviderInterface` +
  `ProviderPoolInterface`): Google reCAPTCHA v2 checkbox, v2 invisible, v3,
  reCAPTCHA Enterprise, and Cloudflare Turnstile. Pick the active provider in
  *Stores → Configuration → Hryvinskyi → Invisible Captcha & Bot Protection*.
- **Route-level protection** (capability B, from TurnstileProtection): a
  site-wide interstitial challenge driven by a Cloudflare-style rule engine
  (`Model\Filter\*`), an HMAC-signed verified cookie, Varnish HTTP-context vary,
  and a verify endpoint at `invisiblecaptcha/verify`.
- **reCAPTCHA Enterprise** verifier using the `projects/{id}/assessments` API.
- Bounded PSR-18 HTTP transport (`Model\Verification\Http\BoundedHttpClient`)
  with a configurable wall-clock budget; the verifier fails closed and fast.
- Per-provider client JS strategies (`view/base/web/js/provider/*`) so any
  provider/widget type renders from one form component.
- Provider-independent verified-cookie HMAC key (Magento crypt key) so switching
  providers does not invalidate verified sessions.
- Data patch `Setup\Patch\Data\MigrateLegacyCaptchaConfig` migrating both the
  legacy `hryvinskyi_invisible_captcha/*` (v2.x) and `hryvinskyi_turnstile/*`
  settings into the unified v3 config tree.

- **Configurable challenge accent color.** New *Challenge Page Appearance*
  sub-group under *Route Protection*
  (`hryvinskyi_invisible_captcha/route_protection/appearance/*`) exposes
  `--primary` (#2f6bd8), `--primary-deep` (#2557b6) and `--primary-soft`
  (rgba(47,107,216,0.12)). `ChallengeRenderer` injects them as a CSP-safe
  `:root` override (values sanitized against CSS-injection) so the interstitial
  re-themes per store view.

- **Migration from Magento's native Google reCAPTCHA.** New CLI command
  `hryvinskyi:invisible-captcha:migrate-recaptcha [--dry-run] [--force]` imports
  `recaptcha_frontend/*` / `recaptcha_backend/*` config (all scopes) into the
  `hryvinskyi_invisible_captcha/*` tree: provider credentials (secrets copied
  verbatim), per-form `type_for/*` selectors, the v3 score threshold, and the
  derived active provider + master switches. The migrator holds only mapping
  policy (`Model\Migration\RecaptchaMigrator`) behind
  `Api\Migration\RecaptchaMigratorInterface`, reads/writes `core_config_data`
  through the `Api\Migration\CoreConfigGatewayInterface` port, carries per-run
  state in `Model\Migration\MigrationRun`, and returns typed
  `Model\Migration\ChangeRecord` results; never clobbers existing values
  without `--force`.
- Companion metapackage `hryvinskyi/magento2-invisible-captcha-recaptcha-replace`
  (published separately) that `replace`s every `magento/module-re-captcha-*`
  package to remove the native reCAPTCHA while leaving the rest of
  `magento/security-package` intact.

### Changed

- Unified, snake-case config tree under `hryvinskyi_invisible_captcha/{general,
  providers,form_protection,route_protection,advanced}`.
- The form-verify flow is now provider-agnostic: one rich
  `Api\Verification\VerificationResultInterface` replaces the reCAPTCHA-only
  response; the "Provider" namespace now means a captcha vendor, while failure /
  token / redirect strategies moved to `Model\Strategy\*`.
- Score thresholds apply only to score-based providers (v3 / Enterprise).
- CSP whitelist is now global and covers Cloudflare + Google hosts across
  script/frame/connect/form-action.
- Submit gating ("disable submit until ready") is now done with CSS + a small
  parse-time inline script rendered next to the captcha marker, instead of
  rewriting the whole HTML response server-side. The script adds the
  `hryvinskyi-captcha-disabled-submit` class to the surrounding form (the CSS in
  `_module.less` makes the submit button unclickable while that class is
  present); `form-manager.js` removes the class once the captcha is ready.

- **Redesigned route-gate challenge page.** The inline interstitial
  (`view/frontend/web/inline_challenge/`) was reworked into a clean,
  system-font layout and no longer loads Google Fonts (Inter / Cormorant
  Garamond) — one fewer third-party request and a tighter CSP surface. The
  placeholder contract and `script.js` bootstrap are unchanged.

### Removed / BREAKING

- `Hryvinskyi_TurnstileProtection` is merged in and deleted. Its config under
  `hryvinskyi_turnstile/*` is migrated automatically; external references to the
  old `turnstile/verify` route, `X-Turnstile-Challenge` header,
  `hryvinskyi_turnstile_verified` cookie and `TURNSTILE_VERIFIED` vary key are
  renamed (run a one-time full-page-cache flush after deploy).
- Legacy v2.x classes (`Helper\Config\*`, `Model\ReCaptcha\*`, `Model\Verify\*`,
  `Model\Provider\{Failure,TokenResponse}*`, `Observer\Captcha`, `LayoutSettings`)
  are removed; integrators should target the new `Api\*` contracts.
- Requires `hryvinskyi/magento2-theme-assets` (replaces the previous
  asset-renderer dependency).
- Removed the server-side `Plugin\DisableSubmit` response rewriter and dropped
  the `voku/simple_html_dom`, `symfony/dom-crawler` and `ext-dom` requirements
  it needed; submit gating is now handled in CSS + JS (see above).

## [2.5.5] - 2026-06-01

### Fixed

- PHP 8.5: added the `: int` return type and an explicit `return Command::SUCCESS;`
  to `Command\Captcha::execute()`, matching Symfony Console `Command::execute(): int`
  (fixes the "must be compatible" fatal error).
- Monolog 3: `Model\Debug::write()` now accepts `Monolog\LogRecord` instead of `array`
  to match `Magento\Framework\Logger\Handler\Base::write()`.

## [2.5.4] - 2026-06-01

### Added

- PHP 8.5 support (`~8.5` added to the `php` requirement).

### Fixed

- PHP 8.4/8.5 compatibility: replaced implicit-nullable parameter declarations
  (`Type $param = null`) with explicit nullable types (`?Type $param = null`)
  across the failure providers, area config, and abstract config classes, removing
  the "Implicitly marking parameter as nullable is deprecated" notices.

### Security

- Widened the `symfony/dom-crawler` requirement from `>=2.7 <5.0` to
  `^5.4.52 || ^6.4.40 || ^7.4.12 || ^8.0.12`. The previous constraint could only
  resolve to versions affected by **CVE-2026-45071** (XXE / local file disclosure
  in `DomCrawler::addXmlContent()` via `validateOnParse = true`), because no fix
  exists for the 2.x–4.x branches. The new constraint pins to the patched releases
  and aligns with the Symfony components shipped by Magento 2.4.x.

## [2.5.3] - 2025-10-06

### Changed

- Updated dependencies and removed leftover `console.log` debug output.

## [2.5.2] - 2025-08-12

### Changed

- `composer.json` maintenance.

## [2.5.1] - 2025-06-24

### Changed

- `composer.json` maintenance.

## [2.5.0] - 2025-06-17

### Changed

- `composer.json` maintenance.

[2.5.4]: https://github.com/hryvinskyi/magento2-invisible-captcha/compare/2.5.3...2.5.4
[2.5.3]: https://github.com/hryvinskyi/magento2-invisible-captcha/compare/2.5.2...2.5.3
[2.5.2]: https://github.com/hryvinskyi/magento2-invisible-captcha/compare/2.5.1...2.5.2
[2.5.1]: https://github.com/hryvinskyi/magento2-invisible-captcha/compare/2.5.0...2.5.1
[2.5.0]: https://github.com/hryvinskyi/magento2-invisible-captcha/compare/2.4.10...2.5.0
