<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Strategy\Failure\RedirectUrl;

use Hryvinskyi\InvisibleCaptcha\Api\Strategy\RedirectUrlInterface;
use Magento\Framework\UrlInterface;

/**
 * Redirect URL built from a configured route path and params.
 */
class Simple implements RedirectUrlInterface
{
    /**
     * @param UrlInterface $url
     * @param string $urlPath
     * @param array<string, mixed>|null $urlParams
     */
    public function __construct(
        private readonly UrlInterface $url,
        private readonly string $urlPath,
        private readonly ?array $urlParams = null
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getRedirectUrl(): string
    {
        return $this->url->getUrl($this->urlPath, $this->urlParams);
    }
}
