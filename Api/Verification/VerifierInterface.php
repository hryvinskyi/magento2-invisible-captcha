<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api\Verification;

/**
 * Server-side token verifier. One implementation per verification protocol:
 *  - SiteVerifyVerifier  — form-encoded siteverify (reCAPTCHA v2/v3 + Turnstile),
 *  - EnterpriseVerifier  — reCAPTCHA Enterprise assessments API.
 */
interface VerifierInterface
{
    /**
     * Verify the request and return a rich, provider-agnostic result.
     * Implementations must fail closed (success=false) on any transport error.
     */
    public function verify(VerificationRequestInterface $request): VerificationResultInterface;
}
