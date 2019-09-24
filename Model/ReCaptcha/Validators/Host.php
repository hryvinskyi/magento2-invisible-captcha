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
 * Class Host
 */
class Host implements ValidatorInterface
{
    /**
     * Expected hostname did not match
     *
     * @const string
     */
    const E_HOSTNAME_MISMATCH = 'hostname-mismatch';

    /**
     * Verify hostname
     *
     * @param VerifyReCaptcha $verify
     * @param Response $response
     *
     * @return string
     */
    public function validate(VerifyReCaptcha $verify, Response $response): ?string
    {
        if (
            $verify->getExpectedHostname()
            && $response->getHostname()
            && strcasecmp($verify->getExpectedHostname(), $response->getHostname()) !== 0
        ) {
            return self::E_HOSTNAME_MISMATCH;
        }

        return null;
    }
}
