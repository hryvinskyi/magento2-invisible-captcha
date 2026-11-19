<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderPoolInterface;
use Hryvinskyi\InvisibleCaptcha\Api\RequestCheckerInterface;
use Hryvinskyi\InvisibleCaptcha\Api\SilentVerificationInterface;
use Hryvinskyi\InvisibleCaptcha\Api\VerificationCookieInterface;
use Hryvinskyi\InvisibleCaptcha\Controller\Router\VerificationRouter;
use Magento\Framework\UrlInterface;

/**
 * Background verification for the route-gate provider, where the provider can
 * issue a token invisibly.
 *
 * Which providers can, and how, is configuration: `silentModes` maps a provider
 * code to the way the browser asks it for a token (`widget` — an invisible
 * widget that gives up when it would need interaction; `score` — a score-based
 * execute call). A provider without an entry never verifies in the background.
 *
 * Whether the page carries the verification depends on the visitor's
 * verification cookie. The full page cache keeps verified and unverified
 * visitors apart through the HTTP context, so each group is served its own copy.
 */
class SilentVerification implements SilentVerificationInterface
{
    /**
     * Action reported with a score-based token; the same one the challenge page reports.
     */
    private const ACTION = 'route_gate';

    /**
     * @param ConfigInterface $config
     * @param RequestCheckerInterface $requestChecker
     * @param ProviderPoolInterface $providerPool
     * @param VerificationCookieInterface $verificationCookie
     * @param UrlInterface $url
     * @param array<string, string> $silentModes Provider code => how the browser gets a token invisibly
     */
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly RequestCheckerInterface $requestChecker,
        private readonly ProviderPoolInterface $providerPool,
        private readonly VerificationCookieInterface $verificationCookie,
        private readonly UrlInterface $url,
        private readonly array $silentModes = []
    ) {
    }

    /**
     * @inheritDoc
     */
    public function isActive(): bool
    {
        if (!$this->config->isSilentVerificationEnabled() || !$this->requestChecker->isConfigured()) {
            return false;
        }

        if ($this->mode() === '') {
            return false;
        }

        return !$this->verificationCookie->isVerified();
    }

    /**
     * @inheritDoc
     */
    public function getClientConfig(): array
    {
        $provider = $this->providerPool->getRouteGateProvider();

        return [
            'verifyUrl' => rtrim($this->url->getBaseUrl(), '/') . '/' . VerificationRouter::VERIFY_PATH,
            'mode' => $this->mode(),
            'provider' => $provider->getCode(),
            'siteKey' => $provider->getSiteKey(),
            'responseParam' => $provider->getResponseParamName(),
            'scriptUrl' => $provider->getClientScriptUrl(),
            'render' => $provider->getRenderConfig(null, ['action' => self::ACTION]),
        ];
    }

    /**
     * How the route-gate provider issues a token invisibly; empty when it cannot.
     */
    private function mode(): string
    {
        return $this->silentModes[$this->providerPool->getRouteGateProvider()->getCode()] ?? '';
    }
}
