<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Provider;

use Hryvinskyi\InvisibleCaptcha\Model\ReCaptcha\VerifyReCaptcha;

/**
 * Class AbstractFailure
 */
abstract class AbstractFailure implements FailureInterface
{
    /**
     * Invalid JSON received
     *
     * @const string
     */
    const E_INVALID_JSON = 'invalid-json';

    /**
     * Could not connect to service
     *
     * @const string
     */
    const E_CONNECTION_FAILED = 'connection-failed';

    /**
     * Did not receive a 200 from the service
     *
     * @const string
     */
    const E_BAD_RESPONSE = 'bad-response';

    /**
     * Not a success, but no error codes received!
     *
     * @const string
     */
    const E_UNKNOWN_ERROR = 'unknown-error';

    /**
     * ReCAPTCHA response not provided
     *
     * @const string
     */
    const E_MISSING_INPUT_RESPONSE = 'missing-input-response';

    /**
     * Expected hostname did not match
     *
     * @const string
     */
    const E_HOSTNAME_MISMATCH = 'hostname-mismatch';

    /**
     * Expected action did not match
     *
     * @const string
     */
    const E_ACTION_MISMATCH = 'action-mismatch';

    /**
     * Score threshold not met
     *
     * @const string
     */
    const E_SCORE_THRESHOLD_NOT_MET = 'score-threshold-not-met';

    /**
     * Challenge timeout
     *
     * @const string
     */
    const E_CHALLENGE_TIMEOUT = 'challenge-timeout';
    
    /**
     * @param VerifyReCaptcha $verifyReCaptcha
     *
     * @return array
     */
    public function checkMessages(VerifyReCaptcha $verifyReCaptcha): array
    {

    }
}
