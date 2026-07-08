<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Strategy\Failure;

use Hryvinskyi\InvisibleCaptcha\Api\Verification\VerificationResultInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Strategy\Failure\AuthenticationException;
use Hryvinskyi\InvisibleCaptcha\Model\Strategy\Failure\FailureMessages;
use Magento\Framework\Exception\Plugin\AuthenticationException as PluginAuthenticationException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AuthenticationExceptionTest extends TestCase
{
    /** @var FailureMessages&MockObject */
    private FailureMessages $failureMessages;
    private AuthenticationException $strategy;

    protected function setUp(): void
    {
        $this->failureMessages = $this->createMock(FailureMessages::class);
        $this->strategy = new AuthenticationException($this->failureMessages);
    }

    public function testThrowsAuthenticationExceptionWithResolvedMessage(): void
    {
        $result = $this->createMock(VerificationResultInterface::class);
        $result->method('getErrorCodes')->willReturn(['invalid-input-response']);

        $this->failureMessages->method('hasErrorMessage')->willReturnMap([
            ['invalid-input-response', true],
        ]);
        $this->failureMessages->method('getErrorMessage')->willReturnMap([
            ['invalid-input-response', 'The captcha token is invalid.'],
        ]);

        $this->expectException(PluginAuthenticationException::class);
        $this->expectExceptionMessage('The captcha token is invalid.');

        $this->strategy->execute($result);
    }

    public function testThrowsWithUnknownErrorFallback(): void
    {
        $result = $this->createMock(VerificationResultInterface::class);
        $result->method('getErrorCodes')->willReturn([]);

        $this->failureMessages->method('hasErrorMessage')->willReturnMap([
            ['unknown-error', true],
        ]);
        $this->failureMessages->method('getErrorMessage')->willReturnMap([
            ['unknown-error', 'Captcha verification failed.'],
        ]);

        $this->expectException(PluginAuthenticationException::class);
        $this->expectExceptionMessage('Captcha verification failed.');

        $this->strategy->execute($result);
    }
}
