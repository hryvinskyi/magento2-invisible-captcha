<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Strategy\Failure\RedirectUrl;

use Hryvinskyi\InvisibleCaptcha\Api\Strategy\RedirectUrlInterface;
use Magento\Customer\Model\Url;
use Magento\Framework\Session\SessionManagerInterface;

/**
 * Redirect URL resolved from the customer "before auth" URL (login redirect).
 */
class BeforeAuthUrl implements RedirectUrlInterface
{
    /**
     * @param SessionManagerInterface $sessionManager
     * @param Url $url
     */
    public function __construct(
        private readonly SessionManagerInterface $sessionManager,
        private readonly Url $url
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getRedirectUrl(): string
    {
        $beforeUrl = $this->sessionManager->getBeforeAuthUrl();

        return $beforeUrl ?: $this->url->getLoginUrl();
    }
}
