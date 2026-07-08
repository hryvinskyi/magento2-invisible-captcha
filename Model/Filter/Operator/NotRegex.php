<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Filter\Operator;

use Magento\Framework\Phrase;

class NotRegex extends AbstractRegex
{
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
     *
     * True only when the pattern is valid and did not match; an invalid pattern
     * returns false (fail safe — see {@see AbstractRegex::match()}).
     */
    public function evaluate(string|int|float|null $fieldValue, string $configValue): bool
    {
        if ($configValue === '') {
            return true;
        }

        return $this->match($fieldValue, $configValue) === false;
    }
}
