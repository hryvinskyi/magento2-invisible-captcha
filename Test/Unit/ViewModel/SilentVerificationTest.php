<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\ViewModel;

use Hryvinskyi\InvisibleCaptcha\Api\SilentVerificationInterface;
use Hryvinskyi\InvisibleCaptcha\ViewModel\SilentVerification;
use Magento\Framework\Serialize\Serializer\JsonHexTag;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SilentVerificationTest extends TestCase
{
    /** @var SilentVerificationInterface&MockObject */
    private SilentVerificationInterface $silentVerification;
    private SilentVerification $viewModel;

    protected function setUp(): void
    {
        $this->silentVerification = $this->createMock(SilentVerificationInterface::class);
        $this->viewModel = new SilentVerification($this->silentVerification, new JsonHexTag());
    }

    public function testActiveStateComesFromTheModel(): void
    {
        $this->silentVerification->method('isActive')->willReturn(true);

        $this->assertTrue($this->viewModel->isActive());
    }

    public function testConfigJsonCannotCloseItsScriptBlock(): void
    {
        $this->silentVerification->method('getClientConfig')
            ->willReturn(['siteKey' => '</script><script>alert(1)</script>', 'mode' => 'widget']);

        $json = $this->viewModel->getConfigJson();

        $this->assertStringNotContainsString('<', $json);
        $this->assertStringNotContainsString('>', $json);
        $this->assertSame(
            ['siteKey' => '</script><script>alert(1)</script>', 'mode' => 'widget'],
            json_decode($json, true)
        );
    }
}
