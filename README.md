# Hryvinskyi_InvisibleCaptcha

Multi-provider invisible captcha **and** bot protection for Magento 2.

Version 3 merges the former `Hryvinskyi_TurnstileProtection` module into a single,
provider-agnostic extension that protects both **individual forms** and **whole
routes**, using whichever CAPTCHA provider you configure.

## Providers

| Code | Provider | Mode |
|------|----------|------|
| `recaptcha_v2_checkbox` | Google reCAPTCHA v2 — "I'm not a robot" | visible checkbox |
| `recaptcha_v2_invisible` | Google reCAPTCHA v2 — invisible badge | invisible |
| `recaptcha_v3` | Google reCAPTCHA v3 | invisible, score-based |
| `recaptcha_enterprise` | Google reCAPTCHA Enterprise | invisible, score-based (assessments API) |
| `turnstile` | Cloudflare Turnstile | managed / invisible |

## Capabilities

- **Form protection** — invisibly protects the same surface as Magento's native
  reCAPTCHA suite: customer **login** (incl. AJAX popup & checkout), **register**,
  **forgot password**, **account edit**, **contact**, **newsletter**,
  **send-to-friend**, **product review**, **share wishlist**, **apply coupon**
  (cart), **PayPal Payflow Pro**, and the admin **login** / **forgot-password**
  forms. Each is toggled independently; score-based providers also support a
  per-form threshold.
- **WebAPI / headless protection** — REST and GraphQL validation for
  **place order**, **apply coupon** (checkout), **in-store pickup place order**,
  and **resend confirmation email**. Protected endpoints require the captcha
  token in the `X-Captcha-Token` request header (`X-ReCaptcha` also accepted).
  A `hryvinskyiInvisibleCaptchaConfig(formType:)` GraphQL query exposes the
  active provider's client config for headless storefronts. Endpoint coverage is
  extensible via `Api\Webapi\WebapiConfigProviderInterface`.
- **Route protection** — a full-page interstitial challenge for requests that
  match a Cloudflare-style rule expression (field / operator / value rows joined
  by AND/OR). A verified visitor receives an HMAC-signed cookie and is keyed into
  a separate Varnish cache bucket via the HTTP context. An optional **fallback
  provider** is revealed on the challenge page after a delay. The interstitial is
  a self-contained, system-font page (no external font/CDN requests) whose accent
  color is themeable from the admin.
- **robots.txt-aware gating** — the rule field **Blocked by robots.txt**
  (`robots_txt_blocked eq 1`) challenges every request for a URL your
  robots.txt disallows. The served robots.txt is resolved per website — a
  physical `pub/robots.txt` wins, otherwise the *Search Engine Robots* custom
  instructions — and evaluated with RFC 9309 semantics: user-agent group
  selection with `*` fallback, longest-match precedence, `Allow` winning
  specificity ties, `*` wildcards and `$` end anchors. Legitimate crawlers
  never fetch disallowed URLs (and can stay in *Excluded User Agents*), so the
  rule surfaces exactly the bots that ignore robots.txt. Missing or empty
  robots.txt never challenges (fail-safe).
- **Rule tester** — a *Test Rules* panel under the rules editor simulates any
  storefront request (URL or path, method, User-Agent, client IP, referer,
  store view) against the rules **as currently edited, before saving**, and
  reports whether it would pass or be challenged: per-condition ✓/✗ trace with
  the actual field values, bypass reasons (excluded IP / user agent, verify
  endpoint), scope context (protection disabled, provider unconfigured) and
  warnings. The simulation runs under full store emulation; the full action
  name is auto-resolved through URL rewrites (SEO URLs, one redirect hop) and
  the front-name → route-id map, and can be overridden manually.

## Installation

```bash
composer require hryvinskyi/magento2-invisible-captcha
bin/magento module:enable Hryvinskyi_InvisibleCaptcha
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy
```

Dependencies: `hryvinskyi/magento2-base`, `hryvinskyi/magento2-theme-assets`,
`hryvinskyi/magento2-configuration-fields`, `symfony/http-client` + PSR-7/17/18.

## Configuration

*Stores → Configuration → Hryvinskyi → Invisible Captcha & Bot Protection*
(`hryvinskyi_invisible_captcha/*`):

- **General** — master switch, active provider, lazy-load, disable-submit, debug.
- **Provider Credentials** — site/secret keys (+ Enterprise project id, widget
  options) per provider. Secret keys are encrypted and flagged sensitive.
