<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Geo\Source\Maxmind;

use MaxMind\Db\Reader;

/**
 * Thin construction seam around {@see Reader} so the MaxMind source can be unit
 * tested with a mocked reader instead of a real `.mmdb` file on disk.
 */
class ReaderFactory
{
    /**
     * Open a MaxMind database reader for the given absolute file path.
     *
     * @param string $absolutePath Absolute path to a `.mmdb` file.
     * @return Reader
     * @throws \MaxMind\Db\Reader\InvalidDatabaseException When the file is not a valid MaxMind database.
     * @throws \InvalidArgumentException When the file does not exist or is not readable.
     */
    public function create(string $absolutePath): Reader
    {
        return new Reader($absolutePath);
    }
}
