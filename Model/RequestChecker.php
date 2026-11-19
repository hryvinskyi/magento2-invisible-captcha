<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\CrawlerExemptionInterface;
use Hryvinskyi\InvisibleCaptcha\Api\ExclusionPolicyInterface;
use Hryvinskyi\InvisibleCaptcha\Api\ExpressionEvaluatorInterface;
use Hryvinskyi\InvisibleCaptcha\Api\ExpressionParserInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Http\ClientIpResolverInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderPoolInterface;
use Hryvinskyi\InvisibleCaptcha\Api\RequestCheckerInterface;
use Hryvinskyi\InvisibleCaptcha\Controller\Router\VerificationRouter;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\RequestInterface;

/**
 * Decides whether the current request must pass the route-level challenge.
 */
class RequestChecker implements RequestCheckerInterface
{
    /**
     * @param ConfigInterface $config
     * @param ProviderPoolInterface $providerPool
     * @param ExpressionParserInterface $expressionParser
     * @param ExpressionEvaluatorInterface $expressionEvaluator
     * @param ClientIpResolverInterface $clientIpResolver
     * @param ExclusionPolicyInterface $exclusionPolicy
     * @param CrawlerExemptionInterface $crawlerExemption
     * @param RequestInterface $request
     */
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly ProviderPoolInterface $providerPool,
        private readonly ExpressionParserInterface $expressionParser,
        private readonly ExpressionEvaluatorInterface $expressionEvaluator,
        private readonly ClientIpResolverInterface $clientIpResolver,
        private readonly ExclusionPolicyInterface $exclusionPolicy,
        private readonly CrawlerExemptionInterface $crawlerExemption,
        private readonly RequestInterface $request
    ) {
    }

    /**
     * @inheritDoc
     */
    public function isConfigured(): bool
    {
        if (!$this->config->isRouteProtectionEnabled()) {
            return false;
        }

        return $this->providerPool->getRouteGateProvider()->isConfigured();
    }

    /**
     * @inheritDoc
     */
    public function needsChallenge(): bool
    {
        // The verify endpoint must never be gated: the challenge page POSTs the
        // captcha token here, so challenging it would deadlock verification for
        // any rule broad enough to match it (e.g. a catch-all).
        if ($this->isVerifyEndpoint()) {
            return false;
        }

        if (!$this->isConfigured()) {
            return false;
        }

        if ($this->isExcludedIp() || $this->crawlerExemption->isExempt() || $this->isExcludedPath()) {
            return false;
        }

        $expression = $this->expressionParser->parse($this->config->getProtectionRulesConfig());

        return $this->expressionEvaluator->evaluate($expression);
    }

    /**
     * Whether the current request targets the captcha verify endpoint (same
     * path normalization as {@see VerificationRouter::match()}).
     */
    private function isVerifyEndpoint(): bool
    {
        if (!$this->request instanceof HttpRequest) {
            return false;
        }

        return trim((string)$this->request->getPathInfo(), '/') === VerificationRouter::VERIFY_PATH;
    }

    /**
     * Whether the client IP is on the bypass list.
     */
    private function isExcludedIp(): bool
    {
        return $this->exclusionPolicy->isIpExcluded($this->clientIpResolver->resolve());
    }

    /**
     * Whether the request path is on the "Excluded Paths" bypass list —
     * matched against the store-code-stripped path info, so background
     * endpoints stay unchallenged whatever the rules say.
     */
    private function isExcludedPath(): bool
    {
        if (!$this->request instanceof HttpRequest) {
            return false;
        }

        return $this->exclusionPolicy->isPathExcluded((string)$this->request->getPathInfo());
    }
}
