<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Config\Source\Recaptcha;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Google reCAPTCHA v2 checkbox widget size options.
 */
class V2Size implements OptionSourceInterface
{
    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'normal', 'label' => __('Normal')],
            ['value' => 'compact', 'label' => __('Compact')],
        ];
    }
}
