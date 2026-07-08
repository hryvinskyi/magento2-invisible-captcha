<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Api\GraphQl;

use Magento\TestFramework\Fixture\Config as ConfigFixture;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * GraphQL test for the hryvinskyiInvisibleCaptchaConfig query.
 *
 * The query exposes the active provider's public client configuration so headless clients can render
 * the widget and submit the token via the X-Captcha-Token header. It is resolved by
 * Hryvinskyi\InvisibleCaptcha\Model\Resolver\CaptchaConfig and is NOT a mutation, so the captcha
 * validator plugin does not gate it.
 */
class CaptchaConfigQueryTest extends GraphQlAbstract
{
    #[
        ConfigFixture('hryvinskyi_invisible_captcha/general/enabled', 1),
        ConfigFixture('hryvinskyi_invisible_captcha/general/active_provider', 'turnstile'),
        ConfigFixture('hryvinskyi_invisible_captcha/providers/turnstile/site_key', 'test_site_key_turnstile'),
        ConfigFixture('hryvinskyi_invisible_captcha/form_protection/enabled', 1),
        ConfigFixture('hryvinskyi_invisible_captcha/form_protection/frontend/enabled', 1),
        ConfigFixture('hryvinskyi_invisible_captcha/form_protection/frontend/enabled_customer_login', 1)
    ]
    public function testReturnsActiveProviderConfig(): void
    {
        $response = $this->graphQlQuery($this->getQuery('customer_login'));

        self::assertArrayHasKey('hryvinskyiInvisibleCaptchaConfig', $response);
        $config = $response['hryvinskyiInvisibleCaptchaConfig'];

        self::assertTrue($config['is_enabled']);
        self::assertSame('turnstile', $config['provider']);
        self::assertSame('test_site_key_turnstile', $config['site_key']);
        self::assertSame('cf-turnstile-response', $config['response_param']);
        self::assertFalse($config['is_score_based']);
    }

    #[
        ConfigFixture('hryvinskyi_invisible_captcha/general/enabled', 1),
        ConfigFixture('hryvinskyi_invisible_captcha/general/active_provider', 'turnstile'),
        ConfigFixture('hryvinskyi_invisible_captcha/providers/turnstile/site_key', 'test_site_key_turnstile'),
        ConfigFixture('hryvinskyi_invisible_captcha/form_protection/enabled', 1),
        ConfigFixture('hryvinskyi_invisible_captcha/form_protection/frontend/enabled', 1),
        ConfigFixture('hryvinskyi_invisible_captcha/form_protection/frontend/enabled_customer_login', 0)
    ]
    public function testIsDisabledForFormWhenFormProtectionOff(): void
    {
        $response = $this->graphQlQuery($this->getQuery('customer_login'));

        self::assertArrayHasKey('hryvinskyiInvisibleCaptchaConfig', $response);
        $config = $response['hryvinskyiInvisibleCaptchaConfig'];

        self::assertFalse($config['is_enabled']);
        // The provider client config is still exposed so the widget can render even when the
        // specific form is not gated server-side.
        self::assertSame('turnstile', $config['provider']);
        self::assertSame('test_site_key_turnstile', $config['site_key']);
    }

    #[
        ConfigFixture('hryvinskyi_invisible_captcha/general/enabled', 0)
    ]
    public function testReturnsDisabledWhenModuleOff(): void
    {
        $response = $this->graphQlQuery($this->getQuery('customer_login'));

        self::assertArrayHasKey('hryvinskyiInvisibleCaptchaConfig', $response);
        $config = $response['hryvinskyiInvisibleCaptchaConfig'];

        self::assertFalse($config['is_enabled']);
        self::assertNull($config['provider']);
        self::assertNull($config['site_key']);
    }

    /**
     * @param string $formType
     * @return string
     */
    private function getQuery(string $formType): string
    {
        return <<<QUERY
query {
    hryvinskyiInvisibleCaptchaConfig(formType: "{$formType}") {
        is_enabled
        provider
        site_key
        response_param
        is_score_based
    }
}
QUERY;
    }
}
