<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Strategy\Failure;

/**
 * DI-configured map of error code => user-facing message.
 */
class FailureMessages
{
    /**
     * @param array<string, string> $errorMessages
     */
    public function __construct(
        private readonly array $errorMessages = []
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function getErrorMessages(): array
    {
        return $this->errorMessages;
    }

    public function getErrorMessage(string $key): ?string
    {
        return $this->errorMessages[$key] ?? null;
    }

    public function hasErrorMessage(string $key): bool
    {
        return array_key_exists($key, $this->errorMessages);
    }
}
