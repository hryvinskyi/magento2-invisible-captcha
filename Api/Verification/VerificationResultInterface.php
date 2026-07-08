<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api\Verification;

/**
 * Provider-agnostic verification result.
 *
 * Models the union of all provider response shapes: reCAPTCHA v2/v3, Enterprise
 * and Cloudflare Turnstile all map onto success + error-codes (+ optional
 * hostname / challenge timestamp / score / action).
 */
interface VerificationResultInterface
{
    /**
     * Whether the provider confirmed the token (after local validators).
     */
    public function isSuccess(): bool;

    /**
     * Provider and local validator error codes (kebab-case strings).
     *
     * @return string[]
     */
    public function getErrorCodes(): array;

    /**
     * Hostname the challenge was solved on, when reported.
     */
    public function getHostname(): ?string;

    /**
     * ISO-8601 timestamp of the challenge, when reported.
     */
    public function getChallengeTs(): ?string;

    /**
     * Risk score for score-based providers (0.0–1.0), null otherwise.
     */
    public function getScore(): ?float;

    /**
     * Action name echoed by score-based providers, null otherwise.
     */
    public function getAction(): ?string;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
