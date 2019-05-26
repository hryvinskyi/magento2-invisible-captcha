<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Provider;

use Hryvinskyi\InvisibleCaptcha\Model\ReCaptcha\Response;
use Magento\Framework\App\ResponseInterface;

interface FailureInterface
{
    /**
     * Handle captcha failure
     *
     * @param Response $verifyReCaptcha
     * @param ResponseInterface $response
     *
     * @return void
     */
    public function execute(Response $verifyReCaptcha, ResponseInterface $response = null);
}
