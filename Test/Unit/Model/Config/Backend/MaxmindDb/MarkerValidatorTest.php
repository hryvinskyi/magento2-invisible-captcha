<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Config\Backend\MaxmindDb;

use Hryvinskyi\InvisibleCaptcha\Model\Config\Backend\MaxmindDb\MarkerValidator;
use PHPUnit\Framework\TestCase;

class MarkerValidatorTest extends TestCase
{
    private const MARKER = "\xab\xcd\xefMaxMind.com";

    private MarkerValidator $validator;

    /** @var string[] */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        $this->validator = new MarkerValidator();
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        $this->tempFiles = [];
    }

    public function testMarkerInTailIsValid(): void
    {
        $path = $this->writeTempFile(str_repeat('A', 4096) . self::MARKER . 'binary-metadata-payload');

        $this->assertTrue($this->validator->isValid($path));
    }

    public function testNonMarkerBytesAreInvalid(): void
    {
        // Deterministic filler that cannot contain the marker (no 0xAB byte).
        $path = $this->writeTempFile(str_repeat("\x00\x01\x02\x03", 2048));

        $this->assertFalse($this->validator->isValid($path));
    }

    public function testMarkerOnlyAtStartOfLargeFileIsInvalid(): void
    {
        // Marker at the very front, followed by >128 KiB of filler, so it falls
        // outside the scanned tail window — proving only the tail is read.
        $path = $this->writeTempFile(self::MARKER . str_repeat('Z', 200 * 1024));

        $this->assertFalse($this->validator->isValid($path));
    }

    public function testEmptyFileIsInvalid(): void
    {
        $path = $this->writeTempFile('');

        $this->assertFalse($this->validator->isValid($path));
    }

    public function testMissingFileIsInvalid(): void
    {
        $this->assertFalse($this->validator->isValid(sys_get_temp_dir() . '/does-not-exist-' . uniqid('', true) . '.mmdb'));
    }

    public function testEmptyPathIsInvalid(): void
    {
        $this->assertFalse($this->validator->isValid(''));
    }

    private function writeTempFile(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'mmdb_test_');
        self::assertIsString($path);
        file_put_contents($path, $contents);
        $this->tempFiles[] = $path;

        return $path;
    }
}
