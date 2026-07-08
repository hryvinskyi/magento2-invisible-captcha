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
 * Fails when the challenge is older than the allowed number of seconds.
 * No-op unless a challenge timeout is set and the provider reported a timestamp.
 */
class TimeoutSeconds implements ValidatorInterface
{
    public const ERROR_CODE = 'challenge-timeout';

    /**
     * @inheritDoc
     */
    public function validate(
        VerificationRequestInterface $request,
        VerificationResultInterface $result
    ): ?string {
        $timeout = $request->getChallengeTimeout();
        $challengeTs = $result->getChallengeTs();

        if ($timeout === null || $timeout <= 0 || $challengeTs === null || $challengeTs === '') {
            return null;
        }

        $challengeTime = strtotime($challengeTs);
        if ($challengeTime === false) {
            return null;
        }

        return (time() - $challengeTime) > $timeout ? self::ERROR_CODE : null;
    }
}
