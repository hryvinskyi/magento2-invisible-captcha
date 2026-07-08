<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api\Strategy;

use Hryvinskyi\InvisibleCaptcha\Api\Verification\VerificationResultInterface;
use Magento\Framework\App\ResponseInterface;

/**
 * Handles a failed form-level verification: redirect, AJAX JSON error, or
 * throw an authentication exception, depending on the form.
 */
interface FailureStrategyInterface
{
    /**
     * @param VerificationResultInterface $result The failed verification result.
     * @param ResponseInterface|null $response Current controller response, when available.
     */
    public function execute(VerificationResultInterface $result, ?ResponseInterface $response = null): void;
}
