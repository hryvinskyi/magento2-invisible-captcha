<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api\Verification;

/**
 * Mutable, fluent value object describing a single verification attempt.
 *
 * A provider builds one of these (secret/url/response param) and the caller
 * adds the per-request data (token, remote IP) plus optional score-based
 * expectations (action, hostname, threshold, challenge timeout). Enterprise
 * carries extra fields (site key, project id) through {@see self::getExtra()}.
 */
interface VerificationRequestInterface
{
    public function getSecret(): string;

    public function setSecret(string $secret): self;

    public function getResponse(): string;

    public function setResponse(string $response): self;

    public function getRemoteIp(): ?string;

    public function setRemoteIp(?string $remoteIp): self;

    public function getVerifyUrl(): string;

    public function setVerifyUrl(string $verifyUrl): self;

    public function getExpectedAction(): ?string;

    public function setExpectedAction(?string $action): self;

    public function getExpectedHostname(): ?string;

    public function setExpectedHostname(?string $hostname): self;

    public function getScoreThreshold(): ?float;

    public function setScoreThreshold(?float $threshold): self;

    public function getChallengeTimeout(): ?int;

    public function setChallengeTimeout(?int $timeoutSeconds): self;

    /**
     * Provider-specific extra fields (e.g. Enterprise siteKey/projectId).
     *
     * @return array<string, mixed>
     */
    public function getExtra(): array;

    /**
     * @param array<string, mixed> $extra
     */
    public function setExtra(array $extra): self;

    /**
     * @return mixed
     */
    public function getExtraValue(string $key, mixed $default = null): mixed;
}
