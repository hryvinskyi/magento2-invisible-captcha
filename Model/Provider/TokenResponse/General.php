<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\InvisibleCaptcha\Model\Provider\TokenResponse;

use Hryvinskyi\InvisibleCaptcha\Model\Provider\TokenResponseInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface;

class General implements TokenResponseInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * DefaultResponseProvider constructor.
     *
     * @param RequestInterface $request
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        RequestInterface $request,
        ManagerInterface $messageManager
    ) {
        $this->request = $request;
        $this->messageManager = $messageManager;
    }

    /**
     * @inheritDoc
     */
    public function getToken(): ?string
    {
        return $this->request->getParam('hryvinskyi_invisible_token');
    }
}
