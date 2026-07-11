<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Tester;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Field\UriPath;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\FieldProvider;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\FieldProviderFactory;
use Hryvinskyi\InvisibleCaptcha\Model\Tester\SimulatedFieldPoolFactory;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;

class SimulatedFieldPoolFactoryTest extends TestCase
{
    public function testRebuildsEveryFieldAroundTheSimulatedRequest(): void
    {
        $liveRequest = $this->createMock(HttpRequest::class);
        $liveRequest->method('getRequestUri')->willReturn('/live');
        $simulatedRequest = $this->createMock(HttpRequest::class);
        $simulatedRequest->method('getRequestUri')->willReturn('/simulated');

        $liveField = new UriPath($liveRequest);
        $liveProvider = $this->createMock(FieldProviderInterface::class);
        $liveProvider->method('getAll')->willReturn(['uri_path' => $liveField]);

        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->expects($this->once())
            ->method('create')
            ->with(UriPath::class, ['request' => $simulatedRequest])
            ->willReturn(new UriPath($simulatedRequest));

        $fieldProviderFactory = $this->getMockBuilder(FieldProviderFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $fieldProviderFactory->method('create')->willReturnCallback(
            static fn (array $data): FieldProvider => new FieldProvider($data['fields'])
        );

        $factory = new SimulatedFieldPoolFactory($objectManager, $liveProvider, $fieldProviderFactory);
        $pool = $factory->create($simulatedRequest);

        $this->assertSame('/simulated', $pool->get('uri_path')->getValue());
        $this->assertSame('/live', $liveField->getValue());
    }
}
