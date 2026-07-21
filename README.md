# Hryvinskyi_InvisibleCaptcha

> Multi-provider invisible CAPTCHA & bot protection for Magento 2 — protect individual forms, headless APIs, and whole routes with whichever provider you choose.

**Version 3** merges the former `Hryvinskyi_TurnstileProtection` module into a single, provider-agnostic extension.

## Table of contents

- [Highlights](#highlights)
- [Supported providers](#supported-providers)
- [Installation](#installation)
- [What it protects](#what-it-protects)
  - [Storefront & admin forms](#storefront--admin-forms)
  - [REST & GraphQL (headless)](#rest--graphql-headless)
  - [Whole routes (bot challenge)](#whole-routes-bot-challenge)
- [Configuration](#configuration)
  - [Geolocation & country rules](#geolocation--country-rules)
- [Migrating from Magento's native reCAPTCHA](#migrating-from-magentos-native-recaptcha)
- [Upgrading from v2.x / TurnstileProtection](#upgrading-from-v2x--turnstileprotection)
- [CLI reference](#cli-reference)
- [Extending the module](#extending-the-module)

## Highlights

- **One extension, five providers** — Google reCAPTCHA v2 / v3 / Enterprise and Cloudflare Turnstile behind a single config; switch providers without touching your forms.
- **Form protection** — the same coverage as Magento's native reCAPTCHA suite, storefront and admin, each form toggled independently.
- **Headless-ready** — server-side validation for REST & GraphQL checkout endpoints, plus a GraphQL query that exposes the client config.
- **Route protection** — a Cloudflare-style rule engine that challenges suspicious requests with a full-page interstitial before they reach Magento.
- **Catches robots.txt violators** — challenge exactly the bots that request URLs your robots.txt disallows.
- **Painless migration** — one CLI command imports your existing native reCAPTCHA configuration and hands protection over in the same run.

## Supported providers

| Code | Provider | Mode |
|------|----------|------|
| `recaptcha_v2_checkbox` | Google reCAPTCHA v2 — "I'm not a robot" | visible checkbox |
| `recaptcha_v2_invisible` | Google reCAPTCHA v2 — invisible badge | invisible |
| `recaptcha_v3` | Google reCAPTCHA v3 | invisible, score-based |
| `recaptcha_enterprise` | Google reCAPTCHA Enterprise | invisible, score-based (assessments API) |
| `turnstile` | Cloudflare Turnstile | managed / invisible |

## Installation

```bash
composer require hryvinskyi/magento2-invisible-captcha
bin/magento module:enable Hryvinskyi_InvisibleCaptcha
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy
```

**Dependencies:** `hryvinskyi/magento2-base`, `hryvinskyi/magento2-theme-assets`, `hryvinskyi/magento2-configuration-fields`, `symfony/http-client` + PSR-7/17/18.

Then configure under *Stores → Configuration → Hryvinskyi → Invisible Captcha & Bot Protection*: pick the active provider, enter its credentials, and enable the forms you want protected.

> [!NOTE]
> Already running Magento's built-in reCAPTCHA? Import its settings with one command — see [Migrating from Magento's native reCAPTCHA](#migrating-from-magentos-native-recaptcha).

## What it protects

### Storefront & admin forms

Invisibly protects the same surface as Magento's native reCAPTCHA suite:

- **Storefront** — customer login (incl. AJAX popup & checkout), registration, forgot password, account edit, contact, newsletter, send-to-friend, product review, share wishlist, apply coupon (cart), PayPal Payflow Pro.
- **Admin** — login, forgot password.

Each form is toggled independently; score-based providers also support a per-form score threshold. The token for form submissions is handled automatically via the hidden `hryvinskyi_invisible_token` field — no storefront changes needed.

### REST & GraphQL (headless)

Server-side validation for **place order**, **apply coupon** (checkout), **in-store pickup place order**, and **resend confirmation email**. Protected endpoints require the captcha token in the `X-Captcha-Token` request header (`X-ReCaptcha` is also accepted).

A `hryvinskyiInvisibleCaptchaConfig(formType:)` GraphQL query exposes the active provider's client config for headless storefronts. Endpoint coverage is extensible via `Api\Webapi\WebapiConfigProviderInterface`.

> [!WARNING]
> The WebAPI keys (`place_order`, `coupon_code`, `store_pickup`, `resend_confirmation_email`) are **off by default**. Before enabling one, make sure your storefront / headless client sends the token in the `X-Captcha-Token` header on that call (for score-based providers, execute the provider with the matching action, e.g. `place_order`) — otherwise those requests will be rejected.

### Whole routes (bot challenge)

Requests matching a Cloudflare-style rule expression (field / operator / value rows joined by AND/OR) get a full-page interstitial challenge instead of the requested page:

1. A visitor (or bot) hits a URL matching your rules.
2. They see a self-contained, system-font challenge page (no external font/CDN requests; accent color themeable from the admin). An optional **fallback provider** is revealed after a delay.
3. On success, the visitor receives an HMAC-signed cookie and is keyed into a separate Varnish cache bucket via the HTTP context.

#### Rule fields

| Field | What it matches |
|-------|-----------------|
| `robots_txt_blocked` | The requested URL is disallowed by your robots.txt |
| `is_ajax` | XHR / background calls (`customer/section/load`, minicart, add-to-cart, login popup) |
| `is_404` | Requests dispatched to the configured no-route action |
| `country` | Visitor's country, ISO 3166-1 alpha-2 (`UA`, `DE`; `T1` = Tor via Cloudflare) — see [Geolocation](#geolocation--country-rules) |

…plus standard request fields (URL/path, method, User-Agent, IP, referer, store view), extensible via DI.

**robots.txt-aware gating.** Legitimate crawlers never fetch disallowed URLs, so `robots_txt_blocked eq 1` surfaces exactly the bots that ignore robots.txt. The served robots.txt is resolved per website — a physical `pub/robots.txt` wins, otherwise the *Search Engine Robots* custom instructions — and evaluated with RFC 9309 semantics: user-agent group selection with `*` fallback, longest-match precedence, `Allow` winning specificity ties, `*` wildcards and `$` end anchors. A missing or empty robots.txt never challenges (fail-safe). Keep well-behaved crawlers in *Excluded User Agents* if needed.

> [!TIP]
> Recommended robots rule: `robots_txt_blocked eq 1 and is_ajax eq 0` — add `or is_404 eq 1` to also challenge URL-probing bots.

**Excluded Paths** — a hard bypass list of robots.txt-style path patterns that are never challenged whatever the rules say. It is pre-filled with Magento's background endpoints (customer sections, minicart, add-to-cart, compare, review/newsletter posts, search suggest, private-content rendering), so a broad rule can't break the storefront.

**Rule tester** — a *Test Rules* panel under the rules editor simulates any storefront request (URL or path, method, User-Agent, client IP, referer, store view) against the rules **as currently edited, before saving**, and reports whether it would pass or be challenged: a per-condition ✓/✗ trace with the actual field values, bypass reasons (excluded IP / user agent, verify endpoint), scope context (protection disabled, provider unconfigured) and warnings. The simulation runs under full store emulation; the full action name is auto-resolved through URL rewrites (SEO URLs, one redirect hop) and the front-name → route-id map, and can be overridden manually.

## Configuration

*Stores → Configuration → Hryvinskyi → Invisible Captcha & Bot Protection* (`hryvinskyi_invisible_captcha/*`):

| Group | Contains |
|-------|----------|
| **General** | Master switch, active provider, lazy-load, disable-submit, debug |
| **Provider Credentials** | Site/secret keys (+ Enterprise project id, widget options) per provider; secret keys are encrypted and flagged sensitive |
| **Form Protection** | Per-form toggles and score thresholds (storefront + admin) |
| **Route Protection** | Rules editor, route-gate provider override, fallback provider, cookie lifetime, IP / user-agent exclusions, AJAX-marker params, challenge page appearance |
| **Geolocation** | Country detection source for the `country` rule field |
| **Advanced** | Outbound verification HTTP timeout |

Notes on **Route Protection**:

- The *Ignored Filter Params*, *AJAX Marker Params* and *Background AJAX Marker Params* lists use tag-style inputs (via `hryvinskyi/magento2-configuration-fields`); values are still stored newline-separated, so existing data is untouched.
- The **Challenge Page Appearance** sub-group themes the interstitial accent palette — `primary_color` (#2f6bd8), `primary_color_deep` (#2557b6) and `primary_color_soft` (rgba(47,107,216,0.12)) under `hryvinskyi_invisible_captcha/route_protection/appearance/*`, injected as CSP-safe `--primary*` overrides and configurable per store view.

### Geolocation & country rules

The `country` rule field needs a geolocation source, selected under the *Geolocation* group. Two sources ship:

| Source | Setup | Caveat |
|--------|-------|--------|
| **Cloudflare** (`CF-IPCountry` header) | Enable the zone's **IP Geolocation** toggle in Cloudflare | Only works for traffic that actually flows through Cloudflare; direct-to-origin requests have no country |
| **MaxMind database** (GeoLite2 / GeoIP2) | Upload a **Country** or **City** `.mmdb` file in the admin | Point-in-time snapshot — re-upload periodically to keep lookups accurate |

MaxMind notes:

- Prefer the smaller **GeoLite2-Country** database over City when you only need country rules; your PHP upload limits (`upload_max_filesize`, `post_max_size`) must accommodate the file — GeoLite2-City is ≈ 70 MB.
- Replacing the file, or ticking **Delete this file** on the field, removes the previous database from `var/hryvinskyi_invisible_captcha/geoip/` automatically.
- Country values are uppercase ISO 3166-1 alpha-2 (`UA`, `DE`); `T1` denotes Tor (Cloudflare only). An unknown country resolves to an **empty value**, so negative operators (`does not equal` / `not in list`) match traffic whose country could not be determined.

> [!IMPORTANT]
> The database lives in the node-local `var/` directory (`var/hryvinskyi_invisible_captcha/geoip/`), so it is not web-accessible — but on multi-node deployments it must be present on every node. Sync that directory across nodes, or upload once per node.

**Performance.** The `maxmind-db/reader` library auto-detects the optional PECL `maxminddb` C extension and, when present, switches to an mmap-based reader whose lookups are orders of magnitude faster than the bundled pure-PHP fallback. Recommended on high-traffic stores:

```bash
pecl install maxminddb
# then enable it, e.g. add to php.ini:
#   extension=maxminddb.so
```

No code or configuration changes needed — the same API is used either way, and the pure-PHP path keeps working where the extension can't be installed. The upload field shows whether the extension is active.

## Migrating from Magento's native reCAPTCHA

If the store already uses Magento's built-in Google reCAPTCHA (`Magento_ReCaptcha*`), import its configuration with:

```bash
bin/magento hryvinskyi:invisible-captcha:migrate-recaptcha --dry-run   # preview the change set
bin/magento hryvinskyi:invisible-captcha:migrate-recaptcha             # apply
```

The command reads every `recaptcha_frontend/*` and `recaptcha_backend/*` row from `core_config_data` (all scopes) and writes the equivalent `hryvinskyi_invisible_captcha/*` settings:

- **Credentials** — v3, v2-checkbox and v2-invisible site/secret keys map to the matching provider under *Provider Credentials*. The secret key ciphertext is copied verbatim (this module's field is encrypted too, so it stays valid under the same crypt key); the **site key is decrypted** on copy because this module stores it in plain text. Run the command on the installation that owns the crypt key. Frontend keys win when frontend and backend differ.
- **Per-form selectors** — each `type_for/<form>` that isn't "disabled" turns on the corresponding form under *Form Protection* (the native `place_order` gate enables both checkout **and** in-store pickup here). The v3 section-wide score threshold is fanned out to each enabled v3 form.
- **Derived** — the active provider (most-used across the enabled forms), the master switch and the form-protection switches are set so protection stays live.
- **Status hand-over** — after copying, each migrated native `type_for/*` selector row is **cleared**, so the built-in reCAPTCHA stops challenging those forms and this module takes over in the same run (`source_disabled` in the summary table). Native credentials are left in place, so the switch is easy to revert.

Existing values in the target tree are never overwritten unless you pass `--force`. The command flushes the config cache and prints a per-path summary table.

> [!NOTE]
> Upgrading from a version whose migration copied the site key without decrypting it? Re-run with `--force` to overwrite the broken ciphertext values.

> [!NOTE]
> **`skipped_undecryptable` rows / missing site keys?** The installation's crypt key (`app/etc/env.php`) cannot decrypt those values — typical when the database was imported from another environment without its crypt key. Nothing else encrypted (payment keys, SMTP passwords) will decrypt on such an installation either. Restore the original crypt key (all versions, in order) or re-enter the keys in the admin, then re-run the command.

### Removing the native reCAPTCHA modules (optional)

Because the hand-over disables the native reCAPTCHA at config level, physically removing its modules is **optional**. If you still want them gone, the companion metapackage `hryvinskyi/magento2-invisible-captcha-recaptcha-replace` `replace`s every `magento/module-re-captcha-*` package (Two-Factor Auth and `security.txt` from `magento/security-package` are left untouched).

> [!CAUTION]
> Other installed code may compile against the reCAPTCHA API modules — notably the bundled PayPal Braintree module implements `Magento\ReCaptchaWebapiApi\Api\WebapiValidationConfigProviderInterface`, so removing all 25 packages breaks `setup:di:compile` on standard installs. On such stores prefer the config-level disable above.

## Upgrading from v2.x / TurnstileProtection

Run `setup:upgrade`. The data patch `MigrateLegacyCaptchaConfig` copies your old `hryvinskyi_invisible_captcha/*` (reCAPTCHA v3) **and** `hryvinskyi_turnstile/*` settings into the new tree (encrypting the legacy plaintext v3 secret). The standalone `Hryvinskyi_TurnstileProtection` module is removed — disable it after upgrading.

> [!IMPORTANT]
> After deploying v3, run a **full page cache flush** — the vary key, cookie and `X-InvisibleCaptcha-Challenge` header were renamed from the old Turnstile names. (The route-gate challenge POSTs to `invisiblecaptcha/verify`; on success it sets the `hryvinskyi_captcha_verified` cookie and the `CAPTCHA_VERIFIED` HTTP-context vary key.)

## CLI reference

```bash
bin/magento hryvinskyi:invisible-captcha:disable [global|frontend|adminhtml] [--website_id=N]
bin/magento hryvinskyi:invisible-captcha:migrate-recaptcha [--dry-run] [--force]
```

## Extending the module

| Extension point | Purpose |
|-----------------|---------|
| `Api\Provider\ProviderInterface` + `ProviderPoolInterface` | Add a CAPTCHA provider — implement the interface, register it in the `Model\Provider\Pool` DI array |
| `Api\Verification\VerifierInterface` / `VerificationRequestInterface` / `VerificationResultInterface` | Provider-agnostic verification |
| `Api\Filter\FieldProviderInterface` / `OperatorProviderInterface` | Add custom rule fields/operators via DI arrays |
| `Api\Filter\OperatorMetadataInterface`, `Api\Filter\FieldValueHintInterface` | Optional: plug into the rule editor's dynamic value validation (value shape: text / text_required / list / pattern / number; format pattern, message, placeholder) |
| `Api\RobotsTxt\{SourceInterface, ParserInterface, MatcherInterface}` | Swap where robots.txt comes from, how it's parsed, or how rules are matched (bound via `di.xml` preferences) |
| `Api\Geo\CountrySourceInterface` + `CountrySourcePoolInterface` | Add a country detection source — register it in the `Model\Geo\SourcePool` DI array |
| `Api\Webapi\WebapiConfigProviderInterface` | Protect additional REST/GraphQL endpoints |
| `Model\Strategy\{Token,Failure}\*` | Pluggable token extraction & failure handling |
