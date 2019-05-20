<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Provider\Failure\RedirectUrl;

use Hryvinskyi\InvisibleCaptcha\Model\Provider\Failure\RedirectUrlInterface;
use Magento\Customer\Model\Url;
use Magento\Framework\Session\SessionManagerInterface;

class BeforeAuthUrlProvider implements RedirectUrlInterface
{
    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var Url
     */
    private $url;

    /**
     * BeforeAuthUrlProvider constructor.
     *
     * @param SessionManagerInterface $sessionManager
     * @param Url $url
     */
    public function __construct(
        SessionManagerInterface $sessionManager,
        Url $url
    ) {
        $this->sessionManager = $sessionManager;
        $this->url = $url;
    }

    /**
     * Get redirection URL
     *
     * @return string
     */
    public function getRedirectUrl(): string
    {
        $beforeUrl = $this->sessionManager->getBeforeAuthUrl();

        return $beforeUrl ?: $this->url->getLoginUrl();
    }
}
