<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Config\Backend\MaxmindDb;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

/**
 * Best-effort removal of an orphaned uploaded MaxMind database file.
 *
 * Extracted from the config backend so the unlink logic is unit-testable
 * without constructing the heavy {@see \Magento\Config\Model\Config\Backend\File}
 * subclass. Cleanup is never allowed to break a config save — every failure is
 * swallowed.
 */
final class FileCleaner
{
    /**
     * Var-relative directory the MaxMind databases are uploaded into. Kept in
     * sync (by string) with {@see \Hryvinskyi\InvisibleCaptcha\Model\Config\Backend\MaxmindDb::UPLOAD_DIR}.
     */
    private const UPLOAD_DIR = 'hryvinskyi_invisible_captcha/geoip';

    /**
     * @param Filesystem $filesystem
     */
    public function __construct(
        private readonly Filesystem $filesystem
    ) {
    }

    /**
     * Delete the given database file (bare filename) from the upload directory.
     *
     * The input is reduced to its basename to defend against path traversal;
     * an empty result is a no-op. Missing files and any I/O failure are ignored.
     *
     * @param string $filename
     * @return void
     */
    public function delete(string $filename): void
    {
        $name = basename($filename);
        if ($name === '' || $name === '.' || $name === '..') {
            return;
        }

        try {
            $write = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $path = self::UPLOAD_DIR . '/' . $name;
            if ($write->isFile($path)) {
                $write->delete($path);
            }
        } catch (\Throwable $e) {
            // Best-effort cleanup — an orphaned file is harmless, a thrown
            // exception here would abort an otherwise successful config save.
        }
    }
}
