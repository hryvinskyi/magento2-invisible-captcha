<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Helper\Config;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Backend
 */
class Backend extends AbstractHelper
{
    /**
     * Configuration path
     */
    const CONFIG_PATH_BACKEND_ENABLED = 'hryvinskyi_invisible_captcha/backend/enabled';

    /**
     * Is google recaptcha enable global
     *
     * @param string $scopeType
     * @param mixed $scopeCode
     *
     * @return bool
     */
    public function hasEnable(
        string $scopeType = ScopeInterface::SCOPE_WEBSITE,
        $scopeCode = null
    ): bool {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_BACKEND_ENABLED,
            $scopeType,
            $scopeCode
        );
    }
}