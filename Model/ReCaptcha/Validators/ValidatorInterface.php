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
 * Class ValidatorInterface
 */
interface ValidatorInterface
{
    public function validate(VerifyReCaptcha $verify, Response $response): ?string;
}
