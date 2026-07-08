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
 * Fails when the expected action does not match the one echoed by a score-based
 * provider. No-op unless an expected action is set and the provider echoed one.
 */
class Action implements ValidatorInterface
{
    public const ERROR_CODE = 'action-mismatch';

    /**
     * @inheritDoc
     */
    public function validate(
        VerificationRequestInterface $request,
        VerificationResultInterface $result
    ): ?string {
        $expected = $request->getExpectedAction();
        if ($expected === null || $expected === '') {
            return null;
        }

        $actual = $result->getAction();
        if ($actual === null || $actual === '') {
            return null;
        }

        return $actual !== $expected ? self::ERROR_CODE : null;
    }
}
