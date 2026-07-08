<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Debug;
use Magento\Framework\Filesystem\DriverInterface;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DebugTest extends TestCase
{
    /** @var DriverInterface|MockObject */
    private $filesystem;
    /** @var ConfigInterface|MockObject */
    private $config;

    protected function setUp(): void
    {
        $this->filesystem = $this->createMock(DriverInterface::class);
        $this->config = $this->createMock(ConfigInterface::class);
    }

    public function testWriteIsSkippedWhenDebugDisabled(): void
    {
        $this->config->method('isDebug')->willReturn(false);
        // The handler must not touch the filesystem when debug is off.
        $this->filesystem->expects(self::never())->method('getParentDirectory');
        $this->filesystem->expects(self::never())->method('isDirectory');
        $this->filesystem->expects(self::never())->method('createDirectory');

        // Explicit filePath: with null the Base handler falls back to the BP
        // constant, which only Magento's app bootstrap defines. No stream is
        // ever opened — write() returns before any I/O when debug is off.
        $handler = new Debug($this->filesystem, $this->config, sys_get_temp_dir(), '/var/log/invisible_captcha.log');
        $handler->write($this->makeRecord());

        $this->addToAssertionCount(1);
    }

    private function makeRecord(): LogRecord
    {
        return new LogRecord(
            new \DateTimeImmutable('2026-01-01T00:00:00+00:00'),
            'invisible_captcha',
            Level::Info,
            'test message'
        );
    }
}
