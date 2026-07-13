<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model;

use Hryvinskyi\InvisibleCaptcha\Model\NoRouteAction;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;

class NoRouteActionTest extends TestCase
{
    public function testDefaultCmsNoRoutePath(): void
    {
        $noRouteAction = $this->noRouteActionFor('cms/noroute/index');

        $this->assertSame(
            ['route' => 'cms', 'controller' => 'noroute', 'action' => 'index'],
            $noRouteAction->getRouteParts()
        );
        $this->assertSame('cms_noroute_index', $noRouteAction->getFullActionName());
    }

    public function testCustomNoRoutePath(): void
    {
        $noRouteAction = $this->noRouteActionFor('vendor/errors/notFound');

        $this->assertSame('vendor_errors_notfound', $noRouteAction->getFullActionName());
    }

    public function testMissingSegmentsFallBackLikeMagentoNoRouteHandler(): void
    {
        $this->assertSame('cms_index_index', $this->noRouteActionFor('cms')->getFullActionName());
        $this->assertSame('core_index_index', $this->noRouteActionFor('')->getFullActionName());
        $this->assertSame('core_index_index', $this->noRouteActionFor(null)->getFullActionName());
    }

    public function testRoutePartsAreMemoized(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('web/default/no_route', 'default')
            ->willReturn('cms/noroute/index');

        $noRouteAction = new NoRouteAction($scopeConfig);
        $noRouteAction->getRouteParts();
        $noRouteAction->getFullActionName();
    }

    /**
     * Build the service with a fixed config value.
     *
     * @param string|null $configValue
     * @return NoRouteAction
     */
    private function noRouteActionFor(?string $configValue): NoRouteAction
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')
            ->with('web/default/no_route', 'default')
            ->willReturn($configValue);

        return new NoRouteAction($scopeConfig);
    }
}
