<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Tester;

use Hryvinskyi\InvisibleCaptcha\Model\Tester\SyntheticRequestFactory;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Request\HttpFactory as HttpRequestFactory;
use Magento\Framework\App\Request\PathInfo;
use Magento\Framework\App\Request\PathInfoProcessorInterface;
use Magento\Framework\App\Route\ConfigInterface as RouteConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieReaderInterface;
use Magento\Framework\Stdlib\StringUtils;
use PHPUnit\Framework\TestCase;

class SyntheticRequestFactoryTest extends TestCase
{
    private SyntheticRequestFactory $factory;
    private HttpRequest $request;

    protected function setUp(): void
    {
        $this->request = new HttpRequest(
            $this->createMock(CookieReaderInterface::class),
            new StringUtils(),
            $this->createMock(RouteConfigInterface::class),
            $this->createMock(PathInfoProcessorInterface::class),
            $this->createMock(ObjectManagerInterface::class),
            null,
            [],
            $this->createMock(PathInfo::class)
        );

        $requestFactory = $this->getMockBuilder(HttpRequestFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $requestFactory->method('create')->willReturn($this->request);

        $this->factory = new SyntheticRequestFactory($requestFactory);
    }

    public function testPopulatesTheCompleteFieldSurface(): void
    {
        $request = $this->factory->create(
            '/lamps',
            'price=10-20&color=blue',
            'shop.test',
            'post',
            'TestBot/1.0',
            '1.2.3.4',
            'https://ref.test/page',
            ['route' => 'catalog', 'controller' => 'category', 'action' => 'view', 'params' => ['id' => '7']]
        );

        $this->assertSame('/lamps?price=10-20&color=blue', $request->getRequestUri());
        $this->assertSame('/lamps', $request->getPathInfo());
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('TestBot/1.0', $request->getHeader('User-Agent'));
        $this->assertSame('https://ref.test/page', $request->getHeader('Referer'));
        $this->assertSame('shop.test', $request->getHeader('Host'));
        $this->assertSame('1.2.3.4', $request->getServer('REMOTE_ADDR'));
        $this->assertSame('price=10-20&color=blue', $request->getServer('QUERY_STRING'));
        $this->assertSame('shop.test', $request->getServer('HTTP_HOST'));
        $this->assertSame('catalog_category_view', $request->getFullActionName());

        $params = $request->getParams();
        $this->assertSame('10-20', $params['price']);
        $this->assertSame('blue', $params['color']);
        $this->assertSame('7', $params['id']);
    }

    public function testInvalidMethodFallsBackToGet(): void
    {
        $request = $this->factory->create('/x', '', '', 'BOGUS METHOD', '', '', '', null);

        $this->assertSame('GET', $request->getMethod());
    }

    public function testAbsentOptionalPartsLeaveNoTraces(): void
    {
        $request = $this->factory->create('/x', '', '', 'GET', '', '', '', null);

        $this->assertSame('/x', $request->getRequestUri());
        $this->assertFalse($request->getHeader('User-Agent'));
        $this->assertFalse($request->getHeader('Referer'));
        $this->assertNull($request->getServer('REMOTE_ADDR'));
        $this->assertSame([], $request->getParams());
    }
}
