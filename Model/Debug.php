<?php
/**
 * Copyright (c) 2020. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model;

use Hryvinskyi\InvisibleCaptcha\Helper\Config\General;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base;
use Monolog\LogRecord;

/**
 * Log handler for the captcha's debug log; writes only while debug logging is on.
 */
class Debug extends Base
{
    private General $config;

    /**
     * @param DriverInterface $filesystem
     * @param General $config
     * @param string|null $filePath
     * @param string $fileName
     *
     * @throws \Exception
     */
    public function __construct(
        DriverInterface $filesystem,
        General $config,
        $filePath = null,
        $fileName = '/var/log/invisible_captcha.log'
    ) {
        parent::__construct($filesystem, $filePath, $fileName);

        $this->config = $config;
    }

    /**
     * Writes the record only while debug logging is on, creating the log directory first.
     *
     * The parent takes an array on Monolog 2 (Magento 2.4.6 and older) and a LogRecord on Monolog 3
     * (Magento 2.4.7 and newer). The union is a supertype of both, so this override fits either parent.
     *
     * @param array<string, mixed>|LogRecord $record
     * @return void
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function write(array|LogRecord $record): void
    {
        if ($this->config->isDebug() === false) {
            return;
        }

        $logDir = $this->filesystem->getParentDirectory($this->url);
        if (!$this->filesystem->isDirectory($logDir)) {
            $this->filesystem->createDirectory($logDir);
        }

        parent::write($record);
    }
}
