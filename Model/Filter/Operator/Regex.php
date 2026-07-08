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

class Regex implements OperatorInterface
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
        return 'regex';
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): Phrase
    {
        return __('matches regex');
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
     *
     * Treats invalid PCRE patterns as non-matching and logs once so a single
     * bad admin entry cannot block legitimate traffic or 500 the request.
     */
    public function evaluate(string|int|float|null $fieldValue, string $configValue): bool
    {
        if ($configValue === '') {
            return false;
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

        return $result === 1;
    }
}
