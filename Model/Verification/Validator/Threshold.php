<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Verification\Validator;

use Hryvinskyi\InvisibleCaptcha\Api\Verification\Validator\ValidatorInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Verification\VerificationRequestInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Verification\VerificationResultInterface;

/**
 * Fails when a score-based provider's score is below the requested threshold.
 * No-op for providers that do not return a score (v2 / Turnstile).
 */
class Threshold implements ValidatorInterface
{
    public const ERROR_CODE = 'score-threshold-not-met';

    /**
     * @inheritDoc
     */
    public function validate(
        VerificationRequestInterface $request,
        VerificationResultInterface $result
    ): ?string {
        $threshold = $request->getScoreThreshold();
        $score = $result->getScore();

        if ($threshold === null || $score === null) {
            return null;
        }

        return $threshold > $score ? self::ERROR_CODE : null;
    }
}
