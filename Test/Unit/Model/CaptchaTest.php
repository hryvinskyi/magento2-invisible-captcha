<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model;

use Hryvinskyi\InvisibleCaptcha\Api\EnablementInterface;
use Hryvinskyi\InvisibleCaptcha\Api\ScoreThresholdInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Strategy\FailureStrategyInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Strategy\TokenStrategyInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Captcha;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CaptchaTest extends TestCase
{
    /** @var TokenStrategyInterface&MockObject */
    private TokenStrategyInterface $tokenStrategy;
    /** @var FailureStrategyInterface&MockObject */
    private FailureStrategyInterface $failureProvider;
    /** @var EnablementInterface&MockObject */
    private EnablementInterface $enablement;
    /** @var ScoreThresholdInterface&MockObject */
    private ScoreThresholdInterface $scoreThreshold;

    protected function setUp(): void
    {
        $this->tokenStrategy = $this->createMock(TokenStrategyInterface::class);
        $this->failureProvider = $this->createMock(FailureStrategyInterface::class);
        $this->enablement = $this->createMock(EnablementInterface::class);
        $this->scoreThreshold = $this->createMock(ScoreThresholdInterface::class);
    }

    public function testGetActionReturnsConfiguredAction(): void
    {
        $captcha = new Captcha(
            $this->tokenStrategy,
            $this->failureProvider,
            $this->enablement,
            $this->scoreThreshold,
            'customer_login'
        );

        $this->assertSame('customer_login', $captcha->getAction());
    }

    public function testGetActionReturnsNullByDefault(): void
    {
        $captcha = new Captcha($this->tokenStrategy, $this->failureProvider, $this->enablement);

        $this->assertNull($captcha->getAction());
    }

    public function testGetTokenDelegatesToTokenStrategy(): void
    {
        $this->tokenStrategy->expects($this->once())
            ->method('getToken')
            ->willReturn('token-123');

        $captcha = new Captcha($this->tokenStrategy, $this->failureProvider, $this->enablement);

        $this->assertSame('token-123', $captcha->getToken());
    }

    public function testGetTokenReturnsNullWhenStrategyReturnsNull(): void
    {
        $this->tokenStrategy->method('getToken')->willReturn(null);

        $captcha = new Captcha($this->tokenStrategy, $this->failureProvider, $this->enablement);

        $this->assertNull($captcha->getToken());
    }

    public function testGetScoreThresholdDelegatesToResolver(): void
    {
        $this->scoreThreshold->expects($this->once())
            ->method('getValue')
            ->willReturn(0.5);

        $captcha = new Captcha(
            $this->tokenStrategy,
            $this->failureProvider,
            $this->enablement,
            $this->scoreThreshold
        );

        $this->assertSame(0.5, $captcha->getScoreThreshold());
    }

    public function testGetScoreThresholdNullWhenNoResolver(): void
    {
        $captcha = new Captcha(
            $this->tokenStrategy,
            $this->failureProvider,
            $this->enablement,
            null
        );

        $this->assertNull($captcha->getScoreThreshold());
    }

    public function testGetFailureReturnsFailureStrategy(): void
    {
        $captcha = new Captcha($this->tokenStrategy, $this->failureProvider, $this->enablement);

        $this->assertSame($this->failureProvider, $captcha->getFailure());
    }

    public function testIsEnabledDelegatesToEnablement(): void
    {
        $this->enablement->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $captcha = new Captcha($this->tokenStrategy, $this->failureProvider, $this->enablement);

        $this->assertTrue($captcha->isEnabled());
    }

    public function testIsEnabledFalseWhenEnablementDisabled(): void
    {
        $this->enablement->method('isEnabled')->willReturn(false);

        $captcha = new Captcha($this->tokenStrategy, $this->failureProvider, $this->enablement);

        $this->assertFalse($captcha->isEnabled());
    }
}
