<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Config\Backend\MaxmindDb;

/**
 * Structural validator for MaxMind `.mmdb` databases.
 *
 * The MMDB binary format ends with a metadata section introduced by the marker
 * bytes `\xab\xcd\xefMaxMind.com`. Rather than pulling in the MaxMind reader
 * library (which need not be installed for the admin to upload a file), this
 * checks only that the marker is present in the file tail — enough to reject an
 * accidental non-database upload before it is moved into place.
 */
final class MarkerValidator
{
    /**
     * Metadata-section marker that every valid MMDB file contains.
     */
    private const MARKER = "\xab\xcd\xefMaxMind.com";

    /**
     * Size of the trailing window scanned for the marker (128 KiB). The metadata
     * section lives at the very end of the file, so a bounded tail read is both
     * sufficient and cheap even for a ~70 MB City database.
     */
    private const TAIL_BYTES = 131072;

    /**
     * Whether the file at the given absolute path looks like a MaxMind database.
     *
     * Never throws — any I/O problem degrades to "not valid".
     *
     * @param string $absolutePath
     * @return bool
     */
    public function isValid(string $absolutePath): bool
    {
        if ($absolutePath === '' || !is_file($absolutePath) || !is_readable($absolutePath)) {
            return false;
        }

        $size = filesize($absolutePath);
        if ($size === false || $size === 0) {
            return false;
        }

        $handle = fopen($absolutePath, 'rb');
        if ($handle === false) {
            return false;
        }

        try {
            $offset = max(0, $size - self::TAIL_BYTES);
            if (fseek($handle, $offset) !== 0) {
                return false;
            }

            $tail = stream_get_contents($handle);
            if ($tail === false) {
                return false;
            }

            return str_contains($tail, self::MARKER);
        } finally {
            fclose($handle);
        }
    }
}
