<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model;

use Hryvinskyi\InvisibleCaptcha\Api\ChallengeRendererInterface;
use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderPoolInterface;
use Hryvinskyi\InvisibleCaptcha\Api\RefIdGeneratorInterface;
use Hryvinskyi\InvisibleCaptcha\Controller\Router\VerificationRouter;
use Hryvinskyi\ThemeAssets\Api\AssetRendererInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Escaper;
use Magento\Framework\HTTP\PhpEnvironment\Request as HttpRequest;
use Magento\Framework\Locale\ResolverInterface as LocaleResolver;
use Magento\Framework\Serialize\Serializer\JsonHexTag;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

/**
 * Assembles the inline interstitial challenge HTML, provider-agnostically.
 * The active route-gate provider (and optional fallback) drive the client-side
 * widget configuration, printed as a JSON data block the bootstrap script reads.
 */
class ChallengeRenderer implements ChallengeRendererInterface
{
    /**
     * Id of the JSON block holding the bootstrap script's configuration; the
     * script looks the block up by this id.
     */
    public const CONFIG_ELEMENT_ID = 'hryvinskyi-captcha-config';

    private const ASSET_TEMPLATE = 'Hryvinskyi_InvisibleCaptcha::inline_challenge/template.html';
    private const ASSET_STYLES = 'Hryvinskyi_InvisibleCaptcha::inline_challenge/styles.css';
    private const ASSET_SCRIPT = 'Hryvinskyi_InvisibleCaptcha::inline_challenge/script.js';

    /** Defaults mirroring styles.css `:root`; used when config is empty/invalid. */
    private const DEFAULT_PRIMARY = '#2f6bd8';
    private const DEFAULT_PRIMARY_DEEP = '#2557b6';
    private const DEFAULT_PRIMARY_SOFT = 'rgba(47,107,216,0.12)';

    /**
     * Accepted CSS color forms — an allowlist of shapes, not just characters, so
     * function calls other than rgb/hsl (notably url(), image-set(), env()) can
     * never slip through into the injected declaration.
     */
    private const CSS_COLOR_PATTERNS = [
        '/^#(?:[0-9a-fA-F]{3,4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/',   // #rgb / #rgba / #rrggbb / #rrggbbaa
        '/^(?:rgb|rgba|hsl|hsla)\([0-9.,%\s\/]{1,60}\)$/i',          // rgb()/rgba()/hsl()/hsla()
        '/^[a-zA-Z]{1,32}$/',                                        // named color / transparent / currentColor
    ];

