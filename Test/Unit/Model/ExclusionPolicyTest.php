<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Model\ExclusionPolicy;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ExclusionPolicyTest extends TestCase
{
    /** @var ConfigInterface&MockObject */
    private ConfigInterface $config;
    private ExclusionPolicy $policy;

    protected function setUp(): void
    {
        $this->config = $this->createMock(ConfigInterface::class);
        $this->policy = new ExclusionPolicy($this->config);
    }

    public function testEmptyIpIsNeverExcluded(): void
    {
        $this->config->expects($this->never())->method('getExcludedIps');
        $this->assertFalse($this->policy->isIpExcluded(''));
    }

    public function testIpExcludedByExactMatchOnly(): void
    {
        $this->config->method('getExcludedIps')->willReturn(['1.2.3.4', '5.6.7.8']);

        $this->assertTrue($this->policy->isIpExcluded('1.2.3.4'));
        $this->assertFalse($this->policy->isIpExcluded('1.2.3.40'));
        $this->assertFalse($this->policy->isIpExcluded('9.9.9.9'));
    }

    public function testIpNotExcludedWhenListEmpty(): void
    {
        $this->config->method('getExcludedIps')->willReturn([]);
        $this->assertFalse($this->policy->isIpExcluded('1.2.3.4'));
    }

    public function testEmptyUserAgentIsNeverExcluded(): void
    {
        $this->config->expects($this->never())->method('getExcludedUserAgents');
        $this->assertFalse($this->policy->isUserAgentExcluded(''));
    }

    public function testUserAgentExcludedByCaseInsensitiveSubstring(): void
    {
        $this->config->method('getExcludedUserAgents')->willReturn(['Googlebot', 'bingbot']);

        $this->assertTrue($this->policy->isUserAgentExcluded('Mozilla/5.0 (compatible; googlebot/2.1)'));
        $this->assertTrue($this->policy->isUserAgentExcluded('BINGBOT/2.0'));
        $this->assertFalse($this->policy->isUserAgentExcluded('Mozilla/5.0 (Macintosh)'));
    }

    public function testScopeCodeIsForwardedToConfig(): void
    {
        $this->config->expects($this->once())
            ->method('getExcludedIps')
            ->with('store_two')
            ->willReturn([]);
        $this->config->expects($this->once())
            ->method('getExcludedUserAgents')
            ->with('store_two')
            ->willReturn([]);

        $this->policy->isIpExcluded('1.2.3.4', 'store_two');
        $this->policy->isUserAgentExcluded('SomeBot', 'store_two');
    }
}
