<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Filter\Field;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Api\RobotsTxt\MatcherInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Phrase;

/**
 * Resolves to 1 when the store's robots.txt disallows the requested URL for
 * the requesting user agent, 0 otherwise. Well-behaved crawlers never fetch
 * such URLs, so `robots_txt_blocked eq 1` challenges exactly the clients
 * that ignore robots.txt.
 */
class RobotsTxtBlocked implements FieldInterface
{
    /**
     * @param MatcherInterface $matcher
     * @param RequestInterface $request
     */
    public function __construct(
        private readonly MatcherInterface $matcher,
        private readonly RequestInterface $request
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getCode(): string
    {
        return 'robots_txt_blocked';
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): Phrase
    {
        return __('Blocked by robots.txt');
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return self::TYPE_BOOLEAN;
    }

    /**
     * @inheritDoc
     */
    public function getValue(): int
    {
        $requestUri = (string)$this->request->getRequestUri();
        if ($requestUri === '') {
            return 0;
        }

        $userAgent = (string)$this->request->getHeader('User-Agent');

        return $this->matcher->isDisallowed($requestUri, $userAgent) ? 1 : 0;
    }
}
