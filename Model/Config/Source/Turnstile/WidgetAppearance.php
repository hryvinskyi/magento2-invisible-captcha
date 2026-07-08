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
 * Cloudflare Turnstile widget `appearance` options — controls when the widget
 * surface is shown to the visitor.
 */
class WidgetAppearance implements OptionSourceInterface
{
    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'always', 'label' => __('Always visible')],
            ['value' => 'execute', 'label' => __('Visible only after execute()')],
            ['value' => 'interaction-only', 'label' => __('Visible only when interaction needed (invisible-like)')],
        ];
    }
}
