<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Filter\Field;

use Hryvinskyi\InvisibleCaptcha\Api\AjaxRequestDetectorInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Field\IsAjax;
use Magento\Framework\App\Request\Http as HttpRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class IsAjaxTest extends TestCase
{
    /** @var AjaxRequestDetectorInterface&MockObject */
    private AjaxRequestDetectorInterface $detector;
    /** @var HttpRequest&MockObject */
    private HttpRequest $request;
    private IsAjax $field;

    protected function setUp(): void
    {
        $this->detector = $this->createMock(AjaxRequestDetectorInterface::class);
        $this->request = $this->createMock(HttpRequest::class);
        $this->field = new IsAjax($this->detector, $this->request);
    }

    public function testMetadata(): void
    {
        $this->assertSame('is_ajax', $this->field->getCode());
        $this->assertSame('Is AJAX Request', (string)$this->field->getLabel());
        $this->assertSame(FieldInterface::TYPE_BOOLEAN, $this->field->getType());
    }

    public function testValueIsOneForAjaxRequests(): void
    {
        $this->detector->expects($this->once())
            ->method('isAjax')
            ->with($this->request)
            ->willReturn(true);

        $this->assertSame(1, $this->field->getValue());
    }

    public function testValueIsZeroForNavigationRequests(): void
    {
        $this->detector->method('isAjax')->willReturn(false);

        $this->assertSame(0, $this->field->getValue());
    }
}
