<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Tester;

use Hryvinskyi\InvisibleCaptcha\Api\ConditionInterface;
use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\ExclusionPolicyInterface;
use Hryvinskyi\InvisibleCaptcha\Api\ExpressionInterface;
use Hryvinskyi\InvisibleCaptcha\Api\ExpressionParserInterface;
use Hryvinskyi\InvisibleCaptcha\Api\ExpressionTracerInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderPoolInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Tester\ActionNameResolverInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Tester\RouteRuleTester;
use Hryvinskyi\InvisibleCaptcha\Model\Tester\Simulation;
use Hryvinskyi\InvisibleCaptcha\Model\Tester\SimulatedFieldPoolFactory;
use Hryvinskyi\InvisibleCaptcha\Model\Tester\SyntheticRequestFactory;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RouteRuleTesterTest extends TestCase
{
    /** @var StoreManagerInterface&MockObject */
    private StoreManagerInterface $storeManager;
    /** @var Emulation&MockObject */
    private Emulation $emulation;
    /** @var ConfigInterface&MockObject */
    private ConfigInterface $config;
    /** @var ProviderPoolInterface&MockObject */
    private ProviderPoolInterface $providerPool;
    /** @var ExclusionPolicyInterface&MockObject */
    private ExclusionPolicyInterface $exclusionPolicy;
    /** @var ExpressionParserInterface&MockObject */
    private ExpressionParserInterface $expressionParser;
    /** @var ExpressionTracerInterface&MockObject */
    private ExpressionTracerInterface $expressionTracer;
    /** @var ActionNameResolverInterface&MockObject */
    private ActionNameResolverInterface $actionNameResolver;
    /** @var SyntheticRequestFactory&MockObject */
    private SyntheticRequestFactory $syntheticRequestFactory;
    /** @var SimulatedFieldPoolFactory&MockObject */
    private SimulatedFieldPoolFactory $simulatedFieldPoolFactory;
    /** @var ExpressionInterface&MockObject */
    private ExpressionInterface $expression;
    private RouteRuleTester $tester;

    /** @var array<int, mixed> Arguments of the last SyntheticRequestFactory::create() call. */
    private array $syntheticCreateArgs = [];
    /** @var array<int, mixed>|null Rows passed into ExpressionParser::parse(). */
    private ?array $parsedRows = null;
    /** @var string|null IP the exclusion policy was asked about. */
    private ?string $checkedIp = null;
    private bool $ipExcluded = false;
    private bool $userAgentExcluded = false;
    /** @var array{route: string, controller: string, action: string, params: array<string, string>, source: string}|null */
    private ?array $resolvedRouteParts = null;
    private bool $routeProtectionEnabled = true;
    /** @var array<string, string> Condition field codes exposed by the parsed expression. */
    private array $expressionFieldCodes = ['uri_path'];

    protected function setUp(): void
    {
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->emulation = $this->createMock(Emulation::class);
        $this->config = $this->createMock(ConfigInterface::class);
        $this->providerPool = $this->createMock(ProviderPoolInterface::class);
        $this->exclusionPolicy = $this->createMock(ExclusionPolicyInterface::class);
        $this->expressionParser = $this->createMock(ExpressionParserInterface::class);
        $this->expressionTracer = $this->createMock(ExpressionTracerInterface::class);
        $this->actionNameResolver = $this->createMock(ActionNameResolverInterface::class);
        $this->syntheticRequestFactory = $this->getMockBuilder(SyntheticRequestFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->simulatedFieldPoolFactory = $this->getMockBuilder(SimulatedFieldPoolFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $store = $this->createMock(Store::class);
        $store->method('getBaseUrl')->willReturn('https://shop.test/');
        $store->method('getCode')->willReturn('default');
        $this->storeManager->method('getStore')->willReturn($store);

        $defaultStore = $this->createMock(StoreInterface::class);
        $defaultStore->method('getId')->willReturn(1);
        $this->storeManager->method('getDefaultStoreView')->willReturn($defaultStore);

        // Record-and-return stubs keep every test's argument assertions unambiguous.
        $this->syntheticRequestFactory->method('create')->willReturnCallback(
            function (...$args): HttpRequest {
                $this->syntheticCreateArgs = $args;

                return $this->createMock(HttpRequest::class);
            }
        );

        $fieldPool = $this->createMock(FieldProviderInterface::class);
        $fieldPool->method('getAll')->willReturn([
            'client_ip' => $this->fieldReturning('9.9.9.9'),
            'action_name' => $this->fieldReturning('checkout_cart_index'),
        ]);
        $this->simulatedFieldPoolFactory->method('create')->willReturn($fieldPool);

        $this->expression = $this->createMock(ExpressionInterface::class);
        $this->expression->method('isEmpty')->willReturn(false);
        $this->expression->method('getConditions')->willReturnCallback(function (): array {
            return array_map(function (string $fieldCode): ConditionInterface {
                $condition = $this->createMock(ConditionInterface::class);
                $condition->method('getFieldCode')->willReturn($fieldCode);

                return $condition;
            }, $this->expressionFieldCodes);
        });
        $this->expressionParser->method('parse')->willReturnCallback(
            function (array $rows): ExpressionInterface {
                $this->parsedRows = $rows;

                return $this->expression;
            }
        );

        $this->expressionTracer->method('trace')->willReturn(['matched' => true, 'groups' => []]);

        $this->exclusionPolicy->method('isIpExcluded')->willReturnCallback(
            function (string $ip): bool {
                $this->checkedIp = $ip;

                return $this->ipExcluded;
            }
        );
        $this->exclusionPolicy->method('isUserAgentExcluded')->willReturnCallback(
            fn (): bool => $this->userAgentExcluded
        );

        $provider = $this->createMock(ProviderInterface::class);
        $provider->method('isConfigured')->willReturn(true);
        $this->providerPool->method('getRouteGateProvider')->willReturn($provider);

        $this->config->method('isRouteProtectionEnabled')->willReturnCallback(
            fn (): bool => $this->routeProtectionEnabled
        );
        $this->config->method('getRouteProviderOverride')->willReturn('');
        $this->config->method('getActiveProvider')->willReturn('turnstile');
        $this->config->method('getProtectionRulesConfig')->willReturn([
            ['combinator' => 'and', 'field' => 'uri_path', 'operator' => 'eq', 'value' => '/checkout/cart'],
        ]);

        $this->actionNameResolver->method('resolve')->willReturnCallback(
            fn (): ?array => $this->resolvedRouteParts
        );

        $this->tester = new RouteRuleTester(
            $this->storeManager,
            $this->emulation,
            $this->config,
            $this->providerPool,
            $this->exclusionPolicy,
            $this->expressionParser,
            $this->expressionTracer,
            $this->actionNameResolver,
            $this->syntheticRequestFactory,
            $this->simulatedFieldPoolFactory
        );
    }

    public function testMatchedRequestWouldBeChallenged(): void
    {
        $this->emulation->expects($this->once())
            ->method('startEnvironmentEmulation')
            ->with(1, 'frontend', true);
        $this->emulation->expects($this->once())->method('stopEnvironmentEmulation');
        $this->resolvedRouteParts = [
            'route' => 'checkout',
            'controller' => 'cart',
            'action' => 'index',
            'params' => [],
            'source' => 'route',
        ];

        $result = $this->tester->test(new Simulation('/checkout/cart'));

        $this->assertTrue($result['matched']);
        $this->assertTrue($result['wouldChallenge']);
        $this->assertSame(
            ['excludedIp' => false, 'excludedUserAgent' => false, 'verifyEndpoint' => false],
            $result['bypass']
        );
        $this->assertSame('turnstile', $result['context']['provider']);
        $this->assertSame('default', $result['context']['store']);
        $this->assertSame('route', $result['context']['actionNameSource']);
        $this->assertFalse($result['context']['usingDraftRules']);
        $this->assertSame('9.9.9.9', $result['fields']['client_ip']);
        $this->assertSame([], $result['warnings']);
    }

    public function testDraftRulesArePreferredOverSavedOnes(): void
    {
        $draft = [['combinator' => 'and', 'field' => 'user_agent', 'operator' => 'contains', 'value' => 'Bot']];
        $this->config->expects($this->never())->method('getProtectionRulesConfig');

        $result = $this->tester->test(new Simulation('/x', 'GET', '', '', '', null, null, $draft));

        $this->assertSame($draft, $this->parsedRows);
        $this->assertTrue($result['context']['usingDraftRules']);
    }

    public function testExcludedUserAgentBlocksTheChallengeButReportsMatch(): void
    {
        $this->userAgentExcluded = true;

        $result = $this->tester->test(new Simulation('/x', 'GET', 'Googlebot/2.1'));

        $this->assertTrue($result['matched']);
        $this->assertFalse($result['wouldChallenge']);
        $this->assertTrue($result['bypass']['excludedUserAgent']);
    }

    public function testExcludedIpUsesTheResolvedClientIp(): void
    {
        $this->ipExcluded = true;

        $result = $this->tester->test(new Simulation('/x'));

        $this->assertSame('9.9.9.9', $this->checkedIp);
        $this->assertTrue($result['bypass']['excludedIp']);
        $this->assertFalse($result['wouldChallenge']);
    }

    public function testDisabledRouteProtectionReportsContext(): void
    {
        $this->routeProtectionEnabled = false;

        $result = $this->tester->test(new Simulation('/x'));

        $this->assertTrue($result['matched']);
        $this->assertFalse($result['wouldChallenge']);
        $this->assertFalse($result['context']['routeProtectionEnabled']);
    }

    public function testVerifyEndpointIsAlwaysBypassed(): void
    {
        $result = $this->tester->test(new Simulation('/invisiblecaptcha/verify'));

        $this->assertTrue($result['bypass']['verifyEndpoint']);
        $this->assertFalse($result['wouldChallenge']);
    }

    public function testAbsoluteUrlPartsReachTheSyntheticRequest(): void
    {
        $this->tester->test(new Simulation('https://other.test:8080/x?y=1'));

        $this->assertSame(
            ['/x', 'y=1', 'other.test:8080', 'GET', '', '', '', null],
            $this->syntheticCreateArgs
        );
    }

    public function testRelativeUrlInheritsTheStoreHost(): void
    {
        $this->tester->test(new Simulation('/lamps?p=2'));

        $this->assertSame(
            ['/lamps', 'p=2', 'shop.test', 'GET', '', '', '', null],
            $this->syntheticCreateArgs
        );
    }

    public function testManualActionNameOverridesResolution(): void
    {
        $this->actionNameResolver->expects($this->never())->method('resolve');

        $result = $this->tester->test(new Simulation('/x', 'GET', '', '', '', 'catalog_product_compare_index'));

        $this->assertSame(
            [
                'route' => 'catalog',
                'controller' => 'product_compare',
                'action' => 'index',
                'params' => [],
                'source' => 'manual',
            ],
            $this->syntheticCreateArgs[7]
        );
        $this->assertSame('manual', $result['context']['actionNameSource']);
    }

    public function testUnresolvedActionNameWarnsWhenRulesUseIt(): void
    {
        $this->expressionFieldCodes = ['action_name'];

        $result = $this->tester->test(new Simulation('/mystery-url'));

        $this->assertNotEmpty($result['warnings']);
        $this->assertStringContainsString('action name', strtolower((string)$result['warnings'][0]));
    }

    public function testNoActionNameWarningWhenRulesDoNotUseIt(): void
    {
        $this->expressionFieldCodes = ['uri_path'];

        $result = $this->tester->test(new Simulation('/mystery-url'));

        $this->assertSame([], $result['warnings']);
    }

    /**
     * Build a field mock resolving to the given value.
     *
     * @param string $value
     * @return FieldInterface&MockObject
     */
    private function fieldReturning(string $value): FieldInterface
    {
        $field = $this->createMock(FieldInterface::class);
        $field->method('getValue')->willReturn($value);

        return $field;
    }
}
