<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Integration\RouteProtection;

use Hryvinskyi\InvisibleCaptcha\ViewModel\SilentVerification;

/**
 * Background verification on a page the rules do not protect, rendered through
 * the real layout, view model and asset renderer.
 *
 * Route protection is on with Turnstile and a rule that matches only category
 * pages, so the home page renders normally. An unverified visitor's home page
 * carries the configuration block and the deferred script; a verified visitor's
 * does not, and neither does any page while the setting is off.
 *
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class SilentVerificationTest extends RouteGateTestCase
{
    private const SCRIPT_PATH = 'Hryvinskyi_InvisibleCaptcha/js/silent-verification.js';

    /**
     * An unverified visitor's unprotected page carries the background verification.
     */
    public function testUnverifiedVisitorGetsTheBackgroundVerification(): void
    {
        $this->enableRouteGate('action_name', 'eq', 'catalog_category_view');
        $this->setConfig('hryvinskyi_invisible_captcha/route_protection/silent_verification', '1');

        $this->dispatch(self::TARGET_ROUTE);

        $this->assertNoChallenge();
        $config = $this->silentConfig();
        self::assertSame('widget', $config['mode']);
        self::assertSame('turnstile', $config['provider']);
        self::assertSame('test_site_key', $config['siteKey']);
        self::assertSame('cf-turnstile-response', $config['responseParam']);
        self::assertIsString($config['verifyUrl']);
        self::assertStringEndsWith('/invisiblecaptcha/verify', $config['verifyUrl']);
        self::assertMatchesRegularExpression(
            '#<script[^>]*\bdefer\b[^>]*src="[^"]*' . preg_quote(self::SCRIPT_PATH, '#') . '"'
            . '|<script[^>]*src="[^"]*' . preg_quote(self::SCRIPT_PATH, '#') . '"[^>]*\bdefer\b#',
            $this->responseBody(),
            'the script is loaded external and deferred'
        );
    }

    /**
     * A verified visitor is not verified again.
     */
    public function testVerifiedVisitorGetsNoBackgroundVerification(): void
    {
        $this->enableRouteGate('action_name', 'eq', 'catalog_category_view');
        $this->setConfig('hryvinskyi_invisible_captcha/route_protection/silent_verification', '1');
        $this->issueVerifiedCookie();

        $this->dispatch(self::TARGET_ROUTE);

        $this->assertNoChallenge();
        $this->assertNoBackgroundVerification();
    }

    /**
     * The setting is off by default.
     */
    public function testOffByDefault(): void
    {
        $this->enableRouteGate('action_name', 'eq', 'catalog_category_view');

        $this->dispatch(self::TARGET_ROUTE);

        $this->assertNoChallenge();
        $this->assertNoBackgroundVerification();
    }

    /**
     * Without route protection there is nothing to verify for.
     */
    public function testNothingWithoutRouteProtection(): void
    {
        $this->enableRouteGate('action_name', 'eq', 'catalog_category_view');
        $this->setConfig('hryvinskyi_invisible_captcha/route_protection/silent_verification', '1');
        $this->setConfig('hryvinskyi_invisible_captcha/route_protection/enabled', '0');

        $this->dispatch(self::TARGET_ROUTE);

        $this->assertNoBackgroundVerification();
    }

    /**
     * A protected page opened directly still gets the challenge page.
     */
    public function testProtectedPageOpenedDirectlyIsStillChallenged(): void
    {
        $this->enableRouteGate('action_name', 'eq', 'cms_index_index');
        $this->setConfig('hryvinskyi_invisible_captcha/route_protection/silent_verification', '1');

        $this->dispatch(self::TARGET_ROUTE);

        $this->assertChallengeServed();
    }

    /**
     * The decoded configuration block of the rendered page.
     *
     * @return array<string, mixed>
     */
    private function silentConfig(): array
    {
        $pattern = '#<script type="application/json" id="' . SilentVerification::CONFIG_ELEMENT_ID
            . '">(.*?)</script>#s';
        if (preg_match($pattern, $this->responseBody(), $match) !== 1) {
            self::fail('The page carries the background verification configuration.');
        }
        $decoded = json_decode($match[1], true);
        self::assertIsArray($decoded);

        $config = [];
        foreach ($decoded as $key => $value) {
            $config[(string)$key] = $value;
        }

        return $config;
    }

    /**
     * The page carries neither the configuration block nor the script.
     */
    private function assertNoBackgroundVerification(): void
    {
        $body = $this->responseBody();
        self::assertStringNotContainsString(SilentVerification::CONFIG_ELEMENT_ID, $body);
        self::assertStringNotContainsString(self::SCRIPT_PATH, $body);
    }
}
