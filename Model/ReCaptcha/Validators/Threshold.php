<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\ReCaptcha\Validators;

use Hryvinskyi\InvisibleCaptcha\Model\ReCaptcha\Response;
use Hryvinskyi\InvisibleCaptcha\Model\ReCaptcha\VerifyReCaptcha;

/**
 * Class Threshold
 */
class Threshold implements ValidatorInterface
{
    /**
     * Score threshold not met
     *
     * @const string
     */
    const E_SCORE_THRESHOLD_NOT_MET = 'score-threshold-not-met';

    /**
     * @param VerifyReCaptcha $verify
     * @param Response $response
     *
     * @return string|null
     */
    public function validate(VerifyReCaptcha $verify, Response $response): ?string
    {
        if (
            $verify->getScoreThreshold()
            && $response->getScore()
            && $verify->getScoreThreshold() > $response->getScore()
        ) {
            return self::E_SCORE_THRESHOLD_NOT_MET;
        }

        return null;
    }
}
