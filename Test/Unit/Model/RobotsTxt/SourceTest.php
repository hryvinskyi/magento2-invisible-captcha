<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\RobotsTxt;

use Hryvinskyi\InvisibleCaptcha\Model\RobotsTxt\Source;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Phrase;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SourceTest extends TestCase
{
    private const CONFIG_PATH = 'design/search_engine_robots/custom_instructions';

    /** @var Filesystem&MockObject */
    private Filesystem $filesystem;
    /** @var ReadInterface&MockObject */
    private ReadInterface $pubDirectory;
    /** @var ScopeConfigInterface&MockObject */
    private ScopeConfigInterface $scopeConfig;
    /** @var StoreManagerInterface&MockObject */
    private StoreManagerInterface $storeManager;
    private Source $source;

    protected function setUp(): void
    {
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->pubDirectory = $this->createMock(ReadInterface::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);

        $this->filesystem->method('getDirectoryRead')
            ->with(DirectoryList::PUB)
            ->willReturn($this->pubDirectory);

        $website = $this->createMock(WebsiteInterface::class);
        $website->method('getId')->willReturn(1);
        $this->storeManager->method('getWebsite')->willReturn($website);

        $this->source = new Source($this->filesystem, $this->scopeConfig, $this->storeManager);
    }

    public function testPhysicalFileWinsOverConfig(): void
    {
        $this->pubDirectory->method('isFile')->with('robots.txt')->willReturn(true);
        $this->pubDirectory->method('readFile')->with('robots.txt')->willReturn("User-agent: *\nDisallow: /x");
        $this->scopeConfig->expects($this->never())->method('getValue');

        $this->assertSame("User-agent: *\nDisallow: /x", $this->source->getContent());
    }

    public function testFallsBackToWebsiteScopedConfigWhenFileIsAbsent(): void
    {
        $this->pubDirectory->method('isFile')->with('robots.txt')->willReturn(false);
        $this->pubDirectory->expects($this->never())->method('readFile');
        $this->scopeConfig->method('getValue')
            ->with(self::CONFIG_PATH, ScopeInterface::SCOPE_WEBSITE)
            ->willReturn("User-agent: *\nDisallow: /cfg");

        $this->assertSame("User-agent: *\nDisallow: /cfg", $this->source->getContent());
    }

    public function testFallsBackToConfigWhenFileReadFails(): void
    {
        $this->pubDirectory->method('isFile')->with('robots.txt')->willReturn(true);
        $this->pubDirectory->method('readFile')
            ->willThrowException(new FileSystemException(new Phrase('unreadable')));
        $this->scopeConfig->method('getValue')
            ->with(self::CONFIG_PATH, ScopeInterface::SCOPE_WEBSITE)
            ->willReturn('config content');

        $this->assertSame('config content', $this->source->getContent());
    }

    public function testEmptyWhenNeitherFileNorConfigExists(): void
    {
        $this->pubDirectory->method('isFile')->willReturn(false);
        $this->scopeConfig->method('getValue')->willReturn(null);

        $this->assertSame('', $this->source->getContent());
    }

    public function testContentIsMemoizedPerWebsite(): void
    {
        $this->pubDirectory->method('isFile')->willReturn(true);
        $this->pubDirectory->expects($this->once())->method('readFile')->willReturn('file content');

        $this->assertSame('file content', $this->source->getContent());
        $this->assertSame('file content', $this->source->getContent());
    }

    public function testUnresolvableWebsiteStillReturnsContent(): void
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->method('getDirectoryRead')->willReturn($this->pubDirectory);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getWebsite')
            ->willThrowException(new LocalizedException(new Phrase('no website')));
        $this->pubDirectory->method('isFile')->willReturn(false);
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturn('config content');

        $source = new Source($filesystem, $scopeConfig, $storeManager);

        $this->assertSame('config content', $source->getContent());
    }
}
