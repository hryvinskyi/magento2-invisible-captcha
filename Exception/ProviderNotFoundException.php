<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Exception;

use Magento\Framework\Exception\LocalizedException;

/**
 * Thrown when a captcha provider code cannot be resolved from the pool.
 */
class ProviderNotFoundException extends LocalizedException
{
}
