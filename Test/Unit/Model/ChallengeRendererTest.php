<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderPoolInterface;
use Hryvinskyi\InvisibleCaptcha\Api\RefIdGeneratorInterface;
use Hryvinskyi\InvisibleCaptcha\Model\ChallengeRenderer;
use Hryvinskyi\ThemeAssets\Api\AssetRendererInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Escaper;
use Magento\Framework\Locale\ResolverInterface as LocaleResolver;
use Magento\Framework\Serialize\Serializer\JsonHexTag;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChallengeRendererTest extends TestCase
{
    /** @var ConfigInterface&MockObject */
    private ConfigInterface $config;
    /** @var ProviderPoolInterface&MockObject */
    private ProviderPoolInterface $providerPool;
    /** @var AssetRendererInterface&MockObject */
    private AssetRendererInterface $assetRenderer;
    private ChallengeRenderer $renderer;

    /**
     * Decode the JSON config data block the renderer prints into the page.
     *
     * @return array<string, mixed>
     */
    private static function scriptConfig(string $html): array
    {
        $pattern = '#<script type="application/json" id="' . ChallengeRenderer::CONFIG_ELEMENT_ID
            . '">(.*?)</script>#s';
        if (preg_match($pattern, $html, $match) !== 1) {
            self::fail('the config data block is printed');
        }
        $decoded = json_decode($match[1], true);
        self::assertIsArray($decoded);

        $config = [];
        foreach ($decoded as $key => $value) {
            $config[(string)$key] = $value;
        }

        return $config;
    }

    protected function setUp(): void
    {
        $this->config = $this->createMock(ConfigInterface::class);
        $this->providerPool = $this->createMock(ProviderPoolInterface::class);
        $this->assetRenderer = $this->createMock(AssetRendererInterface::class);

        $url = $this->createMock(UrlInterface::class);
        $url->method('getBaseUrl')->willReturn('https://example.com/');

        $escaper = $this->createMock(Escaper::class);
        $escaper->method('escapeHtml')->willReturnCallback(static fn (string $v): string => $v);
        $escaper->method('escapeHtmlAttr')->willReturnCallback(static fn (string $v): string => $v);
        $escaper->method('escapeUrl')->willReturnCallback(static fn (string $v): string => $v);

        $locale = $this->createMock(LocaleResolver::class);
        $locale->method('getLocale')->willReturn('en_US');

        $request = $this->createMock(HttpRequest::class);
        $request->method('getServer')->willReturn('example.com');

        $refId = $this->createMock(RefIdGeneratorInterface::class);
        $refId->method('format')->willReturn('A7F2 · 3K9M');

        $secureRenderer = $this->createMock(SecureHtmlRenderer::class);
        $secureRenderer->method('renderTag')->willReturnCallback(
            static fn (string $tag, array $attrs, ?string $content = null, bool $text = true): string
                => '<' . $tag . '>' . (string)$content . '</' . $tag . '>'
        );

        $this->renderer = new ChallengeRenderer(
            $this->config,
            $this->providerPool,
            $url,
            $escaper,
            $locale,
            $request,
            $this->assetRenderer,
            $refId,
            $secureRenderer,
            new JsonHexTag()
        );
    }

    public function testRenderSubstitutesPlaceholdersAndEmitsProviderConfig(): void
    {
        $provider = $this->createMock(ProviderInterface::class);
        $provider->method('getCode')->willReturn(ProviderInterface::CODE_TURNSTILE);
        $provider->method('getSiteKey')->willReturn('site-key');
        $provider->method('getResponseParamName')->willReturn('cf-turnstile-response');
        $provider->method('getClientScriptUrl')->willReturn('https://challenges.cloudflare.com/turnstile/v0/api.js');
        $provider->method('getRenderConfig')->willReturn(['provider' => 'turnstile', 'size' => 'flexible']);

        $this->providerPool->method('getRouteGateProvider')->willReturn($provider);
        $this->providerPool->method('getFallbackProvider')->willReturn(null);
        $this->config->method('getRouteFallbackDelay')->willReturn(15);
        $this->config->method('getSupportEmail')->willReturn('');

        $this->assetRenderer->method('getContent')
            ->willReturn('<html lang="{{lang}}"><title>{{title}}</title>{{ref_id}}{{host}}{{styles}}{{script}}</html>');
        $this->assetRenderer->method('renderStyle')->willReturn('/* css */');
        $this->assetRenderer->expects($this->once())->method('renderScript')
            ->with('Hryvinskyi_InvisibleCaptcha::inline_challenge/script.js')
            ->willReturn('<script src="bootstrap.js"></script>');

        $html = $this->renderer->render('A7F23K9M');

        self::assertStringContainsString('Security Check', $html);
        self::assertStringContainsString('A7F2 · 3K9M', $html);
        self::assertStringNotContainsString('window.hryvinskyiCaptcha', $html);
        // The data block comes before the bootstrap script that reads it.
        self::assertLessThan(
            strpos($html, '<script src="bootstrap.js">'),
            strpos($html, 'id="' . ChallengeRenderer::CONFIG_ELEMENT_ID . '"')
        );
        $config = self::scriptConfig($html);
        self::assertSame('https://example.com/invisiblecaptcha/verify', $config['verifyUrl']);
        self::assertSame('A7F23K9M', $config['refId']);
        self::assertSame('cf-turnstile-response', $config['responseParam']);
        self::assertSame(15000, $config['fallbackDelay']);
        self::assertFalse($config['fallbackEnabled']);
        self::assertStringNotContainsString('{{lang}}', $html);
        self::assertStringNotContainsString('{{script}}', $html);
        // Unset appearance config falls back to the bundled default palette.
        self::assertStringContainsString(':root{--primary:#2f6bd8;--primary-deep:#2557b6;--primary-soft:rgba(47,107,216,0.12);}', $html);
    }

    public function testAppliesConfiguredAccentColorsAndRejectsInvalidValues(): void
    {
        $provider = $this->createMock(ProviderInterface::class);
        $provider->method('getCode')->willReturn(ProviderInterface::CODE_TURNSTILE);
        $provider->method('getRenderConfig')->willReturn([]);
        $this->providerPool->method('getRouteGateProvider')->willReturn($provider);
        $this->providerPool->method('getFallbackProvider')->willReturn(null);
        $this->config->method('getRouteFallbackDelay')->willReturn(15);
        $this->config->method('getSupportEmail')->willReturn('');

        // Valid hex/rgb values are honoured; a url()-smuggling value is rejected by
        // the shape allowlist and falls back to the default.
        $this->config->method('getChallengePrimaryColor')->willReturn('#0a7d55');
        $this->config->method('getChallengePrimaryColorDeep')->willReturn('rgb(8, 100, 68)');
        $this->config->method('getChallengePrimaryColorSoft')->willReturn('url(https://evil.example/beacon)');

        $this->assetRenderer->method('getContent')->willReturn('{{styles}}');
        $this->assetRenderer->method('renderStyle')->willReturn('');
        $this->assetRenderer->method('renderScript')->willReturn('');

        $html = $this->renderer->render('A7F23K9M');

        self::assertStringContainsString('--primary:#0a7d55', $html);
        self::assertStringContainsString('--primary-deep:rgb(8, 100, 68)', $html);
        self::assertStringContainsString('--primary-soft:rgba(47,107,216,0.12)', $html);
        self::assertStringNotContainsString('evil.example', $html);
    }

    public function testRenderIncludesFallbackBlockWhenConfigured(): void
    {
        $primary = $this->createMock(ProviderInterface::class);
        $primary->method('getCode')->willReturn(ProviderInterface::CODE_TURNSTILE);
        $primary->method('getRenderConfig')->willReturn([]);

        $fallback = $this->createMock(ProviderInterface::class);
        $fallback->method('getCode')->willReturn(ProviderInterface::CODE_RECAPTCHA_V2_CHECKBOX);
        $fallback->method('getSiteKey')->willReturn('fallback-key');
        $fallback->method('getResponseParamName')->willReturn('g-recaptcha-response');
        $fallback->method('getClientScriptUrl')->willReturn('https://www.google.com/recaptcha/api.js');
        $fallback->method('getRenderConfig')->willReturn([]);

        $this->providerPool->method('getRouteGateProvider')->willReturn($primary);
        $this->providerPool->method('getFallbackProvider')->willReturn($fallback);
        $this->config->method('getRouteFallbackDelay')->willReturn(10);
        $this->config->method('getSupportEmail')->willReturn('');

        $this->assetRenderer->method('getContent')->willReturn('{{script}}');
        $this->assetRenderer->method('renderStyle')->willReturn('');
        $this->assetRenderer->method('renderScript')->willReturn('');

        $config = self::scriptConfig($this->renderer->render('A7F23K9M'));

        self::assertTrue($config['fallbackEnabled']);
        self::assertIsArray($config['fallback']);
        self::assertSame('g-recaptcha-response', $config['fallback']['responseParam']);
        self::assertSame('fallback-key', $config['fallback']['siteKey']);
    }

    public function testConfigValueCannotCloseTheDataBlock(): void
    {
        $provider = $this->createMock(ProviderInterface::class);
        $provider->method('getCode')->willReturn(ProviderInterface::CODE_TURNSTILE);
        $provider->method('getSiteKey')->willReturn('</script><script>alert(1)</script>');
        $provider->method('getRenderConfig')->willReturn([]);
        $this->providerPool->method('getRouteGateProvider')->willReturn($provider);
        $this->providerPool->method('getFallbackProvider')->willReturn(null);
        $this->config->method('getSupportEmail')->willReturn('');

        $this->assetRenderer->method('getContent')->willReturn('{{script}}');
        $this->assetRenderer->method('renderStyle')->willReturn('');
        $this->assetRenderer->method('renderScript')->willReturn('');

        $html = $this->renderer->render('A7F23K9M');

        self::assertSame(1, substr_count($html, '</script>'));
        self::assertSame('</script><script>alert(1)</script>', self::scriptConfig($html)['siteKey']);
    }
}
