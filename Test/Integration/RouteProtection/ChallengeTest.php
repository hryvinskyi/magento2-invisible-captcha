<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Integration\RouteProtection;

use Hryvinskyi\InvisibleCaptcha\Model\CookieManager;
use Hryvinskyi\InvisibleCaptcha\Model\RequestChecker;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Config\ConfigOptionsListConstants;

/**
 * Integration coverage for the route-level captcha gate
 * ({@see \Hryvinskyi\InvisibleCaptcha\Observer\RouteGate}, fired on
 * `controller_action_predispatch`).
 *
 * With route protection enabled, a configured Turnstile provider, a rule that
 * matches the requested route (action_name eq cms_index_index) and no verified
 * cookie, the gate replaces the page with the inline challenge (HTTP 403). A
 * valid verified cookie or an excluded IP / user-agent bypasses the gate so the
 * page renders normally.
 *
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class ChallengeTest extends RouteGateTestCase
{
    private const TARGET_ACTION_NAME = 'cms_index_index';

    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->deploymentConfig = $this->service(DeploymentConfig::class);
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        unset($_COOKIE[CookieManager::COOKIE_NAME]);
        parent::tearDown();
    }

    /**
     * A matched, unverified request is replaced with the inline challenge (403).
     */
    public function testChallengeIsServedWhenNotVerified(): void
    {
        $this->enableRouteGate('action_name', 'eq', self::TARGET_ACTION_NAME);

        $this->dispatch(self::TARGET_ROUTE);

        $this->assertChallengeServed();
    }

    /**
     * A valid verified cookie lets the gate pass and the page render normally.
     */
    public function testRouteRendersWithValidVerifiedCookie(): void
    {
        $this->enableRouteGate('action_name', 'eq', self::TARGET_ACTION_NAME);
        $this->issueVerifiedCookie();

        // Sanity check: the crafted cookie is accepted by the real CookieManager.
        self::assertTrue($this->service(CookieManager::class)->isVerified());

        $this->dispatch(self::TARGET_ROUTE);

        $this->assertNoChallenge();
    }

    /**
     * An excluded user-agent bypasses the gate entirely.
     */
    public function testExcludedUserAgentBypassesChallenge(): void
    {
        $this->enableRouteGate('action_name', 'eq', self::TARGET_ACTION_NAME);
        $this->setConfig('hryvinskyi_invisible_captcha/route_protection/excluded_user_agents', 'IntegrationBypassBot');
        $this->requestHeaders()->addHeaderLine('User-Agent', 'IntegrationBypassBot/1.0');

        $this->dispatch(self::TARGET_ROUTE);

        self::assertSame(200, $this->httpResponse()->getHttpResponseCode());
    }

    /**
     * An excluded client IP bypasses the gate entirely.
     */
    public function testExcludedIpBypassesChallenge(): void
    {
        $this->enableRouteGate('action_name', 'eq', self::TARGET_ACTION_NAME);

        $clientIp = '198.51.100.24';
        $server = $this->serverParameters();
        $server->set('HTTP_CF_CONNECTING_IP', '');
        $server->set('HTTP_X_REAL_IP', '');
        $server->set('HTTP_X_FORWARDED_FOR', '');
        $server->set('REMOTE_ADDR', $clientIp);

        // Confirm the resolved client IP matches before excluding it.
        self::assertSame($clientIp, $this->service(RequestChecker::class)->getClientIp());
        $this->setConfig('hryvinskyi_invisible_captcha/route_protection/excluded_ips', $clientIp);

        $this->dispatch(self::TARGET_ROUTE);

        self::assertSame(200, $this->httpResponse()->getHttpResponseCode());
    }

    /**
     * Craft and inject a valid verification cookie using the same HMAC scheme as
     * {@see CookieManager} (keyed on the deployment crypt key).
     */
    private function issueVerifiedCookie(): void
    {
        $cryptKey = $this->deploymentConfig->get(ConfigOptionsListConstants::CONFIG_PATH_CRYPT_KEY);
        self::assertIsString($cryptKey);
        $keys = array_filter(explode("\n", $cryptKey));
        $hmacKey = $keys !== [] ? (string)end($keys) : 'hryvinskyi-invisible-captcha';

        $expiry = time() + 3600;
        $_COOKIE[CookieManager::COOKIE_NAME] = $expiry . ':' . hash_hmac('sha256', (string)$expiry, $hmacKey);
    }
}
