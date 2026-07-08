<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Controller\Router;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderPoolInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Verification\VerificationRequestInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Verification\VerificationResultInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Verification\VerifierInterface;
use Hryvinskyi\InvisibleCaptcha\Controller\Router\Verify;
use Hryvinskyi\InvisibleCaptcha\Model\CookieManager;
use Hryvinskyi\InvisibleCaptcha\Model\Http\CaptchaContext;
use Hryvinskyi\InvisibleCaptcha\Model\RefIdGenerator;
use Hryvinskyi\InvisibleCaptcha\Model\RequestChecker;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class VerifyTest extends TestCase
{
    /** @var ConfigInterface&MockObject */
    private ConfigInterface $config;
    /** @var ProviderPoolInterface&MockObject */
    private ProviderPoolInterface $providerPool;
    /** @var CookieManager&MockObject */
    private CookieManager $cookieManager;
    /** @var RequestChecker&MockObject */
    private RequestChecker $requestChecker;
    /** @var CaptchaContext&MockObject */
    private CaptchaContext $captchaContext;
    /** @var RefIdGenerator&MockObject */
    private RefIdGenerator $refIdGenerator;
    /** @var RequestInterface&MockObject */
    private RequestInterface $request;
    /** @var JsonFactory&MockObject */
    private JsonFactory $jsonFactory;
    /** @var Json&MockObject */
    private Json $json;
    /** @var LoggerInterface&MockObject */
    private LoggerInterface $logger;
    private Verify $action;

    protected function setUp(): void
    {
        $this->config = $this->createMock(ConfigInterface::class);
        $this->providerPool = $this->createMock(ProviderPoolInterface::class);
        $this->cookieManager = $this->createMock(CookieManager::class);
        $this->requestChecker = $this->createMock(RequestChecker::class);
        $this->captchaContext = $this->createMock(CaptchaContext::class);
        $this->refIdGenerator = $this->createMock(RefIdGenerator::class);
        $this->request = $this->createMock(RequestInterface::class);
        $this->jsonFactory = $this->createMock(JsonFactory::class);
        $this->json = $this->createMock(Json::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        // setData / setHttpResponseCode are configured per-test (with expectations);
        // setHeader is fire-and-forget cache headers, so a self-returning stub suffices.
        $this->json->method('setHeader')->willReturnSelf();
        $this->jsonFactory->method('create')->willReturn($this->json);

        // Debug logging off by default so log collaborators stay quiet.
        $this->config->method('isDebug')->willReturn(false);

        $this->action = new Verify(
            $this->config,
            $this->providerPool,
            $this->cookieManager,
            $this->requestChecker,
            $this->captchaContext,
            $this->refIdGenerator,
            $this->request,
            $this->jsonFactory,
            $this->logger
        );
    }

    public function testReturns503WhenNotConfigured(): void
    {
        $this->requestChecker->method('isConfigured')->willReturn(false);
        $this->requestChecker->method('getClientIp')->willReturn('');
        $this->stubParams([]);

        // The provider chain is never touched when keys are missing.
        $this->providerPool->expects($this->never())->method('getRouteGateProvider');

        $this->json->expects($this->once())->method('setHttpResponseCode')->with(503)->willReturnSelf();
        $this->json->expects($this->once())->method('setData')
            ->with($this->callback(static fn(array $d): bool => $d['success'] === false))
            ->willReturnSelf();

        $this->assertSame($this->json, $this->action->execute());
    }

    public function testSuccessIssuesCookieAndMarksContext(): void
    {
        $this->requestChecker->method('isConfigured')->willReturn(true);
        $this->requestChecker->method('getClientIp')->willReturn('203.0.113.9');
        $this->refIdGenerator->method('isValid')->willReturn(true);

        $primary = $this->verifyingProvider('recaptcha_v3', 'g-recaptcha-response', true);
        $this->providerPool->method('getRouteGateProvider')->willReturn($primary);
        $this->providerPool->method('getFallbackProvider')->willReturn(null);

        $this->stubParams([
            'ref' => 'A7F23K9M',
            'g-recaptcha-response' => 'valid-token',
        ]);

        $this->cookieManager->expects($this->once())->method('setVerified');
        $this->captchaContext->expects($this->once())->method('markVerified');

        $this->json->expects($this->once())->method('setData')
            ->with(['success' => true])->willReturnSelf();
        $this->json->expects($this->never())->method('setHttpResponseCode');

        $this->assertSame($this->json, $this->action->execute());
    }

    public function testFailsWhenNoTokenSubmitted(): void
    {
        $this->requestChecker->method('isConfigured')->willReturn(true);
        $this->requestChecker->method('getClientIp')->willReturn('');

        $primary = $this->createMock(ProviderInterface::class);
        $primary->method('getCode')->willReturn('recaptcha_v3');
        $primary->method('getResponseParamName')->willReturn('g-recaptcha-response');
        // No verification should be attempted without a token.
        $primary->expects($this->never())->method('getVerifier');
        $this->providerPool->method('getRouteGateProvider')->willReturn($primary);
        $this->providerPool->method('getFallbackProvider')->willReturn(null);

        $this->stubParams(['ref' => '', 'g-recaptcha-response' => '']);

        $this->cookieManager->expects($this->never())->method('setVerified');
        $this->captchaContext->expects($this->never())->method('markVerified');

        $this->json->expects($this->once())->method('setData')
            ->with($this->callback(static fn(array $d): bool => $d['success'] === false))
            ->willReturnSelf();

        $this->assertSame($this->json, $this->action->execute());
    }

    public function testFailsWhenTokenInvalid(): void
    {
        $this->requestChecker->method('isConfigured')->willReturn(true);
        $this->requestChecker->method('getClientIp')->willReturn('203.0.113.9');

        $primary = $this->verifyingProvider('turnstile', 'cf-turnstile-response', false);
        $this->providerPool->method('getRouteGateProvider')->willReturn($primary);
        // A token was submitted (providerCode is non-empty) so the fallback is never reached.
        $this->providerPool->method('getFallbackProvider')->willReturn(null);

        $this->stubParams(['ref' => '', 'cf-turnstile-response' => 'bad-token']);

        $this->cookieManager->expects($this->never())->method('setVerified');
        $this->captchaContext->expects($this->never())->method('markVerified');

        $this->json->expects($this->once())->method('setData')
            ->with($this->callback(static fn(array $d): bool => $d['success'] === false))
            ->willReturnSelf();

        $this->assertSame($this->json, $this->action->execute());
    }

    public function testFallbackProviderRetriedWhenPrimaryHasNoToken(): void
    {
        $this->requestChecker->method('isConfigured')->willReturn(true);
        $this->requestChecker->method('getClientIp')->willReturn('203.0.113.9');

        $primary = $this->createMock(ProviderInterface::class);
        $primary->method('getCode')->willReturn('recaptcha_v3');
        $primary->method('getResponseParamName')->willReturn('g-recaptcha-response');
        $primary->expects($this->never())->method('getVerifier');

        $fallback = $this->verifyingProvider('turnstile', 'cf-turnstile-response', true);

        $this->providerPool->method('getRouteGateProvider')->willReturn($primary);
        $this->providerPool->method('getFallbackProvider')->willReturn($fallback);

        $this->stubParams([
            'ref' => '',
            'g-recaptcha-response' => '',
            'cf-turnstile-response' => 'fallback-token',
        ]);

        $this->cookieManager->expects($this->once())->method('setVerified');
        $this->captchaContext->expects($this->once())->method('markVerified');

        $this->json->expects($this->once())->method('setData')
            ->with(['success' => true])->willReturnSelf();

        $this->assertSame($this->json, $this->action->execute());
    }

    /**
     * Build a provider whose verifier reports the given outcome for any token.
     *
     * @return ProviderInterface&MockObject
     */
    private function verifyingProvider(string $code, string $paramName, bool $success): ProviderInterface
    {
        $verRequest = $this->createMock(VerificationRequestInterface::class);
        $verRequest->method('setResponse')->willReturnSelf();
        $verRequest->method('setRemoteIp')->willReturnSelf();

        $result = $this->createMock(VerificationResultInterface::class);
        $result->method('isSuccess')->willReturn($success);

        $verifier = $this->createMock(VerifierInterface::class);
        $verifier->method('verify')->with($verRequest)->willReturn($result);

        $provider = $this->createMock(ProviderInterface::class);
        $provider->method('getCode')->willReturn($code);
        $provider->method('getResponseParamName')->willReturn($paramName);
        $provider->method('createVerificationRequest')->willReturn($verRequest);
        $provider->method('getVerifier')->willReturn($verifier);

        return $provider;
    }

    /**
     * Stub request->getParam() against a key => value map, honouring the default.
     *
     * @param array<string, string> $params
     */
    private function stubParams(array $params): void
    {
        $this->request->method('getParam')->willReturnCallback(
            static fn(string $key, $default = null) => $params[$key] ?? $default
        );
    }
}
