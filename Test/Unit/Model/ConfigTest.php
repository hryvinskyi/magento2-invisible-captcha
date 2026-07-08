<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /** @var ScopeConfigInterface&MockObject */
    private ScopeConfigInterface $scopeConfig;
    /** @var Json&MockObject */
    private Json $serializer;
    private Config $config;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->serializer = $this->createMock(Json::class);

        $this->config = new Config($this->scopeConfig, $this->serializer);
    }

    /**
     * @return array<string, array{0:string,1:string}>
     */
    public static function flagProvider(): array
    {
        return [
            'isEnabled' => ['isEnabled', 'hryvinskyi_invisible_captcha/general/enabled'],
            'isDebug' => ['isDebug', 'hryvinskyi_invisible_captcha/general/debug'],
            'isLazyLoad' => ['isLazyLoad', 'hryvinskyi_invisible_captcha/general/use_lazy_load'],
            'isDisableSubmitForm' => ['isDisableSubmitForm', 'hryvinskyi_invisible_captcha/general/disable_submit_form'],
            'isFormProtectionEnabled' => ['isFormProtectionEnabled', 'hryvinskyi_invisible_captcha/form_protection/enabled'],
            'isRouteProtectionEnabled' => ['isRouteProtectionEnabled', 'hryvinskyi_invisible_captcha/route_protection/enabled'],
            'isRouteFallbackEnabled' => ['isRouteFallbackEnabled', 'hryvinskyi_invisible_captcha/route_protection/fallback_enabled'],
        ];
    }

    #[DataProvider('flagProvider')]
    public function testBooleanFlagsDelegateToIsSetFlag(string $method, string $path): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with($path, ScopeInterface::SCOPE_STORE, null)
            ->willReturn(true);

        $this->assertTrue($this->config->{$method}());
    }

    public function testGetActiveProviderReturnsDefaultWhenUnset(): void
    {
        $this->scopeConfig->method('getValue')->willReturn(null);

        $this->assertSame(ProviderInterface::CODE_RECAPTCHA_V3, $this->config->getActiveProvider());
    }

    public function testGetActiveProviderReturnsConfiguredValue(): void
    {
        $this->scopeConfig->method('getValue')
            ->with('hryvinskyi_invisible_captcha/general/active_provider', ScopeInterface::SCOPE_STORE, null)
            ->willReturn(ProviderInterface::CODE_TURNSTILE);

        $this->assertSame(ProviderInterface::CODE_TURNSTILE, $this->config->getActiveProvider());
    }

    /**
     * @return array<string, array{0:string,1:string}>
     */
    public static function formEnabledPathProvider(): array
    {
        return [
            'customer_login' => [
                ConfigInterface::FORM_CUSTOMER_LOGIN,
                'hryvinskyi_invisible_captcha/form_protection/frontend/enabled_customer_login',
            ],
            'contact' => [
                ConfigInterface::FORM_CONTACT,
                'hryvinskyi_invisible_captcha/form_protection/frontend/enabled_contact',
            ],
            'admin_login_maps_to_backend' => [
                ConfigInterface::FORM_ADMIN_LOGIN,
                'hryvinskyi_invisible_captcha/form_protection/backend/enabled_login',
            ],
            'admin_forgot_maps_to_backend' => [
                ConfigInterface::FORM_ADMIN_FORGOT,
                'hryvinskyi_invisible_captcha/form_protection/backend/enabled_forgot',
            ],
        ];
    }

    #[DataProvider('formEnabledPathProvider')]
    public function testIsFormEnabledMapsFormToPath(string $form, string $path): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with($path, ScopeInterface::SCOPE_STORE, null)
            ->willReturn(true);

        $this->assertTrue($this->config->isFormEnabled($form));
    }

    public function testIsFormEnabledReturnsFalseForUnknownForm(): void
    {
        $this->scopeConfig->expects($this->never())->method('isSetFlag');

        $this->assertFalse($this->config->isFormEnabled('does_not_exist'));
    }

    /**
     * @return array<string, array{0:string,1:string}>
     */
    public static function formAreaProvider(): array
    {
        return [
            'adminhtml' => [
                ConfigInterface::AREA_ADMINHTML,
                'hryvinskyi_invisible_captcha/form_protection/backend/enabled',
            ],
            'frontend' => [
                ConfigInterface::AREA_FRONTEND,
                'hryvinskyi_invisible_captcha/form_protection/frontend/enabled',
            ],
        ];
    }

    #[DataProvider('formAreaProvider')]
    public function testIsFormAreaEnabledMapsArea(string $area, string $path): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with($path, ScopeInterface::SCOPE_STORE, null)
            ->willReturn(true);

        $this->assertTrue($this->config->isFormAreaEnabled($area));
    }

    public function testGetFormScoreThresholdReturnsConfiguredValue(): void
    {
        $this->scopeConfig->method('getValue')
            ->with(
                'hryvinskyi_invisible_captcha/form_protection/frontend/score_threshold_customer_login',
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn('0.7');

        $this->assertSame(0.7, $this->config->getFormScoreThreshold(ConfigInterface::FORM_CUSTOMER_LOGIN));
    }

    public function testGetFormScoreThresholdReadsBackendPathForAdminForm(): void
    {
        $this->scopeConfig->method('getValue')
            ->with(
                'hryvinskyi_invisible_captcha/form_protection/backend/score_threshold_login',
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn('0.9');

        $this->assertSame(0.9, $this->config->getFormScoreThreshold(ConfigInterface::FORM_ADMIN_LOGIN));
    }

    public function testGetFormScoreThresholdDefaultsWhenNotPositive(): void
    {
        $this->scopeConfig->method('getValue')->willReturn('0');

        $this->assertSame(0.5, $this->config->getFormScoreThreshold(ConfigInterface::FORM_CONTACT));
    }

    public function testGetFormScoreThresholdDefaultsForUnknownForm(): void
    {
        $this->scopeConfig->expects($this->never())->method('getValue');

        $this->assertSame(0.5, $this->config->getFormScoreThreshold('does_not_exist'));
    }

    public function testGetProtectionRulesConfigReturnsEmptyForBlankValue(): void
    {
        $this->scopeConfig->method('getValue')->willReturn('   ');
        $this->serializer->expects($this->never())->method('unserialize');

        $this->assertSame([], $this->config->getProtectionRulesConfig());
    }

    public function testGetProtectionRulesConfigDecodesArray(): void
    {
        $raw = '[{"field":"client_ip"}]';
        $decoded = [['field' => 'client_ip']];

        $this->scopeConfig->method('getValue')
            ->with('hryvinskyi_invisible_captcha/route_protection/rules', ScopeInterface::SCOPE_STORE, null)
            ->willReturn($raw);
        $this->serializer->method('unserialize')->with($raw)->willReturn($decoded);

        $this->assertSame($decoded, $this->config->getProtectionRulesConfig());
    }

    public function testGetProtectionRulesConfigReturnsEmptyOnInvalidJson(): void
    {
        $this->scopeConfig->method('getValue')->willReturn('{not-json');
        $this->serializer->method('unserialize')
            ->willThrowException(new \InvalidArgumentException('Unable to unserialize value.'));

        $this->assertSame([], $this->config->getProtectionRulesConfig());
    }

    public function testGetProtectionRulesConfigReturnsEmptyWhenDecodedIsNotArray(): void
    {
        $this->scopeConfig->method('getValue')->willReturn('"scalar"');
        $this->serializer->method('unserialize')->willReturn('scalar');

        $this->assertSame([], $this->config->getProtectionRulesConfig());
    }

    public function testGetExcludedIpsParsesAndTrimsNewlineList(): void
    {
        $this->scopeConfig->method('getValue')
            ->with('hryvinskyi_invisible_captcha/route_protection/excluded_ips', ScopeInterface::SCOPE_STORE, null)
            ->willReturn("1.1.1.1\n  2.2.2.2  \n\n3.3.3.3");

        $this->assertSame(['1.1.1.1', '2.2.2.2', '3.3.3.3'], $this->config->getExcludedIps());
    }

    public function testGetExcludedUserAgentsParsesList(): void
    {
        $this->scopeConfig->method('getValue')
            ->with(
                'hryvinskyi_invisible_captcha/route_protection/excluded_user_agents',
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn("Googlebot\nbingbot");

        $this->assertSame(['Googlebot', 'bingbot'], $this->config->getExcludedUserAgents());
    }

    public function testGetExcludedUserAgentsReturnsEmptyForBlankValue(): void
    {
        $this->scopeConfig->method('getValue')->willReturn('');

        $this->assertSame([], $this->config->getExcludedUserAgents());
    }

    public function testGetCookieLifetimeReturnsDefaultWhenNotPositive(): void
    {
        $this->scopeConfig->method('getValue')->willReturn(null);

        $this->assertSame(14400, $this->config->getCookieLifetime());
    }

    public function testGetCookieLifetimeReturnsConfiguredValue(): void
    {
        $this->scopeConfig->method('getValue')->willReturn('3600');

        $this->assertSame(3600, $this->config->getCookieLifetime());
    }

    public function testGetRouteFallbackDelayReturnsDefaultWhenNotPositive(): void
    {
        $this->scopeConfig->method('getValue')->willReturn('0');

        $this->assertSame(10, $this->config->getRouteFallbackDelay());
    }

    public function testGetHttpTimeoutReturnsDefaultWhenNotPositive(): void
    {
        $this->scopeConfig->method('getValue')->willReturn('0');

        $this->assertSame(2.0, $this->config->getHttpTimeout());
    }

    public function testGetHttpTimeoutReturnsConfiguredValue(): void
    {
        $this->scopeConfig->method('getValue')->willReturn('5.5');

        $this->assertSame(5.5, $this->config->getHttpTimeout());
    }

    public function testGetSupportEmailReturnsSupportIdentWhenSet(): void
    {
        $this->scopeConfig->method('getValue')->willReturnMap([
            ['trans_email/ident_support/email', ScopeInterface::SCOPE_STORE, null, 'support@example.com'],
            ['trans_email/ident_general/email', ScopeInterface::SCOPE_STORE, null, 'general@example.com'],
        ]);

        $this->assertSame('support@example.com', $this->config->getSupportEmail());
    }

    public function testGetSupportEmailFallsBackToGeneralIdent(): void
    {
        $this->scopeConfig->method('getValue')->willReturnMap([
            ['trans_email/ident_support/email', ScopeInterface::SCOPE_STORE, null, null],
            ['trans_email/ident_general/email', ScopeInterface::SCOPE_STORE, null, 'general@example.com'],
        ]);

        $this->assertSame('general@example.com', $this->config->getSupportEmail());
    }
}
