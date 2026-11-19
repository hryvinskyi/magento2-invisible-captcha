<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api\Http;

use Magento\Framework\App\RequestInterface;

/**
 * Resolves the client IP, so consumers do not each re-implement the
 * security-relevant policy.
 *
 * One answer serves the "Excluded IPs" list, the `client_ip` rule field, the
 * country lookup, the address sent with the token verification and the debug
 * log, so all of them see the same address.
 *
 * The module's own implementation reads forwarding headers that the client
 * writes itself whenever the shop is reached without a proxy that overwrites
 * them. A module that knows the shop's proxies replaces or decorates this
 * contract through a di.xml preference.
 *
 * @api
 */
interface ClientIpResolverInterface
{
    /**
     * Best-effort client IP for the current request. The module's own
     * implementation reads, in priority order, CF-Connecting-IP, X-Real-IP,
     * the left-most X-Forwarded-For entry and REMOTE_ADDR.
     *
     * @return string Empty when no address can be determined
     */
    public function resolve(): string;

    /**
     * Client IP of an explicitly provided request — e.g. the rule tester's
     * synthetic request. For the current request the answer is the one
     * {@see resolve()} gives.
     *
     * @param RequestInterface $request
     * @return string Empty when no address can be determined
     */
    public function resolveFrom(RequestInterface $request): string;
}
