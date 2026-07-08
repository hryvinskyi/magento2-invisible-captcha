<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Strategy\Failure\RedirectUrl;

use Hryvinskyi\InvisibleCaptcha\Api\Strategy\RedirectUrlInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Model\Url;

/**
 * Redirect URL resolved from the customer "before auth" URL (login redirect).
 *
 * Depends on the customer session concretely: `before_auth_url` lives in the
 * customer session storage namespace, so the generic
 * {@see \Magento\Framework\Session\SessionManagerInterface} preference
 * (Session\Generic) would read the wrong namespace and never find it. Wired
 * with a Proxy in etc/frontend/di.xml so the session is not started while the
 * DI graph is being built.
 */
class BeforeAuthUrl implements RedirectUrlInterface
{
    /**
     * @param CustomerSession $customerSession
     * @param Url $url
     */
    public function __construct(
        private readonly CustomerSession $customerSession,
        private readonly Url $url
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getRedirectUrl(): string
    {
        $beforeUrl = $this->customerSession->getBeforeAuthUrl();

        return $beforeUrl ?: $this->url->getLoginUrl();
    }
}
