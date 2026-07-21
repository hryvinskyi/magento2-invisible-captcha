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
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\TestFramework\App\MutableScopeConfig;
use Magento\TestFramework\TestCase\AbstractController;

/**
 * Integration coverage for the `country` route-gate field
 * ({@see \Hryvinskyi\InvisibleCaptcha\Model\Filter\Field\Country}) resolved via
 * the Cloudflare `CF-IPCountry` source.
 *
 * With route protection enabled, a Turnstile provider configured, the
 * geolocation source set to `cloudflare` and a country rule, the gate replaces
 * the page with the inline challenge (HTTP 403) when the resolved country
 * matches the rule. A different country, or an unresolved country against a
 * positive operator, bypasses the gate; an unresolved country against a negative
 * operator (`not in list`) matches, since unknown resolves to the empty string.
 *
 * The `CF-IPCountry` value is injected on the request's mutable server bag before
 * dispatch — the same mechanism {@see ChallengeTest::testExcludedIpBypassesChallenge}
 * uses to seed IP headers the observer chain reads.
 *
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class CountryFilterTest extends AbstractController
{
    private const SECRET = 'test_secret_key';
    private const TARGET_ROUTE = 'cms/index/index';

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
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mutableConfig = $this->_objectManager->get(MutableScopeConfig::class);
        $this->encryptor = $this->_objectManager->get(EncryptorInterface::class);
        $this->json = $this->_objectManager->get(Json::class);
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
     * A resolved country matching a positive rule is challenged (403).
     */
    public function testMatchingCountryServesChallenge(): void
    {
        $this->enableRouteProtection();
        $this->setCountryRule('eq', 'UA');
        $this->getRequest()->getServer()->set('HTTP_CF_IPCOUNTRY', 'UA');

        $this->dispatch(self::TARGET_ROUTE);

        $this->assertChallengeServed();
    }

    /**
     * A resolved country that does not match the rule renders the page (200).
     */
    public function testDifferentCountryRendersPage(): void
    {
        $this->enableRouteProtection();
        $this->setCountryRule('eq', 'UA');
        $this->getRequest()->getServer()->set('HTTP_CF_IPCOUNTRY', 'DE');

        $this->dispatch(self::TARGET_ROUTE);

        $this->assertNoChallenge();
    }

    /**
     * An absent header (unknown country → empty string) does not match a
     * positive rule, so the page renders (200).
     */
    public function testUnknownCountryDoesNotMatchPositiveRule(): void
    {
        $this->enableRouteProtection();
        $this->setCountryRule('eq', 'UA');

        $this->dispatch(self::TARGET_ROUTE);

        $this->assertNoChallenge();
    }

    /**
     * An absent header (unknown country → empty string) matches a negative
     * operator, so `not in list` challenges traffic whose country is unknown.
     */
    public function testUnknownCountryMatchesNegativeRule(): void
    {
        $this->enableRouteProtection();
        $this->setCountryRule('not_in', 'UA US');

        $this->dispatch(self::TARGET_ROUTE);

        $this->assertChallengeServed();
    }

    /**
     * Enable the module, configure Turnstile as the route-gate provider, turn on
     * route protection and select the Cloudflare geolocation source.
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
        $this->setConfig('hryvinskyi_invisible_captcha/geolocation/source', 'cloudflare');
    }

    /**
     * Store a single-condition country rule for the route gate.
     *
     * @param string $operator
     * @param string $value
     */
    private function setCountryRule(string $operator, string $value): void
    {
        $this->setConfig(
            'hryvinskyi_invisible_captcha/route_protection/rules',
            $this->json->serialize([
                [
                    'combinator' => 'and',
                    'field' => 'country',
                    'operator' => $operator,
                    'value' => $value,
                ],
            ])
        );
    }

    /**
     * Assert the inline challenge replaced the page (mirrors ChallengeTest).
     */
    private function assertChallengeServed(): void
    {
        $response = $this->getResponse();
        self::assertSame(403, $response->getHttpResponseCode());
        self::assertEquals(
            'noindex, nofollow',
            $response->getHeader('X-Robots-Tag')->getFieldValue()
        );
        self::assertStringContainsString('class="band"', (string)$response->getBody());
    }

    /**
     * Assert the page rendered normally with no challenge body.
     */
    private function assertNoChallenge(): void
    {
        $response = $this->getResponse();
        self::assertSame(200, $response->getHttpResponseCode());
        self::assertStringNotContainsString('class="band"', (string)$response->getBody());
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
