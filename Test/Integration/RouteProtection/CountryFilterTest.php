<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Integration\RouteProtection;

use Hryvinskyi\InvisibleCaptcha\Model\CookieManager;

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
class CountryFilterTest extends RouteGateTestCase
{
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
        $this->enableCountryRule('eq', 'UA');
        $this->serverParameters()->set('HTTP_CF_IPCOUNTRY', 'UA');

        $this->dispatch(self::TARGET_ROUTE);

        $this->assertChallengeServed();
    }

    /**
     * A resolved country that does not match the rule renders the page (200).
     */
    public function testDifferentCountryRendersPage(): void
    {
        $this->enableCountryRule('eq', 'UA');
        $this->serverParameters()->set('HTTP_CF_IPCOUNTRY', 'DE');

        $this->dispatch(self::TARGET_ROUTE);

        $this->assertNoChallenge();
    }

    /**
     * An absent header (unknown country → empty string) does not match a
     * positive rule, so the page renders (200).
     */
    public function testUnknownCountryDoesNotMatchPositiveRule(): void
    {
        $this->enableCountryRule('eq', 'UA');

        $this->dispatch(self::TARGET_ROUTE);

        $this->assertNoChallenge();
    }

    /**
     * An absent header (unknown country → empty string) matches a negative
     * operator, so `not in list` challenges traffic whose country is unknown.
     */
    public function testUnknownCountryMatchesNegativeRule(): void
    {
        $this->enableCountryRule('not_in', 'UA US');

        $this->dispatch(self::TARGET_ROUTE);

        $this->assertChallengeServed();
    }

    /**
     * Enable route protection with a single country condition and select the
     * Cloudflare geolocation source.
     *
     * @param string $operator
     * @param string $value
     */
    private function enableCountryRule(string $operator, string $value): void
    {
        $this->enableRouteGate('country', $operator, $value);
        $this->setConfig('hryvinskyi_invisible_captcha/geolocation/source', 'cloudflare');
    }
}
