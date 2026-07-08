<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Strategy\Failure;

use Hryvinskyi\InvisibleCaptcha\Api\Strategy\FailureStrategyInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Verification\VerificationResultInterface;

/**
 * Shared failure-message resolution for failure strategies.
 */
abstract class AbstractFailure implements FailureStrategyInterface
{
    /**
     * @param FailureMessages $failureMessages
     */
    public function __construct(
        private readonly FailureMessages $failureMessages
    ) {
    }

    /**
     * Resolve the configured messages for the result's error codes.
     *
     * @return string[]
     */
    public function getMessages(VerificationResultInterface $result): array
    {
        $messages = [];
        foreach ($result->getErrorCodes() as $code) {
            if ($this->failureMessages->hasErrorMessage($code)) {
                $messages[] = (string)$this->failureMessages->getErrorMessage($code);
            }
        }

        if ($messages === [] && $this->failureMessages->hasErrorMessage('unknown-error')) {
            $messages[] = (string)$this->failureMessages->getErrorMessage('unknown-error');
        }

        return $messages;
    }

    /**
     * Resolve a single combined message string.
     */
    public function getMessagesString(VerificationResultInterface $result): string
    {
        return implode('<br>', $this->getMessages($result));
    }
}
