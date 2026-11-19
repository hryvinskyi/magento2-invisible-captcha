<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Integration\RouteProtection;

use Hryvinskyi\InvisibleCaptcha\ViewModel\AjaxFallback;
use Hryvinskyi\ThemeAssets\Api\AssetRendererInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * The AJAX fallback as a storefront page renders it through the real layout,
 * view model and asset renderer: the admin configuration as a JSON data block,
 * then the script that reads it, with no configuration left on `window`; and
 * the block as a layout that sets `script_attributes` builds it.
 *
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class AjaxFallbackRenderTest extends RouteGateTestCase
{
    private const SCRIPT_PATH = 'Hryvinskyi_InvisibleCaptcha/js/ajax-fallback.js';

    public function testThePagePrintsTheConfigurationBlockBeforeTheScript(): void
    {
        $this->setConfig('hryvinskyi_invisible_captcha/route_protection/filter_anchor_selector', '.filter a</script>');
        $this->setConfig('hryvinskyi_invisible_captcha/route_protection/background_ajax_marker_params', 'is_scroll');

        $this->dispatch(self::TARGET_ROUTE);

        $body = $this->responseBody();
        $pattern = '#<script type="application/json" id="' . AjaxFallback::CONFIG_ELEMENT_ID . '">(.*?)</script>#s';
        if (preg_match($pattern, $body, $match, PREG_OFFSET_CAPTURE) !== 1) {
            self::fail('The page carries the AJAX fallback configuration block.');
        }
        $config = json_decode($match[1][0], true);
        self::assertIsArray($config);
        self::assertSame('.filter a</script>', $config['filterAnchorSelector']);
        self::assertSame(['is_scroll'], $config['backgroundAjaxMarkerParams']);

        $scriptAt = strpos($body, self::SCRIPT_PATH);
        self::assertNotFalse($scriptAt, 'the AJAX fallback script is rendered');
        self::assertGreaterThan($match[0][1], $scriptAt, 'the script comes after its configuration block');
        self::assertStringNotContainsString('hryvinskyiCaptchaAjaxConfig', $body);
    }

    public function testConfiguredScriptAttributesGoOnBothTags(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $layout = $objectManager->get(LayoutInterface::class);
        self::assertInstanceOf(LayoutInterface::class, $layout);
        $block = $layout->createBlock(Template::class, 'ajax_fallback_with_attributes', ['data' => [
            'template' => 'Hryvinskyi_InvisibleCaptcha::ajax-config.phtml',
            'view_model' => $objectManager->get(AjaxFallback::class),
            'asset_renderer' => $objectManager->get(AssetRendererInterface::class),
            'script_attributes' => ['data-keep-in-place' => 'false', 'data-quote' => 'a"b'],
        ]]);
        self::assertInstanceOf(Template::class, $block);

        $html = $block->toHtml();

        $configTag = '<script type="application/json" id="' . AjaxFallback::CONFIG_ELEMENT_ID . '"'
            . ' data-keep-in-place="false" data-quote="a&quot;b">';
        self::assertStringContainsString($configTag, $html);
        if (preg_match('#<script[^>]*' . preg_quote(self::SCRIPT_PATH, '#') . '[^>]*>#', $html, $script) !== 1) {
            self::fail('The block renders the AJAX fallback script tag.');
        }
        self::assertStringContainsString('data-keep-in-place="false"', $script[0]);
        self::assertStringNotContainsString(' defer', $script[0]);
        self::assertStringNotContainsString(' async', $script[0]);
    }
}
