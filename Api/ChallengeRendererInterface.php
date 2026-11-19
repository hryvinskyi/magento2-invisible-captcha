<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api;

use Magento\Framework\Exception\LocalizedException;

/**
 * Renders the inline challenge page served in place of a gated response.
 */
interface ChallengeRendererInterface
{
    /**
     * The full challenge HTML document.
     *
     * @param string $refId Correlation token echoed back by the verify POST
     * @return string
     * @throws LocalizedException
     */
    public function render(string $refId): string;
}
