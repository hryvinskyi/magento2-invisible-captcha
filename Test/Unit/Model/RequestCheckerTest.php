<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\ExpressionEvaluatorInterface;
use Hryvinskyi\InvisibleCaptcha\Api\ExpressionInterface;
use Hryvinskyi\InvisibleCaptcha\Api\ExpressionParserInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Http\ClientIpResolverInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderPoolInterface;
use Hryvinskyi\InvisibleCaptcha\Model\ExclusionPolicy;
use Hryvinskyi\InvisibleCaptcha\Model\PathPatternMatcher;
use Hryvinskyi\InvisibleCaptcha\Model\RequestChecker;
use Magento\Framework\App\Request\Http as HttpRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RequestCheckerTest extends TestCase
{
    /** @var ConfigInterface&MockObject */
    private ConfigInterface $config;
    /** @var ProviderPoolInterface&MockObject */
    private ProviderPoolInterface $providerPool;
    /** @var ProviderInterface&MockObject */
    private ProviderInterface $routeGateProvider;
    /** @var ExpressionParserInterface&MockObject */
    private ExpressionParserInterface $parser;
    /** @var ExpressionEvaluatorInterface&MockObject */
    private ExpressionEvaluatorInterface $evaluator;
    /** @var ClientIpResolverInterface&MockObject */
    private ClientIpResolverInterface $clientIpResolver;
    /** @var HttpRequest&MockObject */
    private HttpRequest $request;
    private RequestChecker $checker;

    protected function setUp(): void
    {
        $this->config = $this->createMock(ConfigInterface::class);
        $this->providerPool = $this->createMock(ProviderPoolInterface::class);
        $this->routeGateProvider = $this->createMock(ProviderInterface::class);
        $this->parser = $this->createMock(ExpressionParserInterface::class);
        $this->evaluator = $this->createMock(ExpressionEvaluatorInterface::class);
        $this->clientIpResolver = $this->createMock(ClientIpResolverInterface::class);
        $this->request = $this->createMock(HttpRequest::class);

        // The route-gate provider is resolved through the pool; default it to "configured".
        $this->providerPool->method('getRouteGateProvider')->willReturn($this->routeGateProvider);
        $this->routeGateProvider->method('isConfigured')->willReturn(true);

        $this->checker = new RequestChecker(
            $this->config,
            $this->providerPool,
            $this->parser,
            $this->evaluator,
            $this->clientIpResolver,
            new ExclusionPolicy($this->config, new PathPatternMatcher()),
            $this->request
        );
    }

    public function testReturnsFalseWhenRouteProtectionDisabled(): void
    {
        $this->config->method('isRouteProtectionEnabled')->willReturn(false);
        $this->evaluator->expects($this->never())->method('evaluate');
        $this->assertFalse($this->checker->needsChallenge());
    }

    public function testReturnsFalseWhenRouteGateProviderNotConfigured(): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->method('isRouteProtectionEnabled')->willReturn(true);

        $provider = $this->createMock(ProviderInterface::class);
        $provider->method('isConfigured')->willReturn(false);
        $providerPool = $this->createMock(ProviderPoolInterface::class);
        $providerPool->method('getRouteGateProvider')->willReturn($provider);

        $checker = new RequestChecker(
            $config,
            $providerPool,
            $this->parser,
            $this->evaluator,
            $this->clientIpResolver,
            new ExclusionPolicy($config, new PathPatternMatcher()),
            $this->request
        );

        $this->evaluator->expects($this->never())->method('evaluate');
        $this->assertFalse($checker->needsChallenge());
    }

    public function testVerifyEndpointIsNeverChallenged(): void
    {
        // The challenge page POSTs its token here — gating it would deadlock
        // verification for any rule broad enough to match (e.g. a catch-all).
        $this->config->method('isRouteProtectionEnabled')->willReturn(true);
        $this->config->method('getExcludedIps')->willReturn([]);
        $this->config->method('getExcludedUserAgents')->willReturn([]);
        $this->request->method('getPathInfo')->willReturn('/invisiblecaptcha/verify');
        $this->evaluator->expects($this->never())->method('evaluate');

        $this->assertFalse($this->checker->needsChallenge());
    }

    public function testVerifyEndpointExclusionToleratesTrailingSlash(): void
    {
        $this->config->method('isRouteProtectionEnabled')->willReturn(true);
        $this->request->method('getPathInfo')->willReturn('/invisiblecaptcha/verify/');
        $this->evaluator->expects($this->never())->method('evaluate');

        $this->assertFalse($this->checker->needsChallenge());
    }

    public function testReturnsFalseForExcludedIp(): void
    {
        $this->config->method('isRouteProtectionEnabled')->willReturn(true);
        $this->config->method('getExcludedIps')->willReturn(['1.2.3.4']);
        $this->clientIpResolver->method('resolve')->willReturn('1.2.3.4');
        $this->evaluator->expects($this->never())->method('evaluate');
        $this->assertFalse($this->checker->needsChallenge());
    }

    public function testReturnsFalseForExcludedUserAgentSubstring(): void
    {
        $this->config->method('isRouteProtectionEnabled')->willReturn(true);
        $this->config->method('getExcludedIps')->willReturn([]);
        $this->config->method('getExcludedUserAgents')->willReturn(['Googlebot']);
        $this->request->method('getHeader')->with('User-Agent')->willReturn('Mozilla/5.0 (compatible; Googlebot/2.1)');
        $this->evaluator->expects($this->never())->method('evaluate');
        $this->assertFalse($this->checker->needsChallenge());
    }

    public function testReturnsFalseForExcludedPath(): void
    {
        $this->config->method('isRouteProtectionEnabled')->willReturn(true);
        $this->config->method('getExcludedIps')->willReturn([]);
        $this->config->method('getExcludedUserAgents')->willReturn([]);
        $this->config->method('getExcludedPaths')->willReturn(['customer/section/load']);
        $this->request->method('getPathInfo')->willReturn('/customer/section/load/');
        $this->evaluator->expects($this->never())->method('evaluate');

        $this->assertFalse($this->checker->needsChallenge());
    }

    public function testEmptyUserAgentDoesNotMatchExclusionList(): void
    {
        $this->config->method('isRouteProtectionEnabled')->willReturn(true);
        $this->config->method('getExcludedIps')->willReturn([]);
        $this->config->method('getExcludedUserAgents')->willReturn(['Googlebot']);
        $this->request->method('getHeader')->willReturn('');
        $this->config->method('getProtectionRulesConfig')->willReturn([]);
        $expression = $this->createMock(ExpressionInterface::class);
        $this->parser->method('parse')->willReturn($expression);
        $this->evaluator->method('evaluate')->willReturn(true);

        $this->assertTrue($this->checker->needsChallenge());
    }

    public function testEvaluatesExpressionWhenNotExcluded(): void
    {
        $this->config->method('isRouteProtectionEnabled')->willReturn(true);
        $this->config->method('getExcludedIps')->willReturn([]);
        $this->config->method('getExcludedUserAgents')->willReturn([]);
        $rows = [['combinator' => 'and', 'field' => 'action_name', 'operator' => 'eq', 'value' => 'home']];
        $this->config->method('getProtectionRulesConfig')->willReturn($rows);

        $expression = $this->createMock(ExpressionInterface::class);
        $this->parser->expects($this->once())->method('parse')->with($rows)->willReturn($expression);
        $this->evaluator->expects($this->once())->method('evaluate')->with($expression)->willReturn(true);

        $this->assertTrue($this->checker->needsChallenge());
    }

    public function testReturnsFalseWhenExpressionDoesNotMatch(): void
    {
        $this->config->method('isRouteProtectionEnabled')->willReturn(true);
        $this->config->method('getExcludedIps')->willReturn([]);
        $this->config->method('getExcludedUserAgents')->willReturn([]);
        $this->config->method('getProtectionRulesConfig')->willReturn([]);
        $expression = $this->createMock(ExpressionInterface::class);
        $this->parser->method('parse')->willReturn($expression);
        $this->evaluator->method('evaluate')->willReturn(false);

        $this->assertFalse($this->checker->needsChallenge());
    }

    public function testIsConfiguredReflectsRouteProtectionAndProvider(): void
    {
        $this->config->method('isRouteProtectionEnabled')->willReturn(true);
        $this->assertTrue($this->checker->isConfigured());
    }

    public function testGetClientIpDelegatesToResolver(): void
    {
        $this->clientIpResolver->method('resolve')->willReturn('203.0.113.1');
        $this->assertSame('203.0.113.1', $this->checker->getClientIp());
    }

    public function testUserAgentExclusionCaseInsensitive(): void
    {
        $this->config->method('isRouteProtectionEnabled')->willReturn(true);
        $this->config->method('getExcludedIps')->willReturn([]);
        $this->config->method('getExcludedUserAgents')->willReturn(['googlebot']);
        $this->request->method('getHeader')->willReturn('Mozilla/5.0 (compatible; GoogleBot/2.1)');
        $this->evaluator->expects($this->never())->method('evaluate');
        $this->assertFalse($this->checker->needsChallenge());
    }
}
