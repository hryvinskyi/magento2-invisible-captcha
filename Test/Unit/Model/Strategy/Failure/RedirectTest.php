<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Strategy\Failure;

use Hryvinskyi\InvisibleCaptcha\Api\Strategy\RedirectUrlInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Verification\VerificationResultInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Strategy\Failure\FailureMessages;
use Hryvinskyi\InvisibleCaptcha\Model\Strategy\Failure\Redirect;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RedirectTest extends TestCase
{
    /** @var MessageManagerInterface&MockObject */
    private MessageManagerInterface $messageManager;
    /** @var ActionFlag&MockObject */
    private ActionFlag $actionFlag;
    /** @var UrlInterface&MockObject */
    private UrlInterface $url;
    /** @var FailureMessages&MockObject */
    private FailureMessages $failureMessages;

    protected function setUp(): void
    {
        $this->messageManager = $this->createMock(MessageManagerInterface::class);
        $this->actionFlag = $this->createMock(ActionFlag::class);
        $this->url = $this->createMock(UrlInterface::class);
        $this->failureMessages = $this->createMock(FailureMessages::class);
    }

    public function testRedirectsToProviderUrlWhenProviderSet(): void
    {
        $this->stubMessage('invalid-input-response', 'Token invalid.');

        $provider = $this->createMock(RedirectUrlInterface::class);
        $provider->method('getRedirectUrl')->willReturn('https://example.com/provider');

        $this->messageManager->expects($this->once())
            ->method('addErrorMessage')
            ->with('Token invalid.');
        $this->actionFlag->expects($this->once())
            ->method('set')
            ->with('', Action::FLAG_NO_DISPATCH, true);
        $this->url->expects($this->never())->method('getUrl');

        $response = $this->createMock(HttpResponse::class);
        $response->expects($this->once())
            ->method('setRedirect')
            ->with('https://example.com/provider');

        $strategy = new Redirect(
            $this->messageManager,
            $this->actionFlag,
            $this->url,
            $this->failureMessages,
            $provider
        );

        $strategy->execute($this->makeResult(['invalid-input-response']), $response);
    }

    public function testFallsBackToCurrentUrlWhenNoProvider(): void
    {
        $this->stubMessage('invalid-input-response', 'Token invalid.');

        $this->url->expects($this->once())
            ->method('getUrl')
            ->with('*/*/*', ['_current' => true])
            ->willReturn('https://example.com/current');

        $this->messageManager->expects($this->once())->method('addErrorMessage')->with('Token invalid.');
        $this->actionFlag->expects($this->once())->method('set')->with('', Action::FLAG_NO_DISPATCH, true);

        $response = $this->createMock(HttpResponse::class);
        $response->expects($this->once())
            ->method('setRedirect')
            ->with('https://example.com/current');

        $strategy = new Redirect(
            $this->messageManager,
            $this->actionFlag,
            $this->url,
            $this->failureMessages
        );

        $strategy->execute($this->makeResult(['invalid-input-response']), $response);
    }

    public function testNoOpWhenResponseNotHttp(): void
    {
        $this->messageManager->expects($this->never())->method('addErrorMessage');
        $this->actionFlag->expects($this->never())->method('set');
        $this->url->expects($this->never())->method('getUrl');

        $strategy = new Redirect(
            $this->messageManager,
            $this->actionFlag,
            $this->url,
            $this->failureMessages
        );

        $strategy->execute($this->makeResult(['invalid-input-response']), $this->createMock(ResponseInterface::class));
    }

    public function testNoOpWhenResponseNull(): void
    {
        $this->messageManager->expects($this->never())->method('addErrorMessage');
        $this->actionFlag->expects($this->never())->method('set');

        $strategy = new Redirect(
            $this->messageManager,
            $this->actionFlag,
            $this->url,
            $this->failureMessages
        );

        $strategy->execute($this->makeResult(['invalid-input-response']), null);
    }

    /**
     * @param string[] $codes
     * @return VerificationResultInterface&MockObject
     */
    private function makeResult(array $codes): VerificationResultInterface
    {
        $result = $this->createMock(VerificationResultInterface::class);
        $result->method('getErrorCodes')->willReturn($codes);

        return $result;
    }

    private function stubMessage(string $code, string $message): void
    {
        $this->failureMessages->method('hasErrorMessage')->willReturnMap([[$code, true]]);
        $this->failureMessages->method('getErrorMessage')->willReturnMap([[$code, $message]]);
    }
}
