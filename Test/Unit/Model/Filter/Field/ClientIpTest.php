<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Filter\Field;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldValueHintInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Http\ClientIpResolverInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Field\ClientIp;
use Magento\Framework\App\RequestInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ClientIpTest extends TestCase
{
    /** @var RequestInterface&MockObject */
    private RequestInterface $request;
    /** @var ClientIpResolverInterface&MockObject */
    private ClientIpResolverInterface $clientIpResolver;
    private ClientIp $field;

    protected function setUp(): void
    {
        $this->request = $this->createMock(RequestInterface::class);
        $this->clientIpResolver = $this->createMock(ClientIpResolverInterface::class);
        $this->field = new ClientIp($this->request, $this->clientIpResolver);
    }

    public function testMetadata(): void
    {
        $this->assertSame('client_ip', $this->field->getCode());
        $this->assertSame('Client IP', (string)$this->field->getLabel());
        $this->assertSame(FieldInterface::TYPE_STRING, $this->field->getType());
    }

    public function testGetValueDelegatesToResolverWithItsOwnRequest(): void
    {
        // The field must resolve against the request it was constructed with —
        // the seam the rule tester's SimulatedFieldPoolFactory substitutes.
        $this->clientIpResolver->expects($this->once())
            ->method('resolveFrom')
            ->with($this->identicalTo($this->request))
            ->willReturn('1.2.3.4');

        $this->assertSame('1.2.3.4', $this->field->getValue());
    }

    public function testExposesValueHintForTheRulesEditor(): void
    {
        $this->assertInstanceOf(FieldValueHintInterface::class, $this->field);

        $hint = $this->field->getValueHint();
        $this->assertSame('^(\\d{1,3}(\\.\\d{1,3}){3}|[0-9A-Fa-f:]*:[0-9A-Fa-f:]*)$', $hint['pattern']);
        $this->assertSame('203.0.113.10', $hint['placeholder']);
        $this->assertArrayHasKey('message', $hint);
    }
}
