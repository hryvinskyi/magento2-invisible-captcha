# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.1.0] - 2026-07-11

### Added

- **`Blocked by robots.txt` route-protection rule field.** The Protection Rules
  editor gains a numeric field `robots_txt_blocked` that resolves to 1 when the
  requested URL is disallowed by the website's robots.txt — so the single rule
  `robots_txt_blocked eq 1` challenges every client that ignores robots.txt,
  while legitimate crawlers (which never fetch disallowed URLs, and typically
  sit in *Excluded User Agents* anyway) are unaffected. The served robots.txt
  is resolved per website: a physical `pub/robots.txt` wins (the web server
  delivers it directly), otherwise the *Search Engine Robots* custom
  instructions — the same website-scoped config `Magento_Robots` renders at
  `/robots.txt`. Evaluation follows RFC 9309 / Google semantics: stacked
  `User-agent` groups with the most specific agent token winning and `*` as
  fallback, tied groups merging, longest-path-match precedence with `Allow`
  winning specificity ties, `*` wildcards and `$` end anchors, comment/BOM
  stripping, and lenient relative-path normalization. Content and parsed rules
  are memoized per request; missing, empty or unreadable robots.txt never
  challenges (fail-safe). The field introduces a first-class **boolean** rule
  type: in the builder the operator list narrows to equals / does not equal
  and the Value cell renders as a strict Yes/No select (persisted as `1`/`0`,
  so the raw-expression form stays `robots_txt_blocked eq 1`). Extensible via
  the new `Api\RobotsTxt`
  `SourceInterface` / `ParserInterface` / `MatcherInterface` preferences.
  Covered by unit tests (parser, matcher, source, field) and a
  `RouteProtection\RobotsTxtRuleTest` integration scenario.
- **Tag-style editors for the route-protection parameter lists.** *Ignored
  Filter Params*, *AJAX Marker Params* and *Background AJAX Marker Params* are
  now tag inputs (type a name, press Enter) rendered by the TagList field from
  the new `hryvinskyi/magento2-configuration-fields` dependency, wired as
  `RouteParamsTagList` virtual types. Values are still stored
  newline-separated, so existing configuration keeps working unchanged.
- **"Test Rules" panel — simulate a request against the Protection Rules.**
  The rules editor gains a collapsible tester (collapsed by default, the
  header toggles it) that answers "would this page/request pass or hit the
  rules?" without leaving the admin: enter a URL (absolute or path), pick the
  store view and method, optionally set User-Agent, client IP and referer,
  and run. The verdict distinguishes **CHALLENGE** (expression
  matched and the gate would fire), **MATCHED — but no challenge** (with the
  reasons: excluded IP/user agent, verify endpoint, protection disabled,
  provider unconfigured) and **PASS**, and shows a per-condition ✓/✗ trace
  with each field's actual resolved value plus a full field-value snapshot.
  The tester evaluates the **draft rules currently in the editor** (unsaved
  changes included; falls back to the saved config), runs server-side under
  full store emulation (`Magento\Store\Model\App\Emulation`) so store-scoped
  rules, exclusion lists, credentials and robots.txt resolve exactly as on
  the live storefront, and rebuilds the real field pool around a synthetic
  request — the same field implementations the gate uses, no duplicated
  value logic. The full action name is resolved best-effort through URL
  rewrites (SEO URLs, one redirect hop honored) and the front-name →
  route-id map, with a manual override and an explicit warning when
  `action_name` conditions couldn't be grounded. Under the hood this release
  also extracts the exclusion checks into `Api\ExclusionPolicyInterface`
  (shared by the live gate and the tester) and adds
  `Api\ExpressionTracerInterface` — a no-short-circuit diagnostic twin of the
  evaluator. New admin POST endpoint `hryvinskyi_invisible_captcha/tester/run`
  (ACL `Hryvinskyi_InvisibleCaptcha::config`); new dependencies
  `magento/module-backend` and `magento/module-url-rewrite`.
- **Dynamic value validation and per-field placeholders in the rules editor.**
  Every operator now declares the shape of value it consumes via the new
  optional `Api\Filter\OperatorMetadataInterface` — `text` (equals; empty is
  legal), `text_required` (contains / starts with / ends with — empty never
  matches, so it's flagged), `list` (is in list — one or more comma/space
  separated items), `pattern` (regex operators) and `number` (relational
  operators) — and fields can expose a format hint via the new optional
  `Api\Filter\FieldValueHintInterface` (anchored pattern + message +
  placeholder). The Value cell validates live as you type: numbers for
  numeric comparisons, per-item checks for lists, regex compilation for
  pattern operators (bare and `~…~i`-delimited forms), and full-value format
  checks for exact-match operators on hinted fields — Client IP requires a
  valid IPv4/IPv6 literal, HTTP Method a method token, Full Action Name a
  `catalog_product_view`-style identifier. Fragment-taking operators
  (contains, regex, …) deliberately skip format hints. Invalid values get a
  red state plus an inline message; validation is advisory (the evaluator
  stays fail-safe) but catches conditions that would silently never match.
  All eleven built-in fields now ship example placeholders instead of the
  generic "Value". Third-party operators/fields without the new optional
  interfaces keep working unchanged (default: free text, no hint).
- **Tag input for the list operators.** With *is in list* / *is not in list*
  selected, the Value cell renders each item as a removable chip: Enter,
  comma, space, paste or blur commits the typed text (pasted content is
  split on commas/whitespace, duplicates are skipped), Backspace on an empty
  input pops the last chip, and items failing the field's format hint (e.g.
  a malformed IP on *Client IP*) are highlighted individually. The canonical
  comma-separated string lives in a hidden input with the row's form name,
  so persistence, the parser's `/[\s,]+/` split, raw-expression mode and the
  rule tester all see exactly what a plain text input would have produced.
  Switching between list and scalar operators swaps the control in place.

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
