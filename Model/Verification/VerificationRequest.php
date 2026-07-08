<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Verification;

use Hryvinskyi\InvisibleCaptcha\Api\Verification\VerificationRequestInterface;

/**
 * Mutable, fluent verification request DTO. Instantiate via the auto-generated
 * VerificationRequestFactory so each verification gets a fresh instance.
 */
class VerificationRequest implements VerificationRequestInterface
{
    private string $secret = '';
    private string $response = '';
    private ?string $remoteIp = null;
    private string $verifyUrl = '';
    private ?string $expectedAction = null;
    private ?string $expectedHostname = null;
    private ?float $scoreThreshold = null;
    private ?int $challengeTimeout = null;

    /** @var array<string, mixed> */
    private array $extra = [];

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function setSecret(string $secret): VerificationRequestInterface
    {
        $this->secret = $secret;

        return $this;
    }

    public function getResponse(): string
    {
        return $this->response;
    }

    public function setResponse(string $response): VerificationRequestInterface
    {
        $this->response = $response;

        return $this;
    }

    public function getRemoteIp(): ?string
    {
        return $this->remoteIp;
    }

    public function setRemoteIp(?string $remoteIp): VerificationRequestInterface
    {
        $this->remoteIp = $remoteIp;

        return $this;
    }

    public function getVerifyUrl(): string
    {
        return $this->verifyUrl;
    }

    public function setVerifyUrl(string $verifyUrl): VerificationRequestInterface
    {
        $this->verifyUrl = $verifyUrl;

        return $this;
    }

    public function getExpectedAction(): ?string
    {
        return $this->expectedAction;
    }

    public function setExpectedAction(?string $action): VerificationRequestInterface
    {
        $this->expectedAction = $action;

        return $this;
    }

    public function getExpectedHostname(): ?string
    {
        return $this->expectedHostname;
    }

    public function setExpectedHostname(?string $hostname): VerificationRequestInterface
    {
        $this->expectedHostname = $hostname;

        return $this;
    }

    public function getScoreThreshold(): ?float
    {
        return $this->scoreThreshold;
    }

    public function setScoreThreshold(?float $threshold): VerificationRequestInterface
    {
        $this->scoreThreshold = $threshold;

        return $this;
    }

    public function getChallengeTimeout(): ?int
    {
        return $this->challengeTimeout;
    }

    public function setChallengeTimeout(?int $timeoutSeconds): VerificationRequestInterface
    {
        $this->challengeTimeout = $timeoutSeconds;

        return $this;
    }

    public function getExtra(): array
    {
        return $this->extra;
    }

    public function setExtra(array $extra): VerificationRequestInterface
    {
        $this->extra = $extra;

        return $this;
    }

    public function getExtraValue(string $key, mixed $default = null): mixed
    {
        return $this->extra[$key] ?? $default;
    }
}
