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
use Hryvinskyi\InvisibleCaptcha\Exception\HttpClientException;
use Hryvinskyi\InvisibleCaptcha\Model\Verification\Validator\Threshold;
use Hryvinskyi\InvisibleCaptcha\Model\Verification\Validator\ValidatorList;
use Hryvinskyi\InvisibleCaptcha\Model\Verification\Verifier\EnterpriseVerifier;
use Hryvinskyi\InvisibleCaptcha\Model\Verification\VerificationRequest;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class EnterpriseVerifierTest extends TestCase
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
    private EnterpriseVerifier $verifier;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->validatorList = $this->createMock(ValidatorList::class);
        $this->json = $this->createMock(Json::class);
        $this->config = $this->createMock(ConfigInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->verifier = new EnterpriseVerifier(
            $this->httpClient,
            $this->validatorList,
            $this->json,
            $this->config,
            $this->logger
        );
    }

    public function testEmptyTokenFailsWithMissingInputResponse(): void
    {
        $request = new VerificationRequest();
        $request->setSecret('api-key')->setExtra(['siteKey' => 'site-key']);

        $this->httpClient->expects($this->never())->method('post');

        $result = $this->verifier->verify($request);

        $this->assertFalse($result->isSuccess());
        $this->assertContains('missing-input-response', $result->getErrorCodes());
    }

    public function testMissingSiteKeyFailsWithMissingInputSecret(): void
    {
        $request = new VerificationRequest();
        $request->setResponse('token-value')->setSecret('api-key');

        $this->httpClient->expects($this->never())->method('post');

        $result = $this->verifier->verify($request);

        $this->assertFalse($result->isSuccess());
        $this->assertContains('missing-input-secret', $result->getErrorCodes());
    }

    public function testMissingSecretFailsWithMissingInputSecret(): void
    {
        $request = new VerificationRequest();
        $request->setResponse('token-value')->setExtra(['siteKey' => 'site-key']);

        $this->httpClient->expects($this->never())->method('post');

        $result = $this->verifier->verify($request);

        $this->assertFalse($result->isSuccess());
        $this->assertContains('missing-input-secret', $result->getErrorCodes());
    }

    public function testValidAssessmentWithScoreAboveThresholdSucceeds(): void
    {
        $request = new VerificationRequest();
        $request
            ->setResponse('token-value')
            ->setSecret('api-key')
            ->setVerifyUrl('https://recaptchaenterprise.example/assessments?key=api-key')
            ->setScoreThreshold(0.5)
            ->setExtra(['siteKey' => 'site-key', 'projectId' => 'project-id']);

        $this->json->method('serialize')->willReturn('{"event":{}}');
        $this->httpClient->method('post')->willReturn('{"raw":"json"}');
        $this->json->method('unserialize')->willReturn([
            'tokenProperties' => [
                'valid' => true,
                'hostname' => 'example.com',
                'createTime' => '2026-06-25T10:00:00Z',
                'action' => 'login',
            ],
            'riskAnalysis' => ['score' => 0.9],
        ]);
        // Real threshold validator: 0.9 >= 0.5 so it passes.
        $this->validatorList->method('getList')->willReturn([new Threshold()]);

        $result = $this->verifier->verify($request);

        $this->assertTrue($result->isSuccess());
        $this->assertSame([], $result->getErrorCodes());
        $this->assertSame('example.com', $result->getHostname());
        $this->assertSame(0.9, $result->getScore());
        $this->assertSame('login', $result->getAction());
    }

    public function testValidAssessmentWithScoreBelowThresholdFails(): void
    {
        $request = new VerificationRequest();
        $request
            ->setResponse('token-value')
            ->setSecret('api-key')
            ->setVerifyUrl('https://recaptchaenterprise.example/assessments?key=api-key')
            ->setScoreThreshold(0.5)
            ->setExtra(['siteKey' => 'site-key']);

        $this->json->method('serialize')->willReturn('{"event":{}}');
        $this->httpClient->method('post')->willReturn('{"raw":"json"}');
        $this->json->method('unserialize')->willReturn([
            'tokenProperties' => ['valid' => true],
            'riskAnalysis' => ['score' => 0.1],
        ]);
        $this->validatorList->method('getList')->willReturn([new Threshold()]);

        $result = $this->verifier->verify($request);

        $this->assertFalse($result->isSuccess());
        $this->assertContains(Threshold::ERROR_CODE, $result->getErrorCodes());
    }

    #[DataProvider('invalidReasonProvider')]
    public function testInvalidTokenMapsToErrorCode(string $invalidReason, string $expectedCode): void
    {
        $request = new VerificationRequest();
        $request
            ->setResponse('token-value')
            ->setSecret('api-key')
            ->setVerifyUrl('https://recaptchaenterprise.example/assessments?key=api-key')
            ->setExtra(['siteKey' => 'site-key']);

        $this->json->method('serialize')->willReturn('{"event":{}}');
        $this->httpClient->method('post')->willReturn('{"raw":"json"}');
        $this->json->method('unserialize')->willReturn([
            'tokenProperties' => ['valid' => false, 'invalidReason' => $invalidReason],
        ]);
        $this->validatorList->method('getList')->willReturn([]);

        $result = $this->verifier->verify($request);

        $this->assertFalse($result->isSuccess());
        $this->assertContains($expectedCode, $result->getErrorCodes());
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function invalidReasonProvider(): array
    {
        return [
            'expired token' => ['EXPIRED', 'timeout-or-duplicate'],
            'duplicate token' => ['DUPE', 'timeout-or-duplicate'],
            'malformed token' => ['MALFORMED', 'invalid-input-response'],
            'missing token' => ['MISSING', 'missing-input-response'],
            'browser error' => ['BROWSER_ERROR', 'bad-request'],
            'unknown reason falls back' => ['SOMETHING_ELSE', 'invalid-input-response'],
        ];
    }

    public function testTransportErrorFailsWithConnectionFailed(): void
    {
        $request = new VerificationRequest();
        $request
            ->setResponse('token-value')
            ->setSecret('api-key')
            ->setVerifyUrl('https://recaptchaenterprise.example/assessments?key=api-key')
            ->setExtra(['siteKey' => 'site-key']);

        $this->json->method('serialize')->willReturn('{"event":{}}');
        $this->httpClient->method('post')->willThrowException(new HttpClientException('boom'));
        $this->json->expects($this->never())->method('unserialize');

        $result = $this->verifier->verify($request);

        $this->assertFalse($result->isSuccess());
        $this->assertContains('connection-failed', $result->getErrorCodes());
    }

    public function testInvalidJsonResponseFails(): void
    {
        $request = new VerificationRequest();
        $request
            ->setResponse('token-value')
            ->setSecret('api-key')
            ->setVerifyUrl('https://recaptchaenterprise.example/assessments?key=api-key')
            ->setExtra(['siteKey' => 'site-key']);

        $this->json->method('serialize')->willReturn('{"event":{}}');
        $this->httpClient->method('post')->willReturn('not-json');
        $this->json->method('unserialize')->willThrowException(new \InvalidArgumentException('bad json'));

        $result = $this->verifier->verify($request);

        $this->assertFalse($result->isSuccess());
        $this->assertContains('invalid-json', $result->getErrorCodes());
    }
}
