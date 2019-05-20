<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Provider\Failure\RedirectUrl;

use Hryvinskyi\InvisibleCaptcha\Model\Provider\Failure\RedirectUrlInterface;
use Magento\Framework\UrlInterface;

class SimpleUrlProvider implements RedirectUrlInterface
{
    /**
     * @var string
     */
    private $urlPath;

    /**
     * @var array
     */
    private $urlParams;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * SimpleUrlProvider constructor.
     * @param UrlInterface $url
     * @param $urlPath
     * @param null $urlParams
     */
    public function __construct(
        UrlInterface $url,
        $urlPath,
        $urlParams = null
    ) {
        $this->urlPath = $urlPath;
        $this->urlParams = $urlParams;
        $this->url = $url;
    }

    /**
     * Get redirection URL
     * @return string
     */
    public function getRedirectUrl(): string
    {
        return $this->url->getUrl($this->urlPath, $this->urlParams);
    }
}
