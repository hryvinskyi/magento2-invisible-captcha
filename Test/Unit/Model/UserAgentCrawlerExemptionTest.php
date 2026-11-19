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
use Hryvinskyi\InvisibleCaptcha\Model\PathPatternMatcher;
use Hryvinskyi\InvisibleCaptcha\Model\UserAgentCrawlerExemption;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\RequestInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserAgentCrawlerExemptionTest extends TestCase
{
    /** @var ConfigInterface&MockObject */
    private ConfigInterface $config;
    /** @var HttpRequest&MockObject */
    private HttpRequest $request;
    private UserAgentCrawlerExemption $exemption;

    protected function setUp(): void
    {
        $this->config = $this->createMock(ConfigInterface::class);
        $this->request = $this->createMock(HttpRequest::class);
        $this->exemption = new UserAgentCrawlerExemption(
            new ExclusionPolicy($this->config, new PathPatternMatcher()),
            $this->request
        );
    }

    public function testUserAgentMatchingAnExcludedSubstringIsExempt(): void
    {
        $this->config->method('getExcludedUserAgents')->willReturn(['Googlebot']);
        $this->request->method('getHeader')->with('User-Agent')
            ->willReturn('Mozilla/5.0 (compatible; Googlebot/2.1)');

        $this->assertTrue($this->exemption->isExempt());
    }

    public function testSubstringIsComparedWithoutRegardToCase(): void
    {
        $this->config->method('getExcludedUserAgents')->willReturn(['googlebot']);
        $this->request->method('getHeader')->willReturn('Mozilla/5.0 (compatible; GoogleBot/2.1)');

        $this->assertTrue($this->exemption->isExempt());
    }

    public function testUserAgentMatchingNoSubstringIsNotExempt(): void
    {
        $this->config->method('getExcludedUserAgents')->willReturn(['Googlebot']);
        $this->request->method('getHeader')->willReturn('Mozilla/5.0 (Macintosh)');

        $this->assertFalse($this->exemption->isExempt());
    }

    public function testEmptyUserAgentIsNotExempt(): void
    {
        $this->config->method('getExcludedUserAgents')->willReturn(['Googlebot']);
        $this->request->method('getHeader')->willReturn('');

        $this->assertFalse($this->exemption->isExempt());
    }

    public function testMissingUserAgentHeaderIsNotExempt(): void
    {
        $this->config->method('getExcludedUserAgents')->willReturn(['Googlebot']);
        $this->request->method('getHeader')->willReturn(false);

        $this->assertFalse($this->exemption->isExempt());
    }

    public function testRequestThatIsNotAnHttpRequestIsNeverExempt(): void
    {
        $this->config->method('getExcludedUserAgents')->willReturn(['Googlebot']);
        $exemption = new UserAgentCrawlerExemption(
            new ExclusionPolicy($this->config, new PathPatternMatcher()),
            $this->createMock(RequestInterface::class)
        );

        $this->assertFalse($exemption->isExempt());
    }
}
