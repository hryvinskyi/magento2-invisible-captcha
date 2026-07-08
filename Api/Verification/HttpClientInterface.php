<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api\Verification;

use Hryvinskyi\InvisibleCaptcha\Exception\HttpClientException;

/**
 * Minimal bounded HTTP transport for outbound siteverify / assessment calls.
 *
 * The wired implementation enforces a strict wall-clock budget so a degraded
 * provider endpoint can never tie up a PHP-FPM worker.
 */
interface HttpClientInterface
{
    /**
     * POST a raw body to the given URL and return the response body.
     *
     * @param string $url Absolute endpoint URL
     * @param string $body Pre-encoded request body (form-encoded or JSON)
     * @param array<string, string> $headers Request headers
     * @return string Raw response body
     * @throws HttpClientException On any transport error or non-2xx response
     */
    public function post(string $url, string $body, array $headers = []): string;
}
