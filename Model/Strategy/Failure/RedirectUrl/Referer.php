<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Strategy\Failure\RedirectUrl;

use Hryvinskyi\InvisibleCaptcha\Api\Strategy\RedirectUrlInterface;
use Magento\Framework\App\Response\RedirectInterface;

/**
 * Redirect URL resolved from the request referer.
 */
class Referer implements RedirectUrlInterface
{
    /**
     * @param RedirectInterface $redirect
     */
    public function __construct(
        private readonly RedirectInterface $redirect
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getRedirectUrl(): string
    {
        return $this->redirect->getRedirectUrl();
    }
}
