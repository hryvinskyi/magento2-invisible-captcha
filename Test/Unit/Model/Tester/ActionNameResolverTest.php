<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Tester;

use Hryvinskyi\InvisibleCaptcha\Model\Tester\ActionNameResolver;
use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\GetPageByIdentifierInterface;
use Magento\Framework\App\Route\ConfigInterface as RouteConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ActionNameResolverTest extends TestCase
{
    /** @var UrlFinderInterface&MockObject */
    private UrlFinderInterface $urlFinder;
    /** @var RouteConfigInterface&MockObject */
    private RouteConfigInterface $routeConfig;
    /** @var GetPageByIdentifierInterface&MockObject */
    private GetPageByIdentifierInterface $getPageByIdentifier;
    private ActionNameResolver $resolver;

    protected function setUp(): void
    {
        $this->urlFinder = $this->createMock(UrlFinderInterface::class);
        $this->routeConfig = $this->createMock(RouteConfigInterface::class);
        $this->getPageByIdentifier = $this->createMock(GetPageByIdentifierInterface::class);
        // No CMS page by default; individual tests override.
        $this->getPageByIdentifier->method('execute')
            ->willThrowException(new NoSuchEntityException(new Phrase('no page')));
        $this->resolver = new ActionNameResolver(
            $this->urlFinder,
            $this->routeConfig,
            $this->getPageByIdentifier
        );
    }

    public function testRootPathResolvesToCmsHome(): void
    {
        $this->urlFinder->expects($this->never())->method('findOneByData');

        $parts = $this->resolver->resolve('/', 1);

        $this->assertSame(
            ['route' => 'cms', 'controller' => 'index', 'action' => 'index', 'params' => [], 'source' => 'home'],
            $parts
        );
    }

    public function testRewriteTargetWins(): void
    {
        $rewrite = $this->createMock(UrlRewrite::class);
        $rewrite->method('getRedirectType')->willReturn(0);
        $rewrite->method('getTargetPath')->willReturn('catalog/product/view/id/5');

        $this->urlFinder->method('findOneByData')
            ->with([UrlRewrite::REQUEST_PATH => 'some-product.html', UrlRewrite::STORE_ID => 2])
            ->willReturn($rewrite);
        $this->routeConfig->expects($this->never())->method('getRouteByFrontName');

        $parts = $this->resolver->resolve('/some-product.html', 2);

        $this->assertSame('catalog', $parts['route']);
        $this->assertSame('product', $parts['controller']);
        $this->assertSame('view', $parts['action']);
        $this->assertSame(['id' => '5'], $parts['params']);
        $this->assertSame('rewrite', $parts['source']);
    }

    public function testRedirectRewriteIsFollowedOneHop(): void
    {
        $redirect = $this->createMock(UrlRewrite::class);
        $redirect->method('getRedirectType')->willReturn(301);
        $redirect->method('getTargetPath')->willReturn('new-url.html');

        $target = $this->createMock(UrlRewrite::class);
        $target->method('getRedirectType')->willReturn(0);
        $target->method('getTargetPath')->willReturn('cms/page/view/page_id/3');

        $this->urlFinder->method('findOneByData')->willReturnCallback(
            static fn (array $data): ?UrlRewrite => match ($data[UrlRewrite::REQUEST_PATH]) {
                'old-url.html' => $redirect,
                'new-url.html' => $target,
                default => null,
            }
        );

        $parts = $this->resolver->resolve('old-url.html', 1);

        $this->assertNotNull($parts);
        $this->assertSame('cms', $parts['route']);
        $this->assertSame('page', $parts['controller']);
        $this->assertSame('view', $parts['action']);
        $this->assertSame(['page_id' => '3'], $parts['params']);
    }

    public function testUnresolvableRedirectFallsBackToFrontNameAndFails(): void
    {
        $redirect = $this->createMock(UrlRewrite::class);
        $redirect->method('getRedirectType')->willReturn(301);
        $redirect->method('getTargetPath')->willReturn('gone.html');

        $this->urlFinder->method('findOneByData')->willReturnCallback(
            static fn (array $data): ?UrlRewrite => $data[UrlRewrite::REQUEST_PATH] === 'old.html' ? $redirect : null
        );
        $this->routeConfig->method('getRouteByFrontName')->willReturn(false);

        $this->assertNull($this->resolver->resolve('old.html', 1));
    }

    public function testFrontNameMapsToRouteId(): void
    {
        $this->urlFinder->method('findOneByData')->willReturn(null);
        $this->routeConfig->method('getRouteByFrontName')
            ->with('checkout', 'frontend')
            ->willReturn('checkout');

        $parts = $this->resolver->resolve('/checkout/cart/add/product/12', 1);

        $this->assertSame('checkout', $parts['route']);
        $this->assertSame('cart', $parts['controller']);
        $this->assertSame('add', $parts['action']);
        $this->assertSame(['product' => '12'], $parts['params']);
        $this->assertSame('route', $parts['source']);
    }

    public function testFrontNameDifferentFromRouteId(): void
    {
        $this->urlFinder->method('findOneByData')->willReturn(null);
        $this->routeConfig->method('getRouteByFrontName')
            ->with('blog', 'frontend')
            ->willReturn('vendor_blog');

        $parts = $this->resolver->resolve('blog', 1);

        $this->assertSame('vendor_blog', $parts['route']);
        $this->assertSame('index', $parts['controller']);
        $this->assertSame('index', $parts['action']);
    }

    public function testUnknownFrontNameReturnsNull(): void
    {
        $this->urlFinder->method('findOneByData')->willReturn(null);
        $this->routeConfig->method('getRouteByFrontName')->willReturn(false);

        $this->assertNull($this->resolver->resolve('/no-such-page', 1));
    }

    public function testCmsPageIdentifierResolvesThroughTheCmsRouter(): void
    {
        $this->urlFinder->method('findOneByData')->willReturn(null);
        $this->routeConfig->method('getRouteByFrontName')->willReturn(false);

        $page = $this->createMock(PageInterface::class);
        $page->method('getId')->willReturn(7);
        $getPageByIdentifier = $this->createMock(GetPageByIdentifierInterface::class);
        $getPageByIdentifier->method('execute')->with('about-us', 2)->willReturn($page);

        $resolver = new ActionNameResolver($this->urlFinder, $this->routeConfig, $getPageByIdentifier);
        $parts = $resolver->resolve('/about-us', 2);

        $this->assertNotNull($parts);
        $this->assertSame('cms', $parts['route']);
        $this->assertSame('page', $parts['controller']);
        $this->assertSame('view', $parts['action']);
        $this->assertSame(['page_id' => '7'], $parts['params']);
        $this->assertSame('cms_page', $parts['source']);
    }

    public function testOddParamTailGetsEmptyValue(): void
    {
        $this->urlFinder->method('findOneByData')->willReturn(null);
        $this->routeConfig->method('getRouteByFrontName')->willReturn('catalog');

        $parts = $this->resolver->resolve('catalog/category/view/id', 1);

        $this->assertSame(['id' => ''], $parts['params']);
    }
}
