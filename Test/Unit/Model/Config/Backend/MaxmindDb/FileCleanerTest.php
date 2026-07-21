<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Config\Backend\MaxmindDb;

use Hryvinskyi\InvisibleCaptcha\Model\Config\Backend\MaxmindDb\FileCleaner;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FileCleanerTest extends TestCase
{
    private const DIR = 'hryvinskyi_invisible_captcha/geoip';

    /** @var Filesystem&MockObject */
    private Filesystem $filesystem;
    /** @var WriteInterface&MockObject */
    private WriteInterface $write;
    private FileCleaner $cleaner;

    protected function setUp(): void
    {
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->write = $this->createMock(WriteInterface::class);
        $this->cleaner = new FileCleaner($this->filesystem);
    }

    public function testDeletesExistingFile(): void
    {
        $this->filesystem->method('getDirectoryWrite')
            ->with(DirectoryList::MEDIA)
            ->willReturn($this->write);
        $this->write->method('isFile')->with(self::DIR . '/GeoLite2.mmdb')->willReturn(true);
        $this->write->expects($this->once())->method('delete')->with(self::DIR . '/GeoLite2.mmdb');

        $this->cleaner->delete('GeoLite2.mmdb');
    }

    public function testMissingFileIsNotDeleted(): void
    {
        $this->filesystem->method('getDirectoryWrite')->willReturn($this->write);
        $this->write->method('isFile')->with(self::DIR . '/GeoLite2.mmdb')->willReturn(false);
        $this->write->expects($this->never())->method('delete');

        $this->cleaner->delete('GeoLite2.mmdb');
    }

    public function testTraversalInputIsReducedToBasename(): void
    {
        $this->filesystem->method('getDirectoryWrite')->willReturn($this->write);
        $this->write->method('isFile')->with(self::DIR . '/foo.mmdb')->willReturn(true);
        $this->write->expects($this->once())->method('delete')->with(self::DIR . '/foo.mmdb');

        $this->cleaner->delete('../foo.mmdb');
    }

    public function testEmptyInputLeavesFilesystemUntouched(): void
    {
        $this->filesystem->expects($this->never())->method('getDirectoryWrite');

        $this->cleaner->delete('');
    }

    public function testIsFileFailureIsSwallowed(): void
    {
        $this->filesystem->method('getDirectoryWrite')->willReturn($this->write);
        $this->write->method('isFile')->willThrowException(new \RuntimeException('boom'));
        $this->write->expects($this->never())->method('delete');

        $this->cleaner->delete('GeoLite2.mmdb');

        // No exception escaped.
        $this->addToAssertionCount(1);
    }
}
