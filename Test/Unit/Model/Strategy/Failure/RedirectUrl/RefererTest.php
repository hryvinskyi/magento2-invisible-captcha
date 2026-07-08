<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Strategy\Failure\RedirectUrl;

use Hryvinskyi\InvisibleCaptcha\Model\Strategy\Failure\RedirectUrl\Referer;
use Magento\Framework\App\Response\RedirectInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RefererTest extends TestCase
{
    /** @var RedirectInterface&MockObject */
    private RedirectInterface $redirect;
    private Referer $provider;

    protected function setUp(): void
    {
        $this->redirect = $this->createMock(RedirectInterface::class);
        $this->provider = new Referer($this->redirect);
    }

    public function testReturnsRefererRedirectUrl(): void
    {
        $this->redirect->expects($this->once())
            ->method('getRedirectUrl')
            ->willReturn('https://example.com/previous-page');

        $this->assertSame('https://example.com/previous-page', $this->provider->getRedirectUrl());
    }
}
