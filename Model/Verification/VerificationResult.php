<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Verification;

use Hryvinskyi\InvisibleCaptcha\Api\Verification\VerificationResultInterface;

/**
 * Immutable provider-agnostic verification result.
 */
class VerificationResult implements VerificationResultInterface
{
    /**
     * @param string[] $errorCodes
     */
    public function __construct(
        private readonly bool $success,
        private readonly array $errorCodes = [],
        private readonly ?string $hostname = null,
        private readonly ?string $challengeTs = null,
        private readonly ?float $score = null,
        private readonly ?string $action = null
    ) {
    }

    /**
     * Named constructor for a failure result carrying error codes.
     *
     * @param string[] $errorCodes
     */
    public static function failure(array $errorCodes): self
    {
        return new self(false, $errorCodes);
    }

    /**
     * @inheritDoc
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @inheritDoc
     */
    public function getErrorCodes(): array
    {
        return $this->errorCodes;
    }

    /**
     * @inheritDoc
     */
    public function getHostname(): ?string
    {
        return $this->hostname;
    }

    /**
     * @inheritDoc
     */
    public function getChallengeTs(): ?string
    {
        return $this->challengeTs;
    }

    /**
     * @inheritDoc
     */
    public function getScore(): ?float
    {
        return $this->score;
    }

    /**
     * @inheritDoc
     */
    public function getAction(): ?string
    {
        return $this->action;
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'hostname' => $this->hostname,
            'challenge_ts' => $this->challengeTs,
            'score' => $this->score,
            'action' => $this->action,
            'error-codes' => $this->errorCodes,
        ];
    }
}
