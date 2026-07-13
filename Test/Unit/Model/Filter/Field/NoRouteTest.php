<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Filter\Field;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Api\NoRouteActionInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Field\NoRoute;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\RequestInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NoRouteTest extends TestCase
{
    /** @var NoRouteActionInterface&MockObject */
    private NoRouteActionInterface $noRouteAction;
    /** @var HttpRequest&MockObject */
    private HttpRequest $request;
    private NoRoute $field;

    protected function setUp(): void
    {
        $this->noRouteAction = $this->createMock(NoRouteActionInterface::class);
        $this->noRouteAction->method('getFullActionName')->willReturn('cms_noroute_index');
        $this->request = $this->createMock(HttpRequest::class);
        $this->field = new NoRoute($this->noRouteAction, $this->request);
    }

    public function testMetadata(): void
    {
        $this->assertSame('is_404', $this->field->getCode());
        $this->assertSame('Is 404 (No-Route) Page', (string)$this->field->getLabel());
        $this->assertSame(FieldInterface::TYPE_BOOLEAN, $this->field->getType());
    }

    public function testValueIsOneOnTheNoRouteAction(): void
    {
        $this->request->method('getFullActionName')->willReturn('cms_noroute_index');

        $this->assertSame(1, $this->field->getValue());
    }

    public function testComparisonIsCaseInsensitive(): void
    {
        $this->request->method('getFullActionName')->willReturn('CMS_NoRoute_Index');

        $this->assertSame(1, $this->field->getValue());
    }

    public function testValueIsZeroOnRoutedActions(): void
    {
        $this->request->method('getFullActionName')->willReturn('catalog_product_view');

        $this->assertSame(0, $this->field->getValue());
    }

    public function testNonHttpRequestIsNeverNoRoute(): void
    {
        $field = new NoRoute($this->noRouteAction, $this->createMock(RequestInterface::class));

        $this->assertSame(0, $field->getValue());
    }
}
