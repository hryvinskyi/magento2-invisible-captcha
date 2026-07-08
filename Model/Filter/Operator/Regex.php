<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Filter\Operator;

use Magento\Framework\Phrase;

class Regex extends AbstractRegex
{
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
     *
     * Invalid patterns never match (fail safe — see {@see AbstractRegex::match()}).
     */
    public function evaluate(string|int|float|null $fieldValue, string $configValue): bool
    {
        if ($configValue === '') {
            return false;
        }

        return $this->match($fieldValue, $configValue) === true;
    }
}
