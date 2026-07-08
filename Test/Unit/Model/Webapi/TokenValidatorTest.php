<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Webapi;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderPoolInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Verification\VerificationRequestInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Verification\VerificationResultInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Verification\VerifierInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Webapi\TokenValidator;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TokenValidatorTest extends TestCase
{
    private const FORM_KEY = 'place_order';

    /** @var ProviderPoolInterface&MockObject */
    private ProviderPoolInterface $providerPool;
    /** @var ConfigInterface&MockObject */
    private ConfigInterface $config;
    /** @var RemoteAddress&MockObject */
    private RemoteAddress $remoteAddress;
    private TokenValidator $validator;

    // Captured fluent-setter calls on the verification request.
    private bool $remoteIpSet = false;
    private ?string $capturedRemoteIp = null;
    private bool $actionSet = false;
    private ?string $capturedAction = null;
    private bool $thresholdSet = false;
    private ?float $capturedThreshold = null;

    protected function setUp(): void
    {
        $this->providerPool = $this->createMock(ProviderPoolInterface::class);
        $this->config = $this->createMock(ConfigInterface::class);
        $this->remoteAddress = $this->createMock(RemoteAddress::class);

        $this->validator = new TokenValidator(
            $this->providerPool,
            $this->config,
            $this->remoteAddress
        );
    }

    public function testEmptyTokenReturnsFalseWithoutTouchingProvider(): void
    {
        $this->providerPool->expects($this->never())->method('getActive');

        $this->assertFalse($this->validator->isValid(self::FORM_KEY, ''));
    }

    public function testSetsActionAndThresholdForScoreBasedProviderAndReturnsVerifierResult(): void
    {
        $this->remoteAddress->method('getRemoteAddress')->willReturn('203.0.113.7');
        $this->config->method('getFormScoreThreshold')->with(self::FORM_KEY)->willReturn(0.7);

        $request = $this->verificationRequest();
        $provider = $this->provider($request, true, true, $this->verifier(true));
        $this->providerPool->method('getActive')->willReturn($provider);

        $this->assertTrue($this->validator->isValid(self::FORM_KEY, 'tok-123'));

        // Score-based providers that support actions receive both the expected
        // action (the form key) and the configured score threshold.
        $this->assertTrue($this->actionSet);
        $this->assertSame(self::FORM_KEY, $this->capturedAction);
        $this->assertTrue($this->thresholdSet);
        $this->assertSame(0.7, $this->capturedThreshold);
        $this->assertSame('203.0.113.7', $this->capturedRemoteIp);
    }

    public function testSkipsActionAndThresholdForBinaryProviderAndPropagatesFailure(): void
    {
        $this->remoteAddress->method('getRemoteAddress')->willReturn('198.51.100.4');
        // Binary (pass/fail) providers never consult the score threshold config.
        $this->config->expects($this->never())->method('getFormScoreThreshold');

        $request = $this->verificationRequest();
        $provider = $this->provider($request, false, false, $this->verifier(false));
        $this->providerPool->method('getActive')->willReturn($provider);

        $this->assertFalse($this->validator->isValid(self::FORM_KEY, 'tok-123'));

        $this->assertFalse($this->actionSet);
        $this->assertFalse($this->thresholdSet);
    }

    public function testActionSetButThresholdSkippedWhenSupportsActionWithoutScore(): void
    {
        $this->remoteAddress->method('getRemoteAddress')->willReturn('198.51.100.4');
        $this->config->expects($this->never())->method('getFormScoreThreshold');

        $request = $this->verificationRequest();
        $provider = $this->provider($request, true, false, $this->verifier(true));
        $this->providerPool->method('getActive')->willReturn($provider);

        $this->assertTrue($this->validator->isValid(self::FORM_KEY, 'tok-123'));

        $this->assertTrue($this->actionSet);
        $this->assertSame(self::FORM_KEY, $this->capturedAction);
        $this->assertFalse($this->thresholdSet);
    }

    public function testFalseRemoteAddressIsNormalisedToNull(): void
    {
        // RemoteAddress::getRemoteAddress() returns false when unavailable.
        $this->remoteAddress->method('getRemoteAddress')->willReturn(false);

        $request = $this->verificationRequest();
        $provider = $this->provider($request, false, false, $this->verifier(true));
        $this->providerPool->method('getActive')->willReturn($provider);

        $this->assertTrue($this->validator->isValid(self::FORM_KEY, 'tok-123'));

        $this->assertTrue($this->remoteIpSet);
        $this->assertNull($this->capturedRemoteIp);
    }

    /**
     * Builds a fluent verification-request mock whose setters return the request
     * and record their arguments for assertion.
     *
     * @return VerificationRequestInterface&MockObject
     */
    private function verificationRequest(): VerificationRequestInterface
    {
        $request = $this->createMock(VerificationRequestInterface::class);
        $request->method('setResponse')->willReturnSelf();
        $request->method('setRemoteIp')->willReturnCallback(
            function (?string $ip) use ($request): VerificationRequestInterface {
                $this->remoteIpSet = true;
                $this->capturedRemoteIp = $ip;

                return $request;
            }
        );
        $request->method('setExpectedAction')->willReturnCallback(
            function (?string $action) use ($request): VerificationRequestInterface {
                $this->actionSet = true;
                $this->capturedAction = $action;

                return $request;
            }
        );
        $request->method('setScoreThreshold')->willReturnCallback(
            function (?float $threshold) use ($request): VerificationRequestInterface {
                $this->thresholdSet = true;
                $this->capturedThreshold = $threshold;

                return $request;
            }
        );

        return $request;
    }

    /**
     * @return VerifierInterface&MockObject
     */
    private function verifier(bool $success): VerifierInterface
    {
        $result = $this->createMock(VerificationResultInterface::class);
        $result->method('isSuccess')->willReturn($success);

        $verifier = $this->createMock(VerifierInterface::class);
        $verifier->method('verify')->willReturn($result);

        return $verifier;
    }

    /**
     * @param VerificationRequestInterface&MockObject $request
     * @param VerifierInterface&MockObject $verifier
     * @return ProviderInterface&MockObject
     */
    private function provider(
        VerificationRequestInterface $request,
        bool $supportsAction,
        bool $scoreBased,
        VerifierInterface $verifier
    ): ProviderInterface {
        $provider = $this->createMock(ProviderInterface::class);
        $provider->method('createVerificationRequest')->willReturn($request);
        $provider->method('supportsAction')->willReturn($supportsAction);
        $provider->method('isScoreBased')->willReturn($scoreBased);
        $provider->method('getVerifier')->willReturn($verifier);

        return $provider;
    }
}
