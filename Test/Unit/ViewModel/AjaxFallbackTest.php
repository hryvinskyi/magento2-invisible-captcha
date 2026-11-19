<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\ViewModel;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\ViewModel\AjaxFallback;
use Magento\Framework\Serialize\Serializer\JsonHexTag;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AjaxFallbackTest extends TestCase
{
    /** @var ConfigInterface&MockObject */
    private ConfigInterface $config;
    private AjaxFallback $viewModel;

    protected function setUp(): void
    {
        $this->config = $this->createMock(ConfigInterface::class);
        $this->viewModel = new AjaxFallback($this->config, new JsonHexTag());
    }

    public function testConfigJsonCarriesTheAdminSettings(): void
    {
        $this->config->method('getAjaxMarkerParams')->willReturn(['__ajax', 'p']);
        $this->config->method('getBackgroundAjaxMarkerParams')->willReturn(['__preload']);
        $this->config->method('getFilterAnchorSelector')->willReturn('.filter-options a');
        $this->config->method('getFilterParamPattern')->willReturn('^filter_(.+)$');

        $this->assertSame(
            [
                'ajaxMarkerParams' => ['__ajax', 'p'],
                'backgroundAjaxMarkerParams' => ['__preload'],
                'filterAnchorSelector' => '.filter-options a',
                'filterParamPattern' => '^filter_(.+)$',
            ],
            json_decode($this->viewModel->getConfigJson(), true)
        );
    }

    public function testMarkerListsSerializeAsJsonArrays(): void
    {
        $this->config->method('getAjaxMarkerParams')->willReturn(['a' => 'x', 'b' => 'y']);
        $this->config->method('getBackgroundAjaxMarkerParams')->willReturn([5 => 'z']);
        $this->config->method('getFilterAnchorSelector')->willReturn('');
        $this->config->method('getFilterParamPattern')->willReturn('');

        $json = $this->viewModel->getConfigJson();

        $this->assertStringContainsString('"ajaxMarkerParams":["x","y"]', $json);
        $this->assertStringContainsString('"backgroundAjaxMarkerParams":["z"]', $json);
    }

    public function testAnAdminValueCannotCloseTheDataBlock(): void
    {
        $this->config->method('getAjaxMarkerParams')->willReturn([]);
        $this->config->method('getBackgroundAjaxMarkerParams')->willReturn([]);
        $this->config->method('getFilterAnchorSelector')->willReturn('a</script><script>alert(1)</script>');
        $this->config->method('getFilterParamPattern')->willReturn('');

        $json = $this->viewModel->getConfigJson();

        $this->assertStringNotContainsString('<', $json);
        $this->assertStringNotContainsString('>', $json);
    }
}
