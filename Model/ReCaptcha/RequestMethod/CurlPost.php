<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\ReCaptcha\RequestMethod;

use Hryvinskyi\InvisibleCaptcha\Model\ReCaptcha\RequestMethodInterface;
use Hryvinskyi\InvisibleCaptcha\Model\ReCaptcha\RequestParameters;
use Magento\Framework\HTTP\Client\Curl;

/**
 * Class CurlPost
 */
class CurlPost implements RequestMethodInterface
{
    /**
     * @var Curl
     */
    private $curl;

    /**
     * CurlPost constructor.
     *
     * @param Curl $curl
     */
    public function __construct(
        Curl $curl
    ) {
        $this->curl = $curl;
    }

    /**
     * @inheritDoc
     */
    public function submit(string $url, RequestParameters $params): string
    {
        $this->curl->post($url, $params->toQueryString());

        return $this->curl->getBody();
    }
}
