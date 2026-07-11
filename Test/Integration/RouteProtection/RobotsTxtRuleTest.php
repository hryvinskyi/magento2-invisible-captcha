<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Integration\RouteProtection;

use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\TestFramework\App\MutableScopeConfig;
use Magento\TestFramework\TestCase\AbstractController;

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
class RobotsTxtRuleTest extends AbstractController
{
    private const SECRET = 'test_secret_key';
    private const TARGET_ROUTE = 'cms/index/index';
    private const XML_CUSTOM_INSTRUCTIONS = 'design/search_engine_robots/custom_instructions';

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

        // A physical pub/robots.txt outranks the config this test drives.
        $pubDirectory = $this->_objectManager->get(Filesystem::class)->getDirectoryRead(DirectoryList::PUB);
        if ($pubDirectory->isFile('robots.txt')) {
            $this->markTestSkipped('A physical pub/robots.txt overrides the config-driven robots.txt.');
        }
    }

    /**
     * A URL disallowed for every crawler is served the inline challenge.
     */
    public function testDisallowedUrlIsChallenged(): void
    {
        $this->enableRouteProtectionWithRobotsRule();
        $this->setConfig(self::XML_CUSTOM_INSTRUCTIONS, "User-agent: *\nDisallow: /cms/");

        $this->dispatch(self::TARGET_ROUTE);

        $response = $this->getResponse();
        self::assertSame(403, $response->getHttpResponseCode());
        self::assertStringContainsString('class="band"', (string)$response->getBody());
    }

    /**
     * A URL robots.txt does not disallow renders normally.
     */
    public function testAllowedUrlRendersNormally(): void
    {
        $this->enableRouteProtectionWithRobotsRule();
        $this->setConfig(self::XML_CUSTOM_INSTRUCTIONS, "User-agent: *\nDisallow: /checkout/");

        $this->dispatch(self::TARGET_ROUTE);

        self::assertSame(200, $this->getResponse()->getHttpResponseCode());
    }

    /**
     * A more specific Allow rule wins over a broader Disallow (RFC 9309).
     */
    public function testMoreSpecificAllowRuleBypassesTheChallenge(): void
    {
        $this->enableRouteProtectionWithRobotsRule();
        $this->setConfig(
            self::XML_CUSTOM_INSTRUCTIONS,
            "User-agent: *\nDisallow: /cms/\nAllow: /cms/index"
        );

        $this->dispatch(self::TARGET_ROUTE);

        self::assertSame(200, $this->getResponse()->getHttpResponseCode());
    }

    /**
     * Without any robots.txt content the rule never fires (fail-safe).
     */
    public function testEmptyRobotsTxtNeverChallenges(): void
    {
        $this->enableRouteProtectionWithRobotsRule();
        $this->setConfig(self::XML_CUSTOM_INSTRUCTIONS, '');

        $this->dispatch(self::TARGET_ROUTE);

        self::assertSame(200, $this->getResponse()->getHttpResponseCode());
    }

    /**
     * Enable the module, configure Turnstile as the route-gate provider and add
     * the single rule `robots_txt_blocked eq 1`.
     */
    private function enableRouteProtectionWithRobotsRule(): void
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
                    'field' => 'robots_txt_blocked',
                    'operator' => 'eq',
                    'value' => '1',
                ],
            ])
        );
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
