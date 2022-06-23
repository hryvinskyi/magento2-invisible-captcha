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

/**
 * Class Debug
 */
class Debug extends Base
{
    /**
     * @var General
     */
    private $config;

    /**
     * Debug constructor.
     *
     * @param DriverInterface $filesystem
     * @param General $config
     * @param null $filePath
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
     * @inheritDoc
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function write(array $record): void
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
