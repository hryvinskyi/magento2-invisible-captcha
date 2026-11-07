<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Integration\RouteProtection;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Store\Model\ScopeInterface;

/**
 * Integration coverage for the `robots_txt_blocked` route-protection rule
 * field ({@see \Hryvinskyi\InvisibleCaptcha\Model\Filter\Field\RobotsTxtBlocked}).
 *
 * With route protection enabled and the single rule `robots_txt_blocked eq 1`,
 * a request whose URL the Search Engine Robots custom instructions disallow is
 * replaced with the inline challenge (403), while URLs robots.txt permits —
 * including via a more specific Allow rule — render normally.
 *
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class RobotsTxtRuleTest extends RouteGateTestCase
{
    private const XML_CUSTOM_INSTRUCTIONS = 'design/search_engine_robots/custom_instructions';

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        // A physical pub/robots.txt outranks the config this test drives.
        $pubDirectory = $this->service(Filesystem::class)->getDirectoryRead(DirectoryList::PUB);
        if ($pubDirectory->isFile('robots.txt')) {
            $this->markTestSkipped('A physical pub/robots.txt overrides the config-driven robots.txt.');
        }
    }

    /**
     * A URL disallowed for every crawler is served the inline challenge.
     */
    public function testDisallowedUrlIsChallenged(): void
    {
        $this->enableRouteGate('robots_txt_blocked', 'eq', '1');
        $this->setRobotsInstructions("User-agent: *\nDisallow: /cms/");

        $this->dispatch(self::TARGET_ROUTE);

        $response = $this->httpResponse();
        self::assertSame(403, $response->getHttpResponseCode());
        self::assertStringContainsString('class="band"', $this->responseBody());
    }

    /**
     * A URL robots.txt does not disallow renders normally.
     */
    public function testAllowedUrlRendersNormally(): void
    {
        $this->enableRouteGate('robots_txt_blocked', 'eq', '1');
        $this->setRobotsInstructions("User-agent: *\nDisallow: /checkout/");

        $this->dispatch(self::TARGET_ROUTE);

        self::assertSame(200, $this->httpResponse()->getHttpResponseCode());
    }

    /**
     * A more specific Allow rule wins over a broader Disallow (RFC 9309).
     */
    public function testMoreSpecificAllowRuleBypassesTheChallenge(): void
    {
        $this->enableRouteGate('robots_txt_blocked', 'eq', '1');
        $this->setRobotsInstructions("User-agent: *\nDisallow: /cms/\nAllow: /cms/index");

        $this->dispatch(self::TARGET_ROUTE);

        self::assertSame(200, $this->httpResponse()->getHttpResponseCode());
    }

    /**
     * Without any robots.txt content the rule never fires (fail-safe).
     */
    public function testEmptyRobotsTxtNeverChallenges(): void
    {
        $this->enableRouteGate('robots_txt_blocked', 'eq', '1');
        $this->setRobotsInstructions('');

        $this->dispatch(self::TARGET_ROUTE);

        self::assertSame(200, $this->httpResponse()->getHttpResponseCode());
    }

    /**
     * Store the Search Engine Robots custom instructions, which the module reads
     * at website scope.
     *
     * @param string $instructions
     */
    private function setRobotsInstructions(string $instructions): void
    {
        $this->setConfig(self::XML_CUSTOM_INSTRUCTIONS, $instructions, ScopeInterface::SCOPE_WEBSITE);
    }
}
