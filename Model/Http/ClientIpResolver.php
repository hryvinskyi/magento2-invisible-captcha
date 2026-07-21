<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Http;

use Hryvinskyi\InvisibleCaptcha\Api\Http\ClientIpResolverInterface;
use Magento\Framework\App\RequestInterface;

class ClientIpResolver implements ClientIpResolverInterface
{
    /**
     * Server headers checked when resolving the client IP, in priority order.
     */
    private const CLIENT_IP_HEADERS = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_REAL_IP',
        'HTTP_X_FORWARDED_FOR',
        'REMOTE_ADDR',
    ];

    /**
     * @param RequestInterface $request
     */
    public function __construct(
        private readonly RequestInterface $request
    ) {
    }

    /**
     * @inheritDoc
     */
    public function resolve(): string
    {
        return $this->resolveFrom($this->request);
    }

    /**
     * @inheritDoc
     */
    public function resolveFrom(RequestInterface $request): string
    {
        foreach (self::CLIENT_IP_HEADERS as $header) {
            $value = $request->getServer($header);
            if (empty($value)) {
                continue;
            }

            $ip = (string)$value;
            if (str_contains($ip, ',')) {
                $ip = trim(explode(',', $ip)[0]);
            }

            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        return '';
    }
}
