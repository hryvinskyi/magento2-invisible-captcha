<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Controller\Adminhtml\Tester;

use Hryvinskyi\InvisibleCaptcha\Api\Tester\RouteRuleTesterInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Tester\SimulationInterface;
use Hryvinskyi\InvisibleCaptcha\Controller\Adminhtml\Tester\Run;
use Hryvinskyi\InvisibleCaptcha\Model\Tester\Simulation;
use Hryvinskyi\InvisibleCaptcha\Model\Tester\SimulationFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class RunTest extends TestCase
{
    /** @var HttpRequest&MockObject */
    private HttpRequest $request;
    /** @var RouteRuleTesterInterface&MockObject */
    private RouteRuleTesterInterface $tester;
    /** @var Json&MockObject */
    private Json $json;
    /** @var LoggerInterface&MockObject */
    private LoggerInterface $logger;
    private Run $controller;

    /** @var array<string, mixed>|null Data passed into the JSON result. */
    private ?array $jsonData = null;
    /** @var SimulationInterface|null Simulation the tester received. */
    private ?SimulationInterface $capturedSimulation = null;

    protected function setUp(): void
    {
        $this->request = $this->createMock(HttpRequest::class);
        $context = $this->createMock(Context::class);
        $context->method('getRequest')->willReturn($this->request);

        $this->tester = $this->createMock(RouteRuleTesterInterface::class);
        $this->tester->method('test')->willReturnCallback(
            function (SimulationInterface $simulation): array {
                $this->capturedSimulation = $simulation;

                return ['matched' => true, 'wouldChallenge' => true];
            }
        );

        $simulationFactory = $this->getMockBuilder(SimulationFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $simulationFactory->method('create')->willReturnCallback(
            static fn (array $data): Simulation => new Simulation(
                $data['url'],
                $data['method'],
                $data['userAgent'],
                $data['clientIp'],
                $data['referer'],
                $data['actionName'],
                $data['storeId'],
                $data['draftRules']
            )
        );

        $this->json = $this->createMock(Json::class);
        $this->json->method('setData')->willReturnCallback(
            function (array $data): Json {
                $this->jsonData = $data;

                return $this->json;
            }
        );
        $jsonFactory = $this->createMock(JsonFactory::class);
        $jsonFactory->method('create')->willReturn($this->json);

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->controller = new Run(
            $context,
            $this->tester,
            $simulationFactory,
            $jsonFactory,
            new JsonSerializer(),
            $this->logger
        );
    }

    public function testRunsTheSimulationFromRequestParams(): void
    {
        $this->stubParams([
            'url' => ' /checkout/cart ',
            'method' => 'post',
            'user_agent' => 'Bot/1.0',
            'client_ip' => ' 1.2.3.4 ',
            'referer' => 'https://ref.test/',
            'action_name' => '',
            'store_id' => '2',
            'rules' => '[{"combinator":"and","field":"uri_path","operator":"eq","value":"/x"}]',
        ]);

        $result = $this->controller->execute();

        $this->assertSame($this->json, $result);
        $this->assertTrue($this->jsonData['ok']);
        $this->assertTrue($this->jsonData['matched']);
        $this->assertTrue($this->jsonData['wouldChallenge']);

        $this->assertNotNull($this->capturedSimulation);
        $this->assertSame('/checkout/cart', $this->capturedSimulation->getUrl());
        $this->assertSame('post', $this->capturedSimulation->getMethod());
        $this->assertSame('Bot/1.0', $this->capturedSimulation->getUserAgent());
        $this->assertSame('1.2.3.4', $this->capturedSimulation->getClientIp());
        $this->assertSame('https://ref.test/', $this->capturedSimulation->getReferer());
        $this->assertNull($this->capturedSimulation->getActionName());
        $this->assertSame(2, $this->capturedSimulation->getStoreId());
        $this->assertSame(
            [['combinator' => 'and', 'field' => 'uri_path', 'operator' => 'eq', 'value' => '/x']],
            $this->capturedSimulation->getDraftRules()
        );
    }

    public function testMissingUrlShortCircuits(): void
    {
        $this->stubParams(['url' => '   ']);

        $this->controller->execute();

        $this->assertNull($this->capturedSimulation);
        $this->assertFalse($this->jsonData['ok']);
        $this->assertNotEmpty($this->jsonData['message']);
    }

    public function testEmptyStoreAndRulesBecomeNull(): void
    {
        $this->stubParams(['url' => '/x', 'store_id' => '', 'rules' => '']);

        $this->controller->execute();

        $this->assertNull($this->capturedSimulation->getStoreId());
        $this->assertNull($this->capturedSimulation->getDraftRules());
    }

    public function testMalformedRulesJsonFallsBackToSavedRules(): void
    {
        $this->stubParams(['url' => '/x', 'rules' => '{not json']);

        $this->controller->execute();

        $this->assertNull($this->capturedSimulation->getDraftRules());
    }

    public function testTesterFailureIsLoggedAndReported(): void
    {
        $this->stubParams(['url' => '/x']);
        $tester = $this->createMock(RouteRuleTesterInterface::class);
        $tester->method('test')->willThrowException(new \RuntimeException('boom'));
        $this->logger->expects($this->once())->method('error');

        $context = $this->createMock(Context::class);
        $context->method('getRequest')->willReturn($this->request);
        $simulationFactory = $this->getMockBuilder(SimulationFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $simulationFactory->method('create')->willReturn(new Simulation('/x'));
        $jsonFactory = $this->createMock(JsonFactory::class);
        $jsonFactory->method('create')->willReturn($this->json);

        $controller = new Run(
            $context,
            $tester,
            $simulationFactory,
            $jsonFactory,
            new JsonSerializer(),
            $this->logger
        );
        $controller->execute();

        $this->assertFalse($this->jsonData['ok']);
        $this->assertStringContainsString('boom', $this->jsonData['message']);
    }

    /**
     * Stub request params; unspecified keys resolve to ''.
     *
     * @param array<string, string> $params
     */
    private function stubParams(array $params): void
    {
        $this->request->method('getParam')->willReturnCallback(
            static fn (string $key) => $params[$key] ?? ''
        );
    }
}