    /**
     * @param ConfigInterface $config
     * @param ProviderPoolInterface $providerPool
     * @param UrlInterface $url
     * @param Escaper $escaper
     * @param LocaleResolver $localeResolver
     * @param RequestInterface $request
     * @param AssetRendererInterface $assetRenderer
     * @param RefIdGeneratorInterface $refIdGenerator
     * @param SecureHtmlRenderer $secureRenderer
     * @param JsonHexTag $jsonSerializer
     */
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly ProviderPoolInterface $providerPool,
        private readonly UrlInterface $url,
        private readonly Escaper $escaper,
        private readonly LocaleResolver $localeResolver,
        private readonly RequestInterface $request,
        private readonly AssetRendererInterface $assetRenderer,
        private readonly RefIdGeneratorInterface $refIdGenerator,
        private readonly SecureHtmlRenderer $secureRenderer,
        private readonly JsonHexTag $jsonSerializer
    ) {
    }

    /**
     * @inheritDoc
     */
    public function render(string $refId): string
    {
        $primary = $this->providerPool->getRouteGateProvider();
        $template = $this->assetRenderer->getContent(self::ASSET_TEMPLATE);
        // Bundled stylesheet first, then the admin-configured accent override so
        // its :root wins over the defaults baked into styles.css.
        $styles = $this->assetRenderer->renderStyle(self::ASSET_STYLES) . $this->renderColorOverride();
        $script = $this->renderScriptConfig($refId, $primary)
            . $this->assetRenderer->renderScript(self::ASSET_SCRIPT);

        return strtr($template, [
            '{{lang}}' => $this->escaper->escapeHtmlAttr($this->resolveLang()),
            '{{title}}' => $this->escaper->escapeHtml((string)__('Security Check')),
            '{{conn_secure}}' => $this->escaper->escapeHtml((string)__('Secure connection')),
            '{{eyebrow}}' => $this->escaper->escapeHtml((string)__('Security check')),
            '{{heading}}' => $this->escaper->escapeHtml((string)__('Verifying your access')),
            '{{lede_verifying}}' => $this->escaper->escapeHtml(
                (string)__("We're automatically verifying that your connection is secure – this usually only takes a moment.")
            ),
            '{{row_connection}}' => $this->escaper->escapeHtml((string)__('Connection')),
            '{{status_secure}}' => $this->escaper->escapeHtml((string)__('Secure')),
            '{{row_request}}' => $this->escaper->escapeHtml((string)__('Request')),
            '{{status_confirmed}}' => $this->escaper->escapeHtml((string)__('Confirmed')),
            '{{row_human}}' => $this->escaper->escapeHtml((string)__('Human verification')),
            '{{status_checking}}' => $this->escaper->escapeHtml((string)__('Checking')),
            '{{hc_note}}' => $this->escaper->escapeHtml((string)__('Additional verification required')),
            '{{hc_title}}' => $this->escaper->escapeHtml((string)__("Confirm you're human")),
            '{{hc_arrow_label}}' => $this->renderArrowLabel(),
            '{{rc_label}}' => $this->escaper->escapeHtml((string)__("I'm not a robot")),
            '{{hc_help}}' => $this->escaper->escapeHtml(
                (string)__('Having trouble? Try reloading the page and/or disabling browser extensions.')
            ),
            '{{lede_failed}}' => $this->escaper->escapeHtml(
                (string)__('Verification failed. Please try reloading the page.')
            ),
            '{{fail_support}}' => $this->renderSupportNote(),
            '{{try_again}}' => $this->escaper->escapeHtml((string)__('Try again')),
            '{{foot_verifying}}' => $this->escaper->escapeHtml((string)__('Verifying connection…')),
            '{{foot_waiting}}' => $this->escaper->escapeHtmlAttr((string)__('Awaiting your verification')),
            '{{link_privacy}}' => $this->escaper->escapeHtml((string)__('Privacy policy')),
            '{{link_terms}}' => $this->escaper->escapeHtml((string)__('Terms of service')),
            '{{link_cookies}}' => $this->escaper->escapeHtml((string)__('Cookies')),
            '{{ref_id}}' => $this->escaper->escapeHtml($this->refIdGenerator->format($refId)),
            '{{host}}' => $this->escaper->escapeHtml($this->resolveHost()),
            '{{primary_api}}' => $this->escaper->escapeUrl($primary->getClientScriptUrl()),
            '{{styles}}' => $styles,
            '{{script}}' => $script,
        ]);
    }

    /**
     * Build a CSP-safe inline `<style>` that overrides the `--primary*` custom
     * properties from the admin appearance config. Rendered through the same
     * SecureHtmlRenderer the bundled assets use, so it carries the CSP nonce.
     */
    private function renderColorOverride(): string
    {
        $css = sprintf(
            ':root{--primary:%s;--primary-deep:%s;--primary-soft:%s;}',
            $this->sanitizeCssColor($this->config->getChallengePrimaryColor(), self::DEFAULT_PRIMARY),
            $this->sanitizeCssColor($this->config->getChallengePrimaryColorDeep(), self::DEFAULT_PRIMARY_DEEP),
            $this->sanitizeCssColor($this->config->getChallengePrimaryColorSoft(), self::DEFAULT_PRIMARY_SOFT)
        );

        return $this->secureRenderer->renderTag('style', [], $css, false);
    }

    /**
     * Return the value only if it matches one of the accepted CSS color shapes
     * (defence against config-injected declarations breaking out of the property
     * or smuggling a url()/beacon); else the default.
     */
    private function sanitizeCssColor(string $value, string $default): string
    {
        $value = trim($value);
        if ($value === '' || strlen($value) > 64) {
            return $default;
        }

        foreach (self::CSS_COLOR_PATTERNS as $pattern) {
            if (preg_match($pattern, $value) === 1) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * "Check the box below to continue" with the key phrase emphasised.
     */
    private function renderArrowLabel(): string
    {
        $emphasis = '<strong>' . $this->escaper->escapeHtml((string)__('the box')) . '</strong>';

        return (string)__('Check %1 below to continue', $emphasis);
    }

    /**
     * Failure-state support line linking the store's customer-service email.
     */
    private function renderSupportNote(): string
    {
        $email = $this->config->getSupportEmail();
        if ($email === '') {
            return '';
        }

        $link = '<a href="mailto:' . $this->escaper->escapeHtmlAttr($email) . '">'
            . $this->escaper->escapeHtml($email) . '</a>';

        return '<p class="fail-support">'
            . (string)__('If the issue persists, please contact our customer service at %1', $link)
            . '</p>';
    }

    /**
     * The bootstrap script's configuration as a JSON data block. A data block
     * is never executed, so it needs no CSP nonce; JsonHexTag escapes `<` and
     * `>` so no value can close the block early.
     */
    private function renderScriptConfig(string $refId, ProviderInterface $primary): string
    {
        return '<script type="application/json" id="' . self::CONFIG_ELEMENT_ID . '">'
            . $this->jsonSerializer->serialize($this->buildScriptConfig($refId, $primary))
            . '</script>';
    }

    /**
     * The provider-tagged configuration of the bootstrap script.
     *
     * @return array<string, mixed>
     */
    private function buildScriptConfig(string $refId, ProviderInterface $primary): array
    {
        $config = [
            'verifyUrl' => rtrim($this->url->getBaseUrl(), '/') . '/' . VerificationRouter::VERIFY_PATH,
            'refId' => $refId,
            'provider' => $primary->getCode(),
            'siteKey' => $primary->getSiteKey(),
            'responseParam' => $primary->getResponseParamName(),
            'scriptUrl' => $primary->getClientScriptUrl(),
            'render' => $primary->getRenderConfig(),
            'fallbackEnabled' => false,
            'fallbackDelay' => $this->config->getRouteFallbackDelay() * 1000,
        ];

        $fallback = $this->providerPool->getFallbackProvider();
        if ($fallback !== null && $fallback->getCode() !== $primary->getCode()) {
            $config['fallbackEnabled'] = true;
            $config['fallback'] = [
                'provider' => $fallback->getCode(),
                'siteKey' => $fallback->getSiteKey(),
                'responseParam' => $fallback->getResponseParamName(),
                'scriptUrl' => $fallback->getClientScriptUrl(),
                'render' => $fallback->getRenderConfig(),
            ];
        }

        return $config;
    }

    /**
     * Resolve current locale into a simple HTML lang attribute value.
     */
    private function resolveLang(): string
    {
        $locale = (string)$this->localeResolver->getLocale();
        if ($locale === '') {
            return 'en';
        }
        $primary = strstr($locale, '_', true);

        return $primary !== false ? strtolower($primary) : strtolower($locale);
    }

    /**
     * Resolve the current request host for display in the footer.
     */
    private function resolveHost(): string
    {
        if (!$this->request instanceof HttpRequest) {
            return '';
        }

        $host = $this->request->getServer('HTTP_HOST', '');

        return is_string($host) ? $host : '';
    }
}
