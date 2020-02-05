<?php
/**
 * Copyright (c) 2020. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Verify\Adminhtml;

use Hryvinskyi\InvisibleCaptcha\Model\AbstractCheckEnabledVerify;

/**
 * Class ForgotPassword
 */
class ForgotPassword extends AbstractCheckEnabledVerify
{
    public function verify(): bool
    {
        return parent::verify() && $this->getBackendConfig()->hasEnabledForgot();
    }
}