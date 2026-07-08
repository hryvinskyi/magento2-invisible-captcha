<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Verification\Validator;

use Hryvinskyi\InvisibleCaptcha\Model\Verification\Validator\Threshold;
use Hryvinskyi\InvisibleCaptcha\Model\Verification\VerificationRequest;
use Hryvinskyi\InvisibleCaptcha\Model\Verification\VerificationResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ThresholdTest extends TestCase
{
    private Threshold $validator;

    protected function setUp(): void
    {
        $this->validator = new Threshold();
    }

    public function testNoOpWhenThresholdUnset(): void
    {
        $request = new VerificationRequest();
        $result = new VerificationResult(true, [], null, null, 0.1);

        $this->assertNull($this->validator->validate($request, $result));
    }

    public function testNoOpWhenProviderReturnsNoScore(): void
    {
        $request = (new VerificationRequest())->setScoreThreshold(0.5);
        $result = new VerificationResult(true, [], null, null, null);

        $this->assertNull($this->validator->validate($request, $result));
    }

    #[DataProvider('scoreProvider')]
    public function testScoreAgainstThreshold(float $threshold, float $score, ?string $expected): void
    {
        $request = (new VerificationRequest())->setScoreThreshold($threshold);
        $result = new VerificationResult(true, [], null, null, $score);

        $this->assertSame($expected, $this->validator->validate($request, $result));
    }

    /**
     * @return array<string, array{0: float, 1: float, 2: string|null}>
     */
    public static function scoreProvider(): array
    {
        return [
            'score above threshold passes' => [0.5, 0.9, null],
            'score equal to threshold passes' => [0.5, 0.5, null],
            'score below threshold fails' => [0.5, 0.1, Threshold::ERROR_CODE],
        ];
    }
}
