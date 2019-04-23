<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\InvisibleCaptcha\Model;

interface CheckEnabledVerifyInterface
{
    /**
     * Return true if check enabled captcha
     *
     * @return bool
     */
    public function verify(): bool;
}
