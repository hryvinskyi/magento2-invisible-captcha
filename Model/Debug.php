<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base;
use Monolog\LogRecord;

/**
 * Config-gated log handler: writes to var/log/invisible_captcha.log only when
 * debug mode is enabled, creating the log directory on demand.
 */
class Debug extends Base
{
    /**
     * @param DriverInterface $filesystem
     * @param ConfigInterface $config
     * @param string|null $filePath
     * @param string $fileName
     * @throws \Exception
     */
    public function __construct(
        DriverInterface $filesystem,
        private readonly ConfigInterface $config,
        $filePath = null,
        $fileName = '/var/log/invisible_captcha.log'
    ) {
        parent::__construct($filesystem, $filePath, $fileName);
    }

    /**
     * @inheritDoc
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function write(LogRecord $record): void
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
