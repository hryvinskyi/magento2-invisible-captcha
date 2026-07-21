<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Geo\Source;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Geo\Source\Maxmind\ReaderFactory;
use Hryvinskyi\InvisibleCaptcha\Model\Geo\Source\MaxmindDatabase;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Phrase;
use MaxMind\Db\Reader;
use MaxMind\Db\Reader\InvalidDatabaseException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MaxmindDatabaseTest extends TestCase
{
    private const RELATIVE = 'hryvinskyi_invisible_captcha/geoip/GeoLite2-Country.mmdb';

    /** @var ConfigInterface&MockObject */
    private ConfigInterface $config;

    /** @var Filesystem&MockObject */
    private Filesystem $filesystem;

    /** @var ReadInterface&MockObject */
    private ReadInterface $mediaDir;

    /** @var ReaderFactory&MockObject */
    private ReaderFactory $readerFactory;

    /** @var LoggerInterface&MockObject */
    private LoggerInterface $logger;

    private MaxmindDatabase $source;

    protected function setUp(): void
    {
        if (!class_exists(Reader::class)) {
            $this->markTestSkipped('maxmind-db/reader not installed');
        }

        $this->config = $this->createMock(ConfigInterface::class);
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->mediaDir = $this->createMock(ReadInterface::class);
        $this->readerFactory = $this->createMock(ReaderFactory::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->filesystem->method('getDirectoryRead')
            ->with(DirectoryList::MEDIA)
            ->willReturn($this->mediaDir);

        $this->source = new MaxmindDatabase(
            $this->config,
            $this->filesystem,
            $this->readerFactory,
            $this->logger
        );
    }

    public function testMetadata(): void
    {
        $this->assertSame('maxmind', $this->source->getCode());
        $this->assertInstanceOf(Phrase::class, $this->source->getLabel());
        $this->assertSame('MaxMind database (GeoLite2 / GeoIP2)', (string)$this->source->getLabel());
    }

    public function testNotConfiguredWhenFilenameEmpty(): void
    {
        $this->config->method('getMaxmindDbFile')->willReturn('');
        $this->mediaDir->expects($this->never())->method('isFile');

        $this->assertFalse($this->source->isConfigured());
    }

    public function testNotConfiguredWhenFileMissing(): void
    {
        $this->config->method('getMaxmindDbFile')->willReturn('GeoLite2-Country.mmdb');
        $this->mediaDir->method('isFile')->with(self::RELATIVE)->willReturn(false);

        $this->assertFalse($this->source->isConfigured());
    }

    public function testConfiguredWhenFilePresent(): void
    {
        $this->config->method('getMaxmindDbFile')->willReturn('GeoLite2-Country.mmdb');
        $this->mediaDir->method('isFile')->with(self::RELATIVE)->willReturn(true);

        $this->assertTrue($this->source->isConfigured());
    }

    public function testResolveReturnsUppercaseIsoCode(): void
    {
        $this->config->method('getMaxmindDbFile')->willReturn('GeoLite2-Country.mmdb');
        $this->mediaDir->method('getAbsolutePath')->with(self::RELATIVE)->willReturn('/media/geoip.mmdb');

        $reader = $this->createMock(Reader::class);
        $reader->method('get')->with('203.0.113.7')->willReturn(['country' => ['iso_code' => 'ua']]);
        $this->readerFactory->method('create')->with('/media/geoip.mmdb')->willReturn($reader);

        $this->assertSame('UA', $this->source->resolve('203.0.113.7'));
    }

    public function testResolveReturnsNullWhenRecordHasNoIsoCode(): void
    {
        $this->config->method('getMaxmindDbFile')->willReturn('GeoLite2-Country.mmdb');
        $this->mediaDir->method('getAbsolutePath')->willReturn('/media/geoip.mmdb');

        $reader = $this->createMock(Reader::class);
        $reader->method('get')->willReturn(['country' => []]);
        $this->readerFactory->method('create')->willReturn($reader);

        $this->assertNull($this->source->resolve('203.0.113.7'));
    }

    public function testResolveReturnsNullWhenRecordIsNull(): void
    {
        $this->config->method('getMaxmindDbFile')->willReturn('GeoLite2-Country.mmdb');
        $this->mediaDir->method('getAbsolutePath')->willReturn('/media/geoip.mmdb');

        $reader = $this->createMock(Reader::class);
        $reader->method('get')->willReturn(null);
        $this->readerFactory->method('create')->willReturn($reader);

        $this->assertNull($this->source->resolve('203.0.113.7'));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidIpProvider(): array
    {
        return [
            'garbage' => ['not-an-ip'],
            'empty' => [''],
        ];
    }

    /**
     * @dataProvider invalidIpProvider
     */
    public function testResolveWithInvalidIpReturnsNullAndNeverOpensReader(string $ip): void
    {
        $this->readerFactory->expects($this->never())->method('create');
        $this->logger->expects($this->never())->method('warning');

        $this->assertNull($this->source->resolve($ip));
    }

    public function testCorruptDatabaseIsFlaggedBrokenAfterFirstFailure(): void
    {
        $this->config->method('getMaxmindDbFile')->willReturn('GeoLite2-Country.mmdb');
        $this->mediaDir->method('getAbsolutePath')->willReturn('/media/geoip.mmdb');

        $this->readerFactory->expects($this->once())
            ->method('create')
            ->willThrowException(new InvalidDatabaseException('corrupt'));
        $this->logger->expects($this->once())->method('warning');

        $this->assertNull($this->source->resolve('203.0.113.7'));
        $this->assertNull($this->source->resolve('203.0.113.8'));
    }

    public function testReaderIsOpenedOnceAcrossResolves(): void
    {
        $this->config->method('getMaxmindDbFile')->willReturn('GeoLite2-Country.mmdb');
        $this->mediaDir->method('getAbsolutePath')->willReturn('/media/geoip.mmdb');

        $reader = $this->createMock(Reader::class);
        $reader->method('get')->willReturn(['country' => ['iso_code' => 'de']]);
        $this->readerFactory->expects($this->once())->method('create')->willReturn($reader);

        $this->assertSame('DE', $this->source->resolve('203.0.113.7'));
        $this->assertSame('DE', $this->source->resolve('203.0.113.8'));
    }

    public function testResolveAppliesBasenameToStoredFilename(): void
    {
        $this->config->method('getMaxmindDbFile')->willReturn('../evil.mmdb');
        $this->mediaDir->expects($this->once())
            ->method('getAbsolutePath')
            ->with('hryvinskyi_invisible_captcha/geoip/evil.mmdb')
            ->willReturn('/media/geoip/evil.mmdb');

        $reader = $this->createMock(Reader::class);
        $reader->method('get')->willReturn(['country' => ['iso_code' => 'ua']]);
        $this->readerFactory->method('create')->willReturn($reader);

        $this->assertSame('UA', $this->source->resolve('203.0.113.7'));
    }

    public function testIsConfiguredAppliesBasenameToStoredFilename(): void
    {
        $this->config->method('getMaxmindDbFile')->willReturn('../evil.mmdb');
        $this->mediaDir->expects($this->once())
            ->method('isFile')
            ->with('hryvinskyi_invisible_captcha/geoip/evil.mmdb')
            ->willReturn(true);

        $this->assertTrue($this->source->isConfigured());
    }

    public function testIsConfiguredSwallowsFilesystemException(): void
    {
        $this->config->method('getMaxmindDbFile')->willReturn('GeoLite2-Country.mmdb');
        $this->mediaDir->method('isFile')
            ->willThrowException(new \Magento\Framework\Exception\ValidatorException(__('traversal rejected')));

        $this->assertFalse($this->source->isConfigured());
    }
}
