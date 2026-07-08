<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Strategy\Failure;

use Hryvinskyi\InvisibleCaptcha\Api\Strategy\RedirectUrlInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Verification\VerificationResultInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\Response\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\UrlInterface;

/**
 * Failure strategy for standard form posts: adds an error message, stops
 * dispatch and redirects back to a configured URL provider (or the same page).
 */
class Redirect extends AbstractFailure
{
    /**
     * @param MessageManagerInterface $messageManager
     * @param ActionFlag $actionFlag
     * @param UrlInterface $url
     * @param FailureMessages $failureMessages
     * @param RedirectUrlInterface|null $redirectUrlProvider
     */
    public function __construct(
        private readonly MessageManagerInterface $messageManager,
        private readonly ActionFlag $actionFlag,
        private readonly UrlInterface $url,
        FailureMessages $failureMessages,
        private readonly ?RedirectUrlInterface $redirectUrlProvider = null
    ) {
        parent::__construct($failureMessages);
    }

    /**
     * @inheritDoc
     */
    public function execute(VerificationResultInterface $result, ?ResponseInterface $response = null): void
    {
        if (!$response instanceof Http) {
            return;
        }

        $this->messageManager->addErrorMessage($this->getMessagesString($result));
        $this->actionFlag->set('', Action::FLAG_NO_DISPATCH, true);

        $response->setRedirect($this->resolveUrl());
    }

    /**
     * Resolve the redirect URL, falling back to the current action when no
     * provider is configured.
     */
    private function resolveUrl(): string
    {
        if ($this->redirectUrlProvider !== null) {
            return $this->redirectUrlProvider->getRedirectUrl();
        }

        return $this->url->getUrl('*/*/*', ['_current' => true]);
    }
}
