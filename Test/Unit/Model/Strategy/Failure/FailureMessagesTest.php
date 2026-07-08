<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Strategy\Failure;

use Hryvinskyi\InvisibleCaptcha\Model\Strategy\Failure\FailureMessages;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FailureMessagesTest extends TestCase
{
    private const MESSAGES = [
        'missing-input-response' => 'The captcha token is missing.',
        'invalid-input-response' => 'The captcha token is invalid.',
        'unknown-error' => 'Captcha verification failed.',
    ];

    public function testGetErrorMessagesReturnsConfiguredMap(): void
    {
        $failureMessages = new FailureMessages(self::MESSAGES);

        $this->assertSame(self::MESSAGES, $failureMessages->getErrorMessages());
    }

    public function testDefaultsToEmptyMap(): void
    {
        $failureMessages = new FailureMessages();

        $this->assertSame([], $failureMessages->getErrorMessages());
        $this->assertNull($failureMessages->getErrorMessage('anything'));
        $this->assertFalse($failureMessages->hasErrorMessage('anything'));
    }

    #[DataProvider('getErrorMessageProvider')]
    public function testGetErrorMessage(string $key, ?string $expected): void
    {
        $failureMessages = new FailureMessages(self::MESSAGES);

        $this->assertSame($expected, $failureMessages->getErrorMessage($key));
    }

    public static function getErrorMessageProvider(): array
    {
        return [
            'known code' => ['missing-input-response', 'The captcha token is missing.'],
            'another known code' => ['invalid-input-response', 'The captcha token is invalid.'],
            'unknown fallback code' => ['unknown-error', 'Captcha verification failed.'],
            'absent code returns null' => ['does-not-exist', null],
        ];
    }

    #[DataProvider('hasErrorMessageProvider')]
    public function testHasErrorMessage(string $key, bool $expected): void
    {
        $failureMessages = new FailureMessages(self::MESSAGES);

        $this->assertSame($expected, $failureMessages->hasErrorMessage($key));
    }

    public static function hasErrorMessageProvider(): array
    {
        return [
            'present code' => ['missing-input-response', true],
            'fallback code present' => ['unknown-error', true],
            'absent code' => ['does-not-exist', false],
        ];
    }
}
