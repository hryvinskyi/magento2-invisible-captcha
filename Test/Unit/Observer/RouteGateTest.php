<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Observer;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Model\ChallengeRenderer;
use Hryvinskyi\InvisibleCaptcha\Model\CookieManager;
use Hryvinskyi\InvisibleCaptcha\Model\RefIdGenerator;
use Hryvinskyi\InvisibleCaptcha\Model\RequestChecker;
use Hryvinskyi\InvisibleCaptcha\Observer\RouteGate;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class RouteGateTest extends TestCase
{
    private const CHALLENGE_HEADER = 'X-InvisibleCaptcha-Challenge';

    /** @var ConfigInterface&MockObject */
    private ConfigInterface $config;
    /** @var CookieManager&MockObject */
    private CookieManager $cookieManager;
    /** @var RequestChecker&MockObject */
    private RequestChecker $requestChecker;
    /** @var ChallengeRenderer&MockObject */
    private ChallengeRenderer $challengeRenderer;
    /** @var RefIdGenerator&MockObject */
    private RefIdGenerator $refIdGenerator;
    /** @var ActionFlag&MockObject */
    private ActionFlag $actionFlag;
    /** @var HttpResponse&MockObject */
    private HttpResponse $response;
    /** @var HttpRequest&MockObject */
    private HttpRequest $request;
    /** @var LoggerInterface&MockObject */
    private LoggerInterface $logger;
    private RouteGate $observer;

    protected function setUp(): void
    {
        $this->config = $this->createMock(ConfigInterface::class);
        $this->cookieManager = $this->createMock(CookieManager::class);
        $this->requestChecker = $this->createMock(RequestChecker::class);
        $this->challengeRenderer = $this->createMock(ChallengeRenderer::class);
        $this->refIdGenerator = $this->createMock(RefIdGenerator::class);
        $this->actionFlag = $this->createMock(ActionFlag::class);
        $this->response = $this->createMock(HttpResponse::class);
        $this->request = $this->createMock(HttpRequest::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->observer = new RouteGate(
            $this->config,
            $this->cookieManager,
            $this->requestChecker,
            $this->challengeRenderer,
            $this->refIdGenerator,
            $this->actionFlag,
            $this->response,
            $this->request,
            $this->logger
        );
    }

    public function testNoOpWhenChallengeNotNeeded(): void
    {
        $this->requestChecker->method('needsChallenge')->willReturn(false);

        // Short-circuits before even consulting the cookie state.
        $this->cookieManager->expects($this->never())->method('isVerified');
        $this->actionFlag->expects($this->never())->method('set');
        $this->response->expects($this->never())->method('setHttpResponseCode');

        $this->observer->execute($this->createMock(Observer::class));
    }

    public function testNoOpWhenAlreadyVerified(): void
    {
        $this->requestChecker->method('needsChallenge')->willReturn(true);
        $this->cookieManager->method('isVerified')->willReturn(true);

        $this->actionFlag->expects($this->never())->method('set');
        $this->challengeRenderer->expects($this->never())->method('render');
        $this->response->expects($this->never())->method('setBody');

        $this->observer->execute($this->createMock(Observer::class));
    }

    public function testEmitsInlineChallenge(): void
    {
        $this->requestChecker->method('needsChallenge')->willReturn(true);
        $this->cookieManager->method('isVerified')->willReturn(false);
        $this->config->method('isDebug')->willReturn(false);
        $this->refIdGenerator->method('generate')->willReturn('A7F23K9M');
        $this->challengeRenderer->expects($this->once())
            ->method('render')->with('A7F23K9M')->willReturn('<html>challenge</html>');

        // Nothing about the request marks it as AJAX.
        $this->request->method('isXmlHttpRequest')->willReturn(false);
        $this->request->method('isAjax')->willReturn(false);
        $this->request->method('getHeader')->willReturn('');

        $this->actionFlag->expects($this->once())
            ->method('set')->with('', ActionInterface::FLAG_NO_DISPATCH, true);

        $this->response->expects($this->once())->method('setHttpResponseCode')->with(403);
        $this->response->expects($this->once())->method('setBody')->with('<html>challenge</html>');

        $headers = $this->captureHeaders();

        $this->observer->execute($this->createMock(Observer::class));

        $this->assertSame('text/html; charset=UTF-8', $headers['Content-Type'] ?? null);
        $this->assertSame('noindex, nofollow', $headers['X-Robots-Tag'] ?? null);
        $this->assertArrayHasKey('Cache-Control', $headers);
        // The AJAX challenge header must NOT be present on an inline response.
        $this->assertArrayNotHasKey(self::CHALLENGE_HEADER, $headers);
    }

    public function testEmitsAjaxChallengeWithChallengeHeader(): void
    {
        $this->requestChecker->method('needsChallenge')->willReturn(true);
        $this->cookieManager->method('isVerified')->willReturn(false);
        $this->config->method('isDebug')->willReturn(false);
        $this->refIdGenerator->method('generate')->willReturn('B1C2D3E4');
        $this->challengeRenderer->method('render')->willReturn('{}');

        // XHR request -> AJAX mode.
        $this->request->method('isXmlHttpRequest')->willReturn(true);
        $this->request->method('getHeader')->willReturn('');

        $headers = $this->captureHeaders();

        $this->observer->execute($this->createMock(Observer::class));

        $this->assertSame('1', $headers[self::CHALLENGE_HEADER] ?? null);
    }

    public function testLogsWhenDebugEnabled(): void
    {
        $this->requestChecker->method('needsChallenge')->willReturn(true);
        $this->cookieManager->method('isVerified')->willReturn(false);
        $this->config->method('isDebug')->willReturn(true);
        $this->refIdGenerator->method('generate')->willReturn('A7F23K9M');
        $this->challengeRenderer->method('render')->willReturn('<html></html>');
        $this->request->method('isXmlHttpRequest')->willReturn(false);
        $this->request->method('isAjax')->willReturn(false);
        $this->request->method('getHeader')->willReturn('');
        $this->request->method('getRequestUri')->willReturn('/protected/page');
        $this->requestChecker->method('getClientIp')->willReturn('203.0.113.5');
        $this->response->method('setHeader')->willReturnSelf();

        $this->logger->expects($this->once())->method('info')
            ->with($this->stringContains('ref=A7F23K9M'));

        $this->observer->execute($this->createMock(Observer::class));
    }

    public function testDoesNotLogWhenDebugDisabled(): void
    {
        $this->requestChecker->method('needsChallenge')->willReturn(true);
        $this->cookieManager->method('isVerified')->willReturn(false);
        $this->config->method('isDebug')->willReturn(false);
        $this->refIdGenerator->method('generate')->willReturn('A7F23K9M');
        $this->challengeRenderer->method('render')->willReturn('<html></html>');
        $this->request->method('isXmlHttpRequest')->willReturn(false);
        $this->request->method('isAjax')->willReturn(false);
        $this->request->method('getHeader')->willReturn('');
        $this->response->method('setHeader')->willReturnSelf();

        $this->logger->expects($this->never())->method('info');

        $this->observer->execute($this->createMock(Observer::class));
    }

    /**
     * Record every header written to the response into a name => value map.
     *
     * Returns an ArrayObject (shared handle) rather than an array: a plain
     * array would be returned by value while the closure mutates the local,
     * so the caller's copy would never see the captured headers.
     *
     * @return \ArrayObject<string, string>
     */
    private function captureHeaders(): \ArrayObject
    {
        $headers = new \ArrayObject();
        $this->response->method('setHeader')->willReturnCallback(
            function (string $name, $value) use ($headers): HttpResponse {
                $headers[$name] = $value;

                return $this->response;
            }
        );

        return $headers;
    }
}
