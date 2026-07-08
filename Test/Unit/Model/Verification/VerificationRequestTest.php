<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Verification;

use Hryvinskyi\InvisibleCaptcha\Model\Verification\VerificationRequest;
use PHPUnit\Framework\TestCase;

class VerificationRequestTest extends TestCase
{
    private VerificationRequest $request;

    protected function setUp(): void
    {
        $this->request = new VerificationRequest();
    }

    public function testDefaults(): void
    {
        $this->assertSame('', $this->request->getSecret());
        $this->assertSame('', $this->request->getResponse());
        $this->assertSame('', $this->request->getVerifyUrl());
        $this->assertNull($this->request->getRemoteIp());
        $this->assertNull($this->request->getExpectedAction());
        $this->assertNull($this->request->getExpectedHostname());
        $this->assertNull($this->request->getScoreThreshold());
        $this->assertNull($this->request->getChallengeTimeout());
        $this->assertSame([], $this->request->getExtra());
    }

    public function testFluentSettersReturnSameInstance(): void
    {
        $this->assertSame($this->request, $this->request->setSecret('s'));
        $this->assertSame($this->request, $this->request->setResponse('r'));
        $this->assertSame($this->request, $this->request->setRemoteIp('1.2.3.4'));
        $this->assertSame($this->request, $this->request->setVerifyUrl('https://verify'));
        $this->assertSame($this->request, $this->request->setExpectedAction('login'));
        $this->assertSame($this->request, $this->request->setExpectedHostname('example.com'));
        $this->assertSame($this->request, $this->request->setScoreThreshold(0.5));
        $this->assertSame($this->request, $this->request->setChallengeTimeout(120));
        $this->assertSame($this->request, $this->request->setExtra(['k' => 'v']));
    }

    public function testSettersAndGettersRoundTrip(): void
    {
        $this->request
            ->setSecret('secret-value')
            ->setResponse('token-value')
            ->setRemoteIp('203.0.113.7')
            ->setVerifyUrl('https://siteverify.example/verify')
            ->setExpectedAction('checkout')
            ->setExpectedHostname('shop.example.com')
            ->setScoreThreshold(0.75)
            ->setChallengeTimeout(300);

        $this->assertSame('secret-value', $this->request->getSecret());
        $this->assertSame('token-value', $this->request->getResponse());
        $this->assertSame('203.0.113.7', $this->request->getRemoteIp());
        $this->assertSame('https://siteverify.example/verify', $this->request->getVerifyUrl());
        $this->assertSame('checkout', $this->request->getExpectedAction());
        $this->assertSame('shop.example.com', $this->request->getExpectedHostname());
        $this->assertSame(0.75, $this->request->getScoreThreshold());
        $this->assertSame(300, $this->request->getChallengeTimeout());
    }

    public function testNullableSettersAcceptNull(): void
    {
        $this->request->setRemoteIp('1.2.3.4')->setRemoteIp(null);
        $this->request->setExpectedAction('login')->setExpectedAction(null);
        $this->request->setExpectedHostname('a')->setExpectedHostname(null);
        $this->request->setScoreThreshold(0.5)->setScoreThreshold(null);
        $this->request->setChallengeTimeout(10)->setChallengeTimeout(null);

        $this->assertNull($this->request->getRemoteIp());
        $this->assertNull($this->request->getExpectedAction());
        $this->assertNull($this->request->getExpectedHostname());
        $this->assertNull($this->request->getScoreThreshold());
        $this->assertNull($this->request->getChallengeTimeout());
    }

    public function testExtraRoundTrip(): void
    {
        $extra = ['siteKey' => 'site-key', 'projectId' => 'project-id'];
        $this->request->setExtra($extra);

        $this->assertSame($extra, $this->request->getExtra());
    }

    public function testGetExtraValueReturnsStoredValue(): void
    {
        $this->request->setExtra(['siteKey' => 'abc']);

        $this->assertSame('abc', $this->request->getExtraValue('siteKey'));
    }

    public function testGetExtraValueReturnsDefaultWhenMissing(): void
    {
        $this->assertNull($this->request->getExtraValue('missing'));
        $this->assertSame('fallback', $this->request->getExtraValue('missing', 'fallback'));
    }
}
