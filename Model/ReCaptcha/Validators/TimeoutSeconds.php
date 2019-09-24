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
 * Class TimeoutSeconds
 */
class TimeoutSeconds implements ValidatorInterface
{
    /**
     * Challenge timeout
     *
     * @const string
     */
    const E_CHALLENGE_TIMEOUT = 'challenge-timeout';

    /**
     * @param VerifyReCaptcha $verify
     * @param Response $response
     *
     * @return string|null
     */
    public function validate(VerifyReCaptcha $verify, Response $response): ?string
    {
        if ($verify->getChallengeTimeout() && $response->getChallengeTs()) {
            $challengeTs = strtotime($response->getChallengeTs());

            if ($challengeTs > 0 && time() - $challengeTs > $verify->getChallengeTimeout()) {
                return self::E_CHALLENGE_TIMEOUT;
            }
        }

        return null;
    }
}
