<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\InvisibleCaptcha\Model\Provider\TokenResponse;

use Exception;
use Hryvinskyi\Base\Helper\Json;
use Hryvinskyi\InvisibleCaptcha\Model\Provider\TokenResponseInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface;

class Ajax implements TokenResponseInterface
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
     * Ajax constructor.
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
     * Return token
     *
     * @return string|null
     */
    public function getToken(): ?string
    {
        $token = null;
        $content = $this->request->getContent();

        if ($content) {
            try {
                $params = Json::decode($content);

                if (isset($params['hryvinskyi_invisible_token'])) {
                    $token = $params['hryvinskyi_invisible_token'];
                }
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage(__('Not found invisible captcha token'));
            }
        }

        return $token;
    }
}
