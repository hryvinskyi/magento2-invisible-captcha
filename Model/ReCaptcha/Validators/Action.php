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
 * Class Action
 */
class Action implements ValidatorInterface
{
    /**
     * Expected action did not match
     *
     * @const string
     */
    const E_ACTION_MISMATCH = 'action-mismatch';

    /**
     * @param VerifyReCaptcha $verify
     * @param Response $response
     *
     * @return string|null
     */
    public function validate(VerifyReCaptcha $verify, Response $response): ?string
    {
        if (
            $verify->getExpectedAction()
            && $response->getAction()
            && strcasecmp($verify->getExpectedAction(), $response->getAction()) !== 0
        ) {
            $validationErrors[] = self::E_ACTION_MISMATCH;
        }

        return null;
    }
}
