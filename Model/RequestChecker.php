<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\ExpressionEvaluatorInterface;
use Hryvinskyi\InvisibleCaptcha\Api\ExpressionParserInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderPoolInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Field\ClientIp;
use Magento\Framework\App\RequestInterface;

/**
 * Decides whether the current request must pass the route-level challenge.
 */
class RequestChecker
{
    /**
     * @param ConfigInterface $config
     * @param ProviderPoolInterface $providerPool
     * @param ExpressionParserInterface $expressionParser
     * @param ExpressionEvaluatorInterface $expressionEvaluator
     * @param ClientIp $clientIp
     * @param RequestInterface $request
     */
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly ProviderPoolInterface $providerPool,
        private readonly ExpressionParserInterface $expressionParser,
        private readonly ExpressionEvaluatorInterface $expressionEvaluator,
        private readonly ClientIp $clientIp,
        private readonly RequestInterface $request
    ) {
    }

    /**
     * Whether route-level protection is active and configured for this scope.
     */
    public function isConfigured(): bool
    {
        if (!$this->config->isRouteProtectionEnabled()) {
            return false;
        }

        return $this->providerPool->getRouteGateProvider()->isConfigured();
    }

    /**
     * Decide whether the current request must pass the challenge.
     */
    public function needsChallenge(): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        if ($this->isExcludedIp() || $this->isExcludedUserAgent()) {
            return false;
        }

        $expression = $this->expressionParser->parse($this->config->getProtectionRulesConfig());

        return $this->expressionEvaluator->evaluate($expression);
    }

    /**
     * Resolve the client IP via the shared ClientIp filter field.
     */
    public function getClientIp(): string
    {
        return $this->clientIp->getValue();
    }

    /**
     * Whether the client IP is on the bypass list.
     */
    private function isExcludedIp(): bool
    {
        $excludedIps = $this->config->getExcludedIps();
        if ($excludedIps === []) {
            return false;
        }

        return in_array($this->getClientIp(), $excludedIps, true);
    }

    /**
     * Whether the request user agent matches any configured bypass substring.
     */
    private function isExcludedUserAgent(): bool
    {
        $excludedUserAgents = $this->config->getExcludedUserAgents();
        if ($excludedUserAgents === []) {
            return false;
        }

        $userAgent = (string)$this->request->getHeader('User-Agent');
        if ($userAgent === '') {
            return false;
        }

        foreach ($excludedUserAgents as $excluded) {
            if (stripos($userAgent, $excluded) !== false) {
                return true;
            }
        }

        return false;
    }
}
