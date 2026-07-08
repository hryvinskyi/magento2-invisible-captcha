<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Verification;

use Hryvinskyi\InvisibleCaptcha\Model\Verification\VerificationResult;
use PHPUnit\Framework\TestCase;

class VerificationResultTest extends TestCase
{
    public function testGettersReflectConstructorArguments(): void
    {
        $result = new VerificationResult(
            true,
            ['error-a', 'error-b'],
            'example.com',
            '2026-06-25T10:00:00Z',
            0.9,
            'login'
        );

        $this->assertTrue($result->isSuccess());
        $this->assertSame(['error-a', 'error-b'], $result->getErrorCodes());
        $this->assertSame('example.com', $result->getHostname());
        $this->assertSame('2026-06-25T10:00:00Z', $result->getChallengeTs());
        $this->assertSame(0.9, $result->getScore());
        $this->assertSame('login', $result->getAction());
    }

    public function testDefaultsAreNullAndEmpty(): void
    {
        $result = new VerificationResult(false);

        $this->assertFalse($result->isSuccess());
        $this->assertSame([], $result->getErrorCodes());
        $this->assertNull($result->getHostname());
        $this->assertNull($result->getChallengeTs());
        $this->assertNull($result->getScore());
        $this->assertNull($result->getAction());
    }

    public function testFailureNamedConstructorIsNotSuccessAndCarriesErrorCodes(): void
    {
        $result = VerificationResult::failure(['missing-input-response']);

        $this->assertInstanceOf(VerificationResult::class, $result);
        $this->assertFalse($result->isSuccess());
        $this->assertSame(['missing-input-response'], $result->getErrorCodes());
        $this->assertNull($result->getHostname());
        $this->assertNull($result->getChallengeTs());
        $this->assertNull($result->getScore());
        $this->assertNull($result->getAction());
    }

    public function testToArrayExposesSerializableShape(): void
    {
        $result = new VerificationResult(
            true,
            ['boom'],
            'example.com',
            '2026-06-25T10:00:00Z',
            0.7,
            'checkout'
        );

        $this->assertSame(
            [
                'success' => true,
                'hostname' => 'example.com',
                'challenge_ts' => '2026-06-25T10:00:00Z',
                'score' => 0.7,
                'action' => 'checkout',
                'error-codes' => ['boom'],
            ],
            $result->toArray()
        );
    }

    public function testToArrayWithDefaults(): void
    {
        $result = new VerificationResult(false);

        $this->assertSame(
            [
                'success' => false,
                'hostname' => null,
                'challenge_ts' => null,
                'score' => null,
                'action' => null,
                'error-codes' => [],
            ],
            $result->toArray()
        );
    }
}
