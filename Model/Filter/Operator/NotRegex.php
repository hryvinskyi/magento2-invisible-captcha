<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Filter\Operator;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\OperatorInterface;
use Magento\Framework\Phrase;
use Psr\Log\LoggerInterface;

class NotRegex implements OperatorInterface
{
    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getCode(): string
    {
        return 'not_regex';
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): Phrase
    {
        return __('does not match regex');
    }

    /**
     * @inheritDoc
     */
    public function supports(string $fieldType): bool
    {
        return $fieldType === FieldInterface::TYPE_STRING;
    }

    /**
     * @inheritDoc
     */
    public function evaluate(string|int|float|null $fieldValue, string $configValue): bool
    {
        if ($configValue === '') {
            return true;
        }

        $result = @preg_match($configValue, (string)($fieldValue ?? ''));
        if ($result === false) {
            $this->logger->warning(sprintf(
                '[InvisibleCaptcha] invalid regex skipped | pattern=%s | error=%s',
                $configValue,
                (string)preg_last_error_msg()
            ));

            return false;
        }

        return $result === 0;
    }
}
