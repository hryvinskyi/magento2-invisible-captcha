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
 * Fails when the expected hostname does not match the one reported by the provider.
 * No-op unless an expected hostname is set on the request.
 */
class Host implements ValidatorInterface
{
    public const ERROR_CODE = 'hostname-mismatch';

    /**
     * @inheritDoc
     */
    public function validate(
        VerificationRequestInterface $request,
        VerificationResultInterface $result
    ): ?string {
        $expected = $request->getExpectedHostname();
        if ($expected === null || $expected === '') {
            return null;
        }

        $actual = $result->getHostname();
        if ($actual === null || $actual === '') {
            return null;
        }

        return $actual !== $expected ? self::ERROR_CODE : null;
    }
}
