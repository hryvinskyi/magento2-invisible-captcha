<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Setup\Patch\Data;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

/**
 * Relocates any previously uploaded MaxMind `.mmdb` database from the old
 * web-accessible `pub/media/hryvinskyi_invisible_captcha/geoip/` location to the
 * node-local `var/hryvinskyi_invisible_captcha/geoip/`.
 *
 * The stored config value is a bare filename, so no config change is needed —
 * only the physical file moves. The migration is best-effort: a missing source
 * directory is a no-op, each file is copied then removed under its own guard,
 * and no failure is ever allowed to escape {@see apply()} and abort setup.
 */
final class MoveMaxmindDbToVar implements DataPatchInterface
{
    /**
     * Directory (relative to both media and var roots) holding the database.
     */
    private const UPLOAD_DIR = 'hryvinskyi_invisible_captcha/geoip';

    /**
     * @param Filesystem $filesystem
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function apply(): self
    {
        try {
            $media = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            if (!$media->isDirectory(self::UPLOAD_DIR)) {
                return $this;
            }

            $var = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $var->create(self::UPLOAD_DIR);
            $driver = $media->getDriver();

            foreach ($media->read(self::UPLOAD_DIR) as $path) {
                try {
                    if (!$media->isFile($path)) {
                        continue;
                    }

                    $target = self::UPLOAD_DIR . '/' . basename($path);
                    $driver->copy($media->getAbsolutePath($path), $var->getAbsolutePath($target));
                    $media->delete($path);
                } catch (\Throwable $e) {
                    // Best-effort per-file move; leave the source in place on failure.
                    $this->logger->warning(
                        'Invisible Captcha: failed to move MaxMind database file to var/.',
                        ['path' => $path, 'exception' => $e]
                    );
                }
            }
        } catch (\Throwable $e) {
            // Never abort setup over a file relocation.
            $this->logger->warning(
                'Invisible Captcha: MaxMind database migration to var/ was skipped.',
                ['exception' => $e]
            );
        }

        return $this;
    }
}
