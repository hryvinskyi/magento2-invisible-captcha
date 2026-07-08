<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Integration\RouteProtection;

use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Model\CookieManager;
use Hryvinskyi\InvisibleCaptcha\Model\RequestChecker;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\TestFramework\App\MutableScopeConfig;
use Magento\TestFramework\TestCase\AbstractController;

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
class ChallengeTest extends AbstractController
{
    private const SECRET = 'test_secret_key';
    private const TARGET_ROUTE = 'cms/index/index';
    private const TARGET_ACTION_NAME = 'cms_index_index';

    /**
     * @var MutableScopeConfig
     */
    private $mutableConfig;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var Json
     */
    private $json;

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

        $this->mutableConfig = $this->_objectManager->get(MutableScopeConfig::class);
        $this->encryptor = $this->_objectManager->get(EncryptorInterface::class);
        $this->json = $this->_objectManager->get(Json::class);
        $this->deploymentConfig = $this->_objectManager->get(DeploymentConfig::class);
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
        $this->enableRouteProtection();

        $this->dispatch(self::TARGET_ROUTE);

        $response = $this->getResponse();
        self::assertSame(403, $response->getHttpResponseCode());
        self::assertEquals(
            'noindex, nofollow',
            $response->getHeader('X-Robots-Tag')->getFieldValue()
        );
        self::assertStringContainsString('class="band"', (string)$response->getBody());
    }

    /**
     * A valid verified cookie lets the gate pass and the page render normally.
     */
    public function testRouteRendersWithValidVerifiedCookie(): void
    {
        $this->enableRouteProtection();
        $this->issueVerifiedCookie();

        // Sanity check: the crafted cookie is accepted by the real CookieManager.
        self::assertTrue($this->_objectManager->get(CookieManager::class)->isVerified());

        $this->dispatch(self::TARGET_ROUTE);

        $response = $this->getResponse();
        self::assertSame(200, $response->getHttpResponseCode());
        self::assertStringNotContainsString('class="band"', (string)$response->getBody());
    }

    /**
     * An excluded user-agent bypasses the gate entirely.
     */
    public function testExcludedUserAgentBypassesChallenge(): void
    {
        $this->enableRouteProtection();
        $this->setConfig('hryvinskyi_invisible_captcha/route_protection/excluded_user_agents', 'IntegrationBypassBot');
        $this->getRequest()->getHeaders()->addHeaderLine('User-Agent', 'IntegrationBypassBot/1.0');

        $this->dispatch(self::TARGET_ROUTE);

        self::assertSame(200, $this->getResponse()->getHttpResponseCode());
    }

    /**
     * An excluded client IP bypasses the gate entirely.
     */
    public function testExcludedIpBypassesChallenge(): void
    {
        $this->enableRouteProtection();

        $clientIp = '198.51.100.24';
        $server = $this->getRequest()->getServer();
        $server->set('HTTP_CF_CONNECTING_IP', '');
        $server->set('HTTP_X_REAL_IP', '');
        $server->set('HTTP_X_FORWARDED_FOR', '');
        $server->set('REMOTE_ADDR', $clientIp);

        // Confirm the resolved client IP matches before excluding it.
        self::assertSame($clientIp, $this->_objectManager->get(RequestChecker::class)->getClientIp());
        $this->setConfig('hryvinskyi_invisible_captcha/route_protection/excluded_ips', $clientIp);

        $this->dispatch(self::TARGET_ROUTE);

        self::assertSame(200, $this->getResponse()->getHttpResponseCode());
    }

    /**
     * Enable the module, configure Turnstile as the route-gate provider and add a
     * rule that matches the target route.
     */
    private function enableRouteProtection(): void
    {
        $this->setConfig('hryvinskyi_invisible_captcha/general/enabled', '1');
        $this->setConfig('hryvinskyi_invisible_captcha/general/active_provider', ProviderInterface::CODE_TURNSTILE);
        $this->setConfig('hryvinskyi_invisible_captcha/providers/turnstile/site_key', 'test_site_key');
        $this->setConfig(
            'hryvinskyi_invisible_captcha/providers/turnstile/secret_key',
            $this->encryptor->encrypt(self::SECRET)
        );
        $this->setConfig('hryvinskyi_invisible_captcha/route_protection/enabled', '1');
        $this->setConfig(
            'hryvinskyi_invisible_captcha/route_protection/rules',
            $this->json->serialize([
                [
                    'combinator' => 'and',
                    'field' => 'action_name',
                    'operator' => 'eq',
                    'value' => self::TARGET_ACTION_NAME,
                ],
            ])
        );
    }

    /**
     * Craft and inject a valid verification cookie using the same HMAC scheme as
     * {@see CookieManager} (keyed on the deployment crypt key).
     */
    private function issueVerifiedCookie(): void
    {
        $cryptKey = (string)$this->deploymentConfig->get(ConfigOptionsListConstants::CONFIG_PATH_CRYPT_KEY);
        $keys = array_filter(explode("\n", $cryptKey));
        $hmacKey = $keys !== [] ? (string)end($keys) : 'hryvinskyi-invisible-captcha';

        $expiry = time() + 3600;
        $_COOKIE[CookieManager::COOKIE_NAME] = $expiry . ':' . hash_hmac('sha256', (string)$expiry, $hmacKey);
    }

    /**
     * Persist a default-scope config value via the mutable test config.
     *
     * @param string $path
     * @param string $value
     */
    private function setConfig(string $path, string $value): void
    {
        $this->mutableConfig->setValue($path, $value, ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
    }
}
