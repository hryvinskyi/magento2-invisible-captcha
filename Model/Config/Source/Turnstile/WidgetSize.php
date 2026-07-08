<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Config\Source\Turnstile;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Cloudflare Turnstile widget size options.
 */
class WidgetSize implements OptionSourceInterface
{
    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'flexible', 'label' => __('Flexible (auto-width)')],
            ['value' => 'normal', 'label' => __('Normal (300x65)')],
            ['value' => 'compact', 'label' => __('Compact (150x140)')],
        ];
    }
}
