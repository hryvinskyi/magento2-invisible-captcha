<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Verification\Verifier;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Verification\HttpClientInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Verification\Validator\ValidatorInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Verification\VerificationRequestInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Verification\VerificationResultInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Verification\Validator\ValidatorList;
use Hryvinskyi\InvisibleCaptcha\Model\Verification\Verifier\SiteVerifyVerifier;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SiteVerifyVerifierTest extends TestCase
{
    /** @var HttpClientInterface&MockObject */
    private HttpClientInterface $httpClient;
    /** @var ValidatorList&MockObject */
    private ValidatorList $validatorList;
    /** @var Json&MockObject */
    private Json $json;
    /** @var ConfigInterface&MockObject */
    private ConfigInterface $config;
    /** @var LoggerInterface&MockObject */
    private LoggerInterface $logger;
    private SiteVerifyVerifier $verifier;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->validatorList = $this->createMock(ValidatorList::class);
        $this->json = $this->createMock(Json::class);
        $this->config = $this->createMock(ConfigInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->verifier = new SiteVerifyVerifier(
            $this->httpClient,
            $this->validatorList,
            $this->json,
            $this->config,
            $this->logger,
            'SiteVerify'
        );
    }

    public function testEmptyTokenFailsWithMissingInputResponse(): void
    {
        $request = $this->createMock(VerificationRequestInterface::class);
        $request->method('getResponse')->willReturn('');

        // No outbound call should be made when the token is empty.
        $this->httpClient->expects($this->never())->method('post');
        $this->json->expects($this->never())->method('unserialize');

        $result = $this->verifier->verify($request);

        $this->assertFalse($result->isSuccess());
        $this->assertContains('missing-input-response', $result->getErrorCodes());
    }

    public function testScoreBelowThresholdIsNotSuccess(): void
    {
        $request = $this->createMock(VerificationRequestInterface::class);
        $request->method('getResponse')->willReturn('token-value');
        $request->method('getSecret')->willReturn('secret-value');
        $request->method('getVerifyUrl')->willReturn('https://siteverify.example/verify');
        $request->method('getRemoteIp')->willReturn(null);
        $request->method('getScoreThreshold')->willReturn(0.5);

        $this->httpClient->method('post')->willReturn('{"success":true,"score":0.1}');
        $this->json->method('unserialize')->willReturn(['success' => true, 'score' => 0.1]);
        $this->validatorList->method('getList')->willReturn([$this->thresholdValidator()]);

        $result = $this->verifier->verify($request);

        $this->assertFalse($result->isSuccess());
        $this->assertContains('score-threshold-not-met', $result->getErrorCodes());
    }

    public function testSuccessWithoutScoreIsSuccess(): void
    {
        $request = $this->createMock(VerificationRequestInterface::class);
        $request->method('getResponse')->willReturn('token-value');
        $request->method('getSecret')->willReturn('secret-value');
        $request->method('getVerifyUrl')->willReturn('https://siteverify.example/verify');
        $request->method('getRemoteIp')->willReturn(null);
        $request->method('getScoreThreshold')->willReturn(null);

        // Turnstile-style response: success with no score field.
        $this->httpClient->method('post')->willReturn('{"success":true}');
        $this->json->method('unserialize')->willReturn(['success' => true]);
        // The same threshold validator no-ops when no score is present.
        $this->validatorList->method('getList')->willReturn([$this->thresholdValidator()]);

        $result = $this->verifier->verify($request);

        $this->assertTrue($result->isSuccess());
        $this->assertSame([], $result->getErrorCodes());
    }

    /**
     * A self-gating threshold validator: fails when the result carries a score
     * below the request's expected threshold, otherwise passes.
     *
     * @return ValidatorInterface&MockObject
     */
    private function thresholdValidator(): ValidatorInterface
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturnCallback(
            static function (
                VerificationRequestInterface $request,
                VerificationResultInterface $result
            ): ?string {
                $threshold = $request->getScoreThreshold();
                $score = $result->getScore();
                if ($threshold === null || $score === null) {
                    return null;
                }

                return $score < $threshold ? 'score-threshold-not-met' : null;
            }
        );

        return $validator;
    }
}
