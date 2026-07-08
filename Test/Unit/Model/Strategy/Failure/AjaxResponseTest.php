<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Strategy\Failure;

use Hryvinskyi\InvisibleCaptcha\Api\Verification\VerificationResultInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Strategy\Failure\AjaxResponse;
use Hryvinskyi\InvisibleCaptcha\Model\Strategy\Failure\FailureMessages;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Json\EncoderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AjaxResponseTest extends TestCase
{
    /** @var ActionFlag&MockObject */
    private ActionFlag $actionFlag;
    /** @var EncoderInterface&MockObject */
    private EncoderInterface $encoder;
    /** @var FailureMessages&MockObject */
    private FailureMessages $failureMessages;
    private AjaxResponse $strategy;

    protected function setUp(): void
    {
        $this->actionFlag = $this->createMock(ActionFlag::class);
        $this->encoder = $this->createMock(EncoderInterface::class);
        $this->failureMessages = $this->createMock(FailureMessages::class);

        $this->strategy = new AjaxResponse(
            $this->actionFlag,
            $this->encoder,
            $this->failureMessages
        );
    }

    public function testStopsDispatchAndRepresentsJsonErrors(): void
    {
        $result = $this->createMock(VerificationResultInterface::class);
        $result->method('getErrorCodes')->willReturn(['missing-input-response', 'invalid-input-response']);

        $this->failureMessages->method('hasErrorMessage')->willReturnMap([
            ['missing-input-response', true],
            ['invalid-input-response', true],
        ]);
        $this->failureMessages->method('getErrorMessage')->willReturnMap([
            ['missing-input-response', 'Token missing.'],
            ['invalid-input-response', 'Token invalid.'],
        ]);

        $this->actionFlag->expects($this->once())
            ->method('set')
            ->with('', Action::FLAG_NO_DISPATCH, true);

        $this->encoder->expects($this->once())
            ->method('encode')
            ->with([
                'errors' => true,
                'message' => 'Token missing.<br>Token invalid.',
            ])
            ->willReturn('{"errors":true,"message":"Token missing.<br>Token invalid."}');

        $response = $this->createMock(HttpResponse::class);
        $response->expects($this->once())
            ->method('representJson')
            ->with('{"errors":true,"message":"Token missing.<br>Token invalid."}');

        $this->strategy->execute($result, $response);
    }

    public function testFallsBackToUnknownErrorWhenNoCodesMatch(): void
    {
        $result = $this->createMock(VerificationResultInterface::class);
        $result->method('getErrorCodes')->willReturn(['some-unmapped-code']);

        $this->failureMessages->method('hasErrorMessage')->willReturnMap([
            ['some-unmapped-code', false],
            ['unknown-error', true],
        ]);
        $this->failureMessages->method('getErrorMessage')->willReturnMap([
            ['unknown-error', 'Unexpected error.'],
        ]);

        $this->encoder->expects($this->once())
            ->method('encode')
            ->with([
                'errors' => true,
                'message' => 'Unexpected error.',
            ])
            ->willReturn('encoded');

        $response = $this->createMock(HttpResponse::class);
        $response->expects($this->once())->method('representJson')->with('encoded');

        $this->strategy->execute($result, $response);
    }

    public function testNoOpWhenResponseNotHttp(): void
    {
        $result = $this->createMock(VerificationResultInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $this->actionFlag->expects($this->never())->method('set');
        $this->encoder->expects($this->never())->method('encode');

        $this->strategy->execute($result, $response);
    }

    public function testNoOpWhenResponseNull(): void
    {
        $result = $this->createMock(VerificationResultInterface::class);

        $this->actionFlag->expects($this->never())->method('set');
        $this->encoder->expects($this->never())->method('encode');

        $this->strategy->execute($result, null);
    }
}
