<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Observer;

use Hryvinskyi\InvisibleCaptcha\Api\CaptchaInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderPoolInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Strategy\FailureStrategyInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Verification\VerificationRequestInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Verification\VerificationResultInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Verification\VerifierInterface;
use Hryvinskyi\InvisibleCaptcha\Observer\FormVerify;
use Magento\Framework\App\Action\AbstractAction;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FormVerifyTest extends TestCase
{
    /** @var ProviderPoolInterface&MockObject */
    private ProviderPoolInterface $providerPool;
    /** @var RemoteAddress&MockObject */
    private RemoteAddress $remoteAddress;
    /** @var HttpRequest&MockObject */
    private HttpRequest $request;
    /** @var CaptchaInterface&MockObject */
    private CaptchaInterface $provider;
    private FormVerify $observer;

    protected function setUp(): void
    {
        $this->providerPool = $this->createMock(ProviderPoolInterface::class);
        $this->remoteAddress = $this->createMock(RemoteAddress::class);
        $this->request = $this->createMock(HttpRequest::class);
        $this->provider = $this->createMock(CaptchaInterface::class);

        $this->observer = new FormVerify(
            $this->providerPool,
            $this->remoteAddress,
            $this->request,
            $this->provider
        );
    }

    public function testNoOpWhenFormDisabled(): void
    {
        $this->provider->method('isEnabled')->willReturn(false);

        // The active provider must never be resolved when the form is off.
        $this->providerPool->expects($this->never())->method('getActive');
        $this->request->expects($this->never())->method('getMethod');

        $this->observer->execute($this->createMock(Observer::class));
    }

    #[DataProvider('getMethodProvider')]
    public function testNoOpOnGetRequest(string $method): void
    {
        $this->provider->method('isEnabled')->willReturn(true);
        $this->request->method('getMethod')->willReturn($method);

        $this->providerPool->expects($this->never())->method('getActive');

        $this->observer->execute($this->createMock(Observer::class));
    }

    public static function getMethodProvider(): array
    {
        return [
            'upper case GET' => ['GET'],
            'lower case get' => ['get'],
            'mixed case Get' => ['Get'],
        ];
    }

    public function testSuccessfulVerificationDoesNotInvokeFailure(): void
    {
        $verRequest = $this->verificationRequest();
        $activeProvider = $this->activeProvider($verRequest, true);

        $this->provider->method('isEnabled')->willReturn(true);
        $this->request->method('getMethod')->willReturn('POST');
        $this->provider->method('getToken')->willReturn('token-abc');
        $this->remoteAddress->method('getRemoteAddress')->willReturn('203.0.113.10');
        $this->providerPool->method('getActive')->willReturn($activeProvider);

        // v2-style provider: no action, no score.
        $activeProvider->method('supportsAction')->willReturn(false);
        $activeProvider->method('isScoreBased')->willReturn(false);

        $verRequest->expects($this->once())->method('setResponse')->with('token-abc')->willReturnSelf();
        $verRequest->expects($this->once())->method('setRemoteIp')->with('203.0.113.10')->willReturnSelf();
        $verRequest->expects($this->never())->method('setExpectedAction');
        $verRequest->expects($this->never())->method('setScoreThreshold');

        // Failure strategy must never be touched on success.
        $this->provider->expects($this->never())->method('getFailure');

        $this->observer->execute($this->createMock(Observer::class));
    }

    public function testFailureInvokesFailureStrategyWithControllerResponse(): void
    {
        $verRequest = $this->verificationRequest();
        $result = $this->createMock(VerificationResultInterface::class);
        $result->method('isSuccess')->willReturn(false);
        $activeProvider = $this->activeProvider($verRequest, false, $result);

        $this->provider->method('isEnabled')->willReturn(true);
        $this->request->method('getMethod')->willReturn('POST');
        $this->provider->method('getToken')->willReturn('token-abc');
        $this->remoteAddress->method('getRemoteAddress')->willReturn(false);
        $this->providerPool->method('getActive')->willReturn($activeProvider);
        $activeProvider->method('supportsAction')->willReturn(false);
        $activeProvider->method('isScoreBased')->willReturn(false);

        // Remote IP resolves to null when RemoteAddress returns false.
        $verRequest->method('setResponse')->willReturnSelf();
        $verRequest->expects($this->once())->method('setRemoteIp')->with(null)->willReturnSelf();

        $response = $this->createMock(ResponseInterface::class);
        $controller = $this->createMock(AbstractAction::class);
        $controller->method('getResponse')->willReturn($response);

        $observerEvent = $this->createMock(Observer::class);
        $observerEvent->method('getData')->with('controller_action')->willReturn($controller);

        $failure = $this->createMock(FailureStrategyInterface::class);
        $failure->expects($this->once())->method('execute')->with($result, $response);
        $this->provider->method('getFailure')->willReturn($failure);

        $this->observer->execute($observerEvent);
    }

    public function testFailurePassesNullResponseWhenNoController(): void
    {
        $verRequest = $this->verificationRequest();
        $result = $this->createMock(VerificationResultInterface::class);
        $result->method('isSuccess')->willReturn(false);
        $activeProvider = $this->activeProvider($verRequest, false, $result);

        $this->provider->method('isEnabled')->willReturn(true);
        $this->request->method('getMethod')->willReturn('POST');
        $this->provider->method('getToken')->willReturn(null);
        $this->remoteAddress->method('getRemoteAddress')->willReturn('');
        $this->providerPool->method('getActive')->willReturn($activeProvider);
        $activeProvider->method('supportsAction')->willReturn(false);
        $activeProvider->method('isScoreBased')->willReturn(false);

        // Null token casts to empty string.
        $verRequest->expects($this->once())->method('setResponse')->with('')->willReturnSelf();
        $verRequest->method('setRemoteIp')->willReturnSelf();

        $observerEvent = $this->createMock(Observer::class);
        $observerEvent->method('getData')->with('controller_action')->willReturn(null);

        $failure = $this->createMock(FailureStrategyInterface::class);
        $failure->expects($this->once())->method('execute')->with($result, null);
        $this->provider->method('getFailure')->willReturn($failure);

        $this->observer->execute($observerEvent);
    }

    public function testActionAndScoreThresholdAppliedForScoreBasedProvider(): void
    {
        $verRequest = $this->verificationRequest();
        $activeProvider = $this->activeProvider($verRequest, true);

        $this->provider->method('isEnabled')->willReturn(true);
        $this->request->method('getMethod')->willReturn('POST');
        $this->provider->method('getToken')->willReturn('token-abc');
        $this->provider->method('getAction')->willReturn('login');
        $this->provider->method('getScoreThreshold')->willReturn(0.7);
        $this->remoteAddress->method('getRemoteAddress')->willReturn('198.51.100.7');
        $this->providerPool->method('getActive')->willReturn($activeProvider);

        $activeProvider->method('supportsAction')->willReturn(true);
        $activeProvider->method('isScoreBased')->willReturn(true);

        $verRequest->method('setResponse')->willReturnSelf();
        $verRequest->method('setRemoteIp')->willReturnSelf();
        $verRequest->expects($this->once())->method('setExpectedAction')->with('login')->willReturnSelf();
        $verRequest->expects($this->once())->method('setScoreThreshold')->with(0.7)->willReturnSelf();

        $this->observer->execute($this->createMock(Observer::class));
    }

    public function testActionAndScoreSkippedWhenDescriptorReturnsNull(): void
    {
        $verRequest = $this->verificationRequest();
        $activeProvider = $this->activeProvider($verRequest, true);

        $this->provider->method('isEnabled')->willReturn(true);
        $this->request->method('getMethod')->willReturn('POST');
        $this->provider->method('getToken')->willReturn('token-abc');
        // Provider supports the features but the per-form descriptor opts out.
        $this->provider->method('getAction')->willReturn(null);
        $this->provider->method('getScoreThreshold')->willReturn(null);
        $this->remoteAddress->method('getRemoteAddress')->willReturn('198.51.100.7');
        $this->providerPool->method('getActive')->willReturn($activeProvider);

        $activeProvider->method('supportsAction')->willReturn(true);
        $activeProvider->method('isScoreBased')->willReturn(true);

        $verRequest->method('setResponse')->willReturnSelf();
        $verRequest->method('setRemoteIp')->willReturnSelf();
        $verRequest->expects($this->never())->method('setExpectedAction');
        $verRequest->expects($this->never())->method('setScoreThreshold');

        $this->observer->execute($this->createMock(Observer::class));
    }

    /**
     * @return VerificationRequestInterface&MockObject
     */
    private function verificationRequest(): VerificationRequestInterface
    {
        return $this->createMock(VerificationRequestInterface::class);
    }

    /**
     * Build an active provider whose verifier returns a result with the given outcome.
     *
     * @return ProviderInterface&MockObject
     */
    private function activeProvider(
        VerificationRequestInterface $verRequest,
        bool $success,
        ?VerificationResultInterface $result = null
    ): ProviderInterface {
        if ($result === null) {
            $result = $this->createMock(VerificationResultInterface::class);
            $result->method('isSuccess')->willReturn($success);
        }

        $verifier = $this->createMock(VerifierInterface::class);
        $verifier->method('verify')->with($verRequest)->willReturn($result);

        $provider = $this->createMock(ProviderInterface::class);
        $provider->method('createVerificationRequest')->willReturn($verRequest);
        $provider->method('getVerifier')->willReturn($verifier);

        return $provider;
    }
}
