<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\ScoreThreshold\Backend;

use Hryvinskyi\InvisibleCaptcha\Model\ScoreThreshold\AbstractScoreThreshold;

/**
 * Class ForgotPassword
 */
class ForgotPassword extends AbstractScoreThreshold
{
    public function getValue(): float
    {
        return $this->getBackendConfig()->getScoreThresholdForgot();
    }
}
