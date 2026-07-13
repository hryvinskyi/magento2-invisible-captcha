<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model;

use Hryvinskyi\InvisibleCaptcha\Model\AjaxRequestDetector;
use Magento\Framework\App\Request\Http as HttpRequest;
use PHPUnit\Framework\TestCase;

class AjaxRequestDetectorTest extends TestCase
{
    private AjaxRequestDetector $detector;

    protected function setUp(): void
    {
        $this->detector = new AjaxRequestDetector();
    }

    public function testXmlHttpRequestIsAjax(): void
    {
        $request = $this->createMock(HttpRequest::class);
        $request->method('isXmlHttpRequest')->willReturn(true);

        $this->assertTrue($this->detector->isAjax($request));
    }

    public function testAjaxMarkerParamIsAjax(): void
    {
        $request = $this->createMock(HttpRequest::class);
        $request->method('isXmlHttpRequest')->willReturn(false);
        $request->method('isAjax')->willReturn(true);

        $this->assertTrue($this->detector->isAjax($request));
    }

    public function testJsonAcceptHeaderIsAjax(): void
    {
        $request = $this->createMock(HttpRequest::class);
        $request->method('isXmlHttpRequest')->willReturn(false);
        $request->method('isAjax')->willReturn(false);
        $request->method('getHeader')->willReturnMap([
            ['Accept', false, 'application/json, text/javascript, */*; q=0.01'],
            ['X-Requested-With', false, false],
        ]);

        $this->assertTrue($this->detector->isAjax($request));
    }

    public function testRequestedWithHeaderIsAjaxCaseInsensitively(): void
    {
        $request = $this->createMock(HttpRequest::class);
        $request->method('isXmlHttpRequest')->willReturn(false);
        $request->method('isAjax')->willReturn(false);
        $request->method('getHeader')->willReturnMap([
            ['Accept', false, 'text/html'],
            ['X-Requested-With', false, 'xmlhttprequest'],
        ]);

        $this->assertTrue($this->detector->isAjax($request));
    }

    public function testPlainNavigationRequestIsNotAjax(): void
    {
        $request = $this->createMock(HttpRequest::class);
        $request->method('isXmlHttpRequest')->willReturn(false);
        $request->method('isAjax')->willReturn(false);
        $request->method('getHeader')->willReturnMap([
            ['Accept', false, 'text/html,application/xhtml+xml'],
            ['X-Requested-With', false, false],
        ]);

        $this->assertFalse($this->detector->isAjax($request));
    }
}
