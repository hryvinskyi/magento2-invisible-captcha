<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\ScoreThreshold\Frontend;

use Hryvinskyi\InvisibleCaptcha\Model\ScoreThreshold\AbstractScoreThreshold;

/**
 * Class CustomerCreate
 */
class CustomerCreate extends AbstractScoreThreshold
{
    /**
     * @inheritDoc
     */
    public function getValue(): float
    {
        return $this->getFrontendConfig()->getScoreThresholdCustomerCreate();
    }
}
