<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Integration\RouteProtection;

use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Test\Integration\ControllerTestAccess;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\App\MutableScopeConfig;
use Magento\TestFramework\TestCase\AbstractController;
use Laminas\Http\Header\HeaderInterface;

/**
 * Shared setup for the route-gate controller tests.
 *
 * The module reads every one of its settings at store scope, and the test
 * config answers a store-scope read only from a value written at store scope
 * (a default-scope write is invisible to it, so the gate never engages and the
 * page renders with 200). {@see setConfig()} therefore writes at store scope
 * unless a test names the scope a setting is really read at.
 */
abstract class RouteGateTestCase extends AbstractController
{
    use ControllerTestAccess;

    protected const SECRET = 'test_secret_key';
    protected const TARGET_ROUTE = 'cms/index/index';

    /**
     * @var MutableScopeConfig
     */
    protected $mutableConfig;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mutableConfig = $this->service(MutableScopeConfig::class);
        $this->encryptor = $this->service(EncryptorInterface::class);
        $this->json = $this->service(Json::class);
    }

    /**
     * Enable the module with Turnstile as the route-gate provider and route
     * protection on, guarded by a single condition.
     *
     * @param string $field Rule field code.
     * @param string $operator Rule operator code.
     * @param string $value Rule operand.
     * @return void
     */
    protected function enableRouteGate(string $field, string $operator, string $value): void
    {
        $this->setConfig('hryvinskyi_invisible_captcha/general/enabled', '1');
        $this->setConfig('hryvinskyi_invisible_captcha/general/active_provider', ProviderInterface::CODE_TURNSTILE);
        $this->setConfig('hryvinskyi_invisible_captcha/providers/turnstile/site_key', 'test_site_key');
        $this->setConfig(
            'hryvinskyi_invisible_captcha/providers/turnstile/secret_key',
            $this->encryptor->encrypt(self::SECRET)
        );
        $this->setConfig('hryvinskyi_invisible_captcha/route_protection/enabled', '1');
        $this->setRouteRule($field, $operator, $value);
    }

    /**
     * Store a single-condition rule for the route gate.
     *
     * @param string $field Rule field code.
     * @param string $operator Rule operator code.
     * @param string $value Rule operand.
     * @return void
     */
    protected function setRouteRule(string $field, string $operator, string $value): void
    {
        $rules = $this->json->serialize([
            [
                'combinator' => 'and',
                'field' => $field,
                'operator' => $operator,
                'value' => $value,
            ],
        ]);
        self::assertIsString($rules);
        $this->setConfig('hryvinskyi_invisible_captcha/route_protection/rules', $rules);
    }

    /**
     * Assert the inline challenge replaced the page.
     *
     * @return void
     */
    protected function assertChallengeServed(): void
    {
        $response = $this->httpResponse();
        self::assertSame(403, $response->getHttpResponseCode());
        $robots = $response->getHeader('X-Robots-Tag');
        self::assertInstanceOf(HeaderInterface::class, $robots, 'The challenge carries one X-Robots-Tag header');
        self::assertEquals('noindex, nofollow', $robots->getFieldValue());
        self::assertStringContainsString('class="band"', $this->responseBody());
    }

    /**
     * Assert the page rendered normally with no challenge body.
     *
     * @return void
     */
    protected function assertNoChallenge(): void
    {
        self::assertSame(200, $this->httpResponse()->getHttpResponseCode());
        self::assertStringNotContainsString('class="band"', $this->responseBody());
    }

    /**
     * Persist a config value in the mutable test config.
     *
     * @param string $path
     * @param string $value
     * @param string $scope Scope the setting is read at; store scope by default.
     * @return void
     */
    protected function setConfig(string $path, string $value, string $scope = ScopeInterface::SCOPE_STORE): void
    {
        $this->mutableConfig->setValue($path, $value, $scope);
    }
}