- **Form Protection** — per-form toggles and score thresholds (storefront + admin).
- **Route Protection** — rules editor, route-gate provider override, fallback
  provider, cookie lifetime, IP / user-agent exclusions, AJAX-marker params, etc.
  The *Ignored Filter Params*, *AJAX Marker Params* and *Background AJAX Marker
  Params* lists use tag-style inputs (via `hryvinskyi/magento2-configuration-fields`);
  values are still stored newline-separated, so existing data is untouched.
  Includes a **Challenge Page Appearance** sub-group that themes the interstitial
  accent palette — `primary_color` (#2f6bd8), `primary_color_deep` (#2557b6) and
  `primary_color_soft` (rgba(47,107,216,0.12)) under
  `hryvinskyi_invisible_captcha/route_protection/appearance/*`, injected as
  CSP-safe `--primary*` overrides and configurable per store view.
- **Advanced** — outbound verification HTTP timeout.

## Architecture (extension points)

- `Api\Provider\ProviderInterface` + `ProviderPoolInterface` — add a provider by
  implementing the interface and adding it to the `Model\Provider\Pool` DI array.
- `Api\Verification\VerifierInterface` / `VerificationRequestInterface` /
  `VerificationResultInterface` — provider-agnostic verification.
- `Api\Filter\FieldProviderInterface` / `OperatorProviderInterface` — extend the
  route rule engine with custom fields/operators via DI arrays. Optionally
  implement `Api\Filter\OperatorMetadataInterface` (value shape: text /
  text_required / list / pattern / number) and
  `Api\Filter\FieldValueHintInterface` (format pattern, message, placeholder)
  to plug into the editor's dynamic value validation.
- `Api\RobotsTxt\{SourceInterface, ParserInterface, MatcherInterface}` — swap
  where robots.txt content comes from, how it is parsed, or how rules are
  matched (all bound via `di.xml` preferences).
- `Model\Strategy\{Token,Failure}\*` — pluggable token extraction & failure handling.

## WebAPI / checkout note

The `place_order`, `coupon_code` (checkout), `store_pickup` and
`resend_confirmation_email` keys validate **server-side** on the WebAPI/GraphQL
call. They are **off by default**. Before enabling one, make sure your storefront
/ headless client sends the captcha token in the `X-Captcha-Token` header on that
call (for score-based providers, execute the provider with the matching action,
e.g. `place_order`) — otherwise those requests will be rejected. The token for
form-level (non-WebAPI) submissions is handled automatically via the hidden
`hryvinskyi_invisible_token` field.

## Verify endpoint & caching

The route-gate challenge POSTs to `invisiblecaptcha/verify`. On success it sets the
`hryvinskyi_captcha_verified` cookie and the `CAPTCHA_VERIFIED` HTTP-context vary
key. After deploying v3, run a **full page cache flush** (the vary key / cookie /
`X-InvisibleCaptcha-Challenge` header were renamed from the old Turnstile names).

## Upgrading from v2.x / TurnstileProtection

Run `setup:upgrade`. The data patch `MigrateLegacyCaptchaConfig` copies your old
`hryvinskyi_invisible_captcha/*` (reCAPTCHA v3) **and** `hryvinskyi_turnstile/*`
settings into the new tree (encrypting the legacy plaintext v3 secret). The
standalone `Hryvinskyi_TurnstileProtection` module is removed — disable it after
upgrading.

## Migrating from Magento's native Google reCAPTCHA

If the store already uses Magento's built-in Google reCAPTCHA (`Magento_ReCaptcha*`),
import its configuration into this module with:

```bash
bin/magento hryvinskyi:invisible-captcha:migrate-recaptcha --dry-run   # preview the change set
bin/magento hryvinskyi:invisible-captcha:migrate-recaptcha             # apply
```

The command reads every `recaptcha_frontend/*` and `recaptcha_backend/*` row from
`core_config_data` (all scopes) and writes the equivalent
`hryvinskyi_invisible_captcha/*` settings:

- **Credentials** — v3, v2-checkbox and v2-invisible site/secret keys map to the
  matching provider under *Provider Credentials*. The native module stores **both**
  keys encrypted; the secret key is copied verbatim (this module's field is
  encrypted too, so the ciphertext stays valid under the same crypt key), while the
  **site key is decrypted** on copy because this module stores it in plain text.
  Run the command on the installation that owns the crypt key. Frontend keys win
  when frontend and backend differ.
- **Per-form selectors** — each `type_for/<form>` that isn't "disabled" turns on the
  corresponding form under *Form Protection* (the native `place_order` gate enables
  both checkout **and** in-store pickup here). The v3 section-wide score threshold is
  fanned out to each enabled v3 form.
- **Derived** — the active provider (most-used across the enabled forms), the master
  switch and the form-protection switches are set so protection stays live.
- **Status hand-over** — after copying, each migrated native `type_for/*` selector
  row is **cleared**, so the built-in reCAPTCHA stops challenging those forms and
  this module takes over in the same run (`source_disabled` in the summary table).
  Native credentials are left in place, so the switch is easy to revert.

Existing values in the target tree are never overwritten unless you pass `--force`.
The command flushes the config cache and prints a per-path summary table.

> Upgrading from a version whose migration copied the site key without decrypting
> it? Re-run with `--force` to overwrite the broken ciphertext values.

> **`skipped_undecryptable` rows / missing site keys?** The installation's crypt key
> (`app/etc/env.php`) cannot decrypt those values — typical when the database was
> imported from another environment without its crypt key. Nothing else encrypted
> (payment keys, SMTP passwords) will decrypt on such an installation either.
> Restore the original crypt key (all versions, in order) or re-enter the keys in
> the admin, then re-run the command.

Because the hand-over disables the native reCAPTCHA at config level, physically
removing its modules is **optional**. If you still want them gone, the companion
metapackage `hryvinskyi/magento2-invisible-captcha-recaptcha-replace` `replace`s
every `magento/module-re-captcha-*` package (Two-Factor Auth and `security.txt`
from `magento/security-package` are left untouched). **Caution:** other installed
code may compile against the reCAPTCHA API modules — notably the bundled
PayPal Braintree module implements
`Magento\ReCaptchaWebapiApi\Api\WebapiValidationConfigProviderInterface`, so
removing all 25 packages breaks `setup:di:compile` on standard installs. On such
stores prefer the config-level disable above.

## CLI

```bash
bin/magento hryvinskyi:invisible-captcha:disable [global|frontend|adminhtml] [--website_id=N]
bin/magento hryvinskyi:invisible-captcha:migrate-recaptcha [--dry-run] [--force]
```
