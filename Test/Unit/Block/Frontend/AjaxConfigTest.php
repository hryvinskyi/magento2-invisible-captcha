<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Block\Frontend;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Block\Frontend\AjaxConfig;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AjaxConfigTest extends TestCase
{
    /** @var ConfigInterface&MockObject */
    private ConfigInterface $config;
    private AjaxConfig $block;

    protected function setUp(): void
    {
        $this->config = $this->createMock(ConfigInterface::class);
        $this->block = (new ObjectManager($this))->getObject(
            AjaxConfig::class,
            ['config' => $this->config]
        );
    }

    public function testGetConfigJsonReturnsConfigPayload(): void
    {
        $this->config->method('getAjaxMarkerParams')->willReturn(['__ajax', 'p']);
        $this->config->method('getBackgroundAjaxMarkerParams')->willReturn(['__preload']);
        $this->config->method('getFilterAnchorSelector')->willReturn('.filter-options a');
        $this->config->method('getFilterParamPattern')->willReturn('/^amshopby/');

        $decoded = json_decode($this->block->getConfigJson(), true);

        $this->assertSame(['__ajax', 'p'], $decoded['ajaxMarkerParams']);
        $this->assertSame(['__preload'], $decoded['backgroundAjaxMarkerParams']);
        $this->assertSame('.filter-options a', $decoded['filterAnchorSelector']);
        $this->assertSame('/^amshopby/', $decoded['filterParamPattern']);
    }

    public function testGetConfigJsonReindexesMarkerParams(): void
    {
        // Associative / gapped arrays must be flattened to a JSON array via array_values().
        $this->config->method('getAjaxMarkerParams')->willReturn(['a' => 'x', 'b' => 'y']);
        $this->config->method('getBackgroundAjaxMarkerParams')->willReturn([5 => 'z']);
        $this->config->method('getFilterAnchorSelector')->willReturn('');
        $this->config->method('getFilterParamPattern')->willReturn('');

        $json = $this->block->getConfigJson();
        $decoded = json_decode($json, true);

        $this->assertSame(['x', 'y'], $decoded['ajaxMarkerParams']);
        $this->assertSame(['z'], $decoded['backgroundAjaxMarkerParams']);
        // Reindexed values must serialize as a JSON array, not an object.
        $this->assertStringContainsString('"ajaxMarkerParams":["x","y"]', $json);
    }
}
