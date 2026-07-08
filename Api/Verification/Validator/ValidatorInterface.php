<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api\Verification\Validator;

use Hryvinskyi\InvisibleCaptcha\Api\Verification\VerificationRequestInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Verification\VerificationResultInterface;

/**
 * Post-verification check applied to a provider response. Each validator is
 * self-gating: it returns null (pass) when the relevant expectation is not set
 * on the request, so the same validator list is reusable across all providers.
 */
interface ValidatorInterface
{
    /**
     * @return string|null Error code on failure, null when the check passes / is not applicable.
     */
    public function validate(
        VerificationRequestInterface $request,
        VerificationResultInterface $result
    ): ?string;
}
