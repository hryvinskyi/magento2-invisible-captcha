<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Tester;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\ExclusionPolicyInterface;
use Hryvinskyi\InvisibleCaptcha\Api\ExpressionInterface;
use Hryvinskyi\InvisibleCaptcha\Api\ExpressionParserInterface;
use Hryvinskyi\InvisibleCaptcha\Api\ExpressionTracerInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderPoolInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Tester\ActionNameResolverInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Tester\RouteRuleTesterInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Tester\SimulationInterface;
use Hryvinskyi\InvisibleCaptcha\Controller\Router\VerificationRouter;
use Magento\Framework\App\Area;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class RouteRuleTester implements RouteRuleTesterInterface
{
    /**
     * @param StoreManagerInterface $storeManager
     * @param Emulation $emulation
     * @param ConfigInterface $config
     * @param ProviderPoolInterface $providerPool
     * @param ExclusionPolicyInterface $exclusionPolicy
     * @param ExpressionParserInterface $expressionParser
     * @param ExpressionTracerInterface $expressionTracer
     * @param ActionNameResolverInterface $actionNameResolver
     * @param SyntheticRequestFactory $syntheticRequestFactory
     * @param SimulatedFieldPoolFactory $simulatedFieldPoolFactory
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly Emulation $emulation,
        private readonly ConfigInterface $config,
        private readonly ProviderPoolInterface $providerPool,
        private readonly ExclusionPolicyInterface $exclusionPolicy,
        private readonly ExpressionParserInterface $expressionParser,
        private readonly ExpressionTracerInterface $expressionTracer,
        private readonly ActionNameResolverInterface $actionNameResolver,
        private readonly SyntheticRequestFactory $syntheticRequestFactory,
        private readonly SimulatedFieldPoolFactory $simulatedFieldPoolFactory
    ) {
    }

    /**
     * @inheritDoc
     *
     * The whole run executes under frontend environment emulation for the
     * target store, so every scope-sensitive read — rules, exclusion lists,
     * provider credentials, robots.txt — resolves exactly as it would on the
     * live storefront request.
     */
    public function test(SimulationInterface $simulation): array
    {
        $storeId = $simulation->getStoreId() ?? $this->resolveDefaultStoreId();

        $this->emulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
        try {
            return $this->run($simulation, $storeId);
        } finally {
            $this->emulation->stopEnvironmentEmulation();
        }
    }

    /**
     * Execute the simulation inside the emulated store environment.
     *
     * @param SimulationInterface $simulation
     * @param int $storeId
     * @return array<string, mixed>
     */
    private function run(SimulationInterface $simulation, int $storeId): array
    {
        $warnings = [];

        [$host, $path, $query] = $this->parseUrl($simulation->getUrl());
        $routeParts = $this->resolveRouteParts($simulation, $path, $storeId);

        $request = $this->syntheticRequestFactory->create(
            $path,
            $query,
            $host,
            $simulation->getMethod(),
            $simulation->getUserAgent(),
            $simulation->getClientIp(),
            $simulation->getReferer(),
            $routeParts
        );
        $fieldPool = $this->simulatedFieldPoolFactory->create($request);

        $rows = $simulation->getDraftRules() ?? $this->config->getProtectionRulesConfig();
        $expression = $this->expressionParser->parse($rows);
        $this->collectRuleWarnings($rows, $expression, $routeParts, $warnings);

        $trace = $this->expressionTracer->trace($expression, $fieldPool);
        $fields = $this->snapshotFieldValues($fieldPool);

        $clientIp = (string)($fields['client_ip'] ?? $simulation->getClientIp());
        $bypass = [
            'excludedIp' => $this->exclusionPolicy->isIpExcluded($clientIp),
            'excludedUserAgent' => $this->exclusionPolicy->isUserAgentExcluded($simulation->getUserAgent()),
            'verifyEndpoint' => trim($path, '/') === VerificationRouter::VERIFY_PATH,
        ];

        $enabled = $this->config->isRouteProtectionEnabled();
        $providerConfigured = $this->isRouteGateProviderConfigured();

        $matched = (bool)$trace['matched'];
        $wouldChallenge = $matched
            && $enabled
            && $providerConfigured
            && !$bypass['excludedIp']
            && !$bypass['excludedUserAgent']
            && !$bypass['verifyEndpoint'];

        return [
            'matched' => $matched,
            'wouldChallenge' => $wouldChallenge,
            'bypass' => $bypass,
            'context' => [
                'storeId' => $storeId,
                'store' => $this->resolveStoreCode(),
                'routeProtectionEnabled' => $enabled,
                'providerConfigured' => $providerConfigured,
                'provider' => $this->config->getRouteProviderOverride() ?: $this->config->getActiveProvider(),
                'requestUri' => $path . ($query !== '' ? '?' . $query : ''),
                'actionNameSource' => $routeParts['source'] ?? null,
                'usingDraftRules' => $simulation->getDraftRules() !== null,
            ],
            'fields' => $fields,
            'groups' => $trace['groups'],
            'warnings' => $warnings,
        ];
    }

    /**
     * Split the simulated URL into host, path and query string. A bare path
     * inherits the emulated store's base-URL host.
     *
     * @param string $url
     * @return array{0: string, 1: string, 2: string} [host, path, query]
     */
    private function parseUrl(string $url): array
    {
        $url = trim($url);

        if (str_contains($url, '://')) {
            $parts = parse_url($url) ?: [];
            $host = strtolower((string)($parts['host'] ?? ''));
            if (isset($parts['port'])) {
                $host .= ':' . $parts['port'];
            }
            $path = (string)($parts['path'] ?? '/');
            $query = (string)($parts['query'] ?? '');
        } else {
            $host = $this->resolveStoreHost();
            $queryStart = strpos($url, '?');
            $path = $queryStart === false ? $url : substr($url, 0, $queryStart);
            $query = $queryStart === false ? '' : substr($url, $queryStart + 1);
        }

        if ($path === '' || $path[0] !== '/') {
            $path = '/' . $path;
        }

        return [$host, $path, $query];
    }

    /**
     * Determine the dispatched route parts: a manual action name wins,
     * otherwise the URL is resolved via rewrites / route config.
     *
     * @param SimulationInterface $simulation
     * @param string $path
     * @param int $storeId
     * @return array{route: string, controller: string, action: string, params: array<string, string>, source: string}|null
     */
    private function resolveRouteParts(SimulationInterface $simulation, string $path, int $storeId): ?array
    {
        $manual = trim((string)$simulation->getActionName());
        if ($manual !== '') {
            return $this->splitManualActionName($manual);
        }

        return $this->actionNameResolver->resolve($path, $storeId);
    }

    /**
     * Split a manually entered full action name into route parts: first
     * segment = route, last = action, everything between = controller.
     *
     * @param string $actionName
     * @return array{route: string, controller: string, action: string, params: array<string, string>, source: string}
     */
    private function splitManualActionName(string $actionName): array
    {
        $bits = array_values(array_filter(explode('_', strtolower($actionName)), static fn ($bit) => $bit !== ''));

        $route = $bits !== [] ? array_shift($bits) : 'index';
        $action = $bits !== [] ? array_pop($bits) : 'index';
        $controller = $bits !== [] ? implode('_', $bits) : 'index';

        return [
            'route' => $route,
            'controller' => $controller,
            'action' => $action,
            'params' => [],
            'source' => 'manual',
        ];
    }

    /**
     * Add advisory warnings: unparsable rule rows, an empty expression, and
     * action_name conditions evaluated without a resolved action name.
     *
     * @param array<int, mixed> $rows
     * @param ExpressionInterface $expression
     * @param array<string, mixed>|null $routeParts
     * @param array<int, string> $warnings Modified in place
     * @return void
     */
    private function collectRuleWarnings(
        array $rows,
        ExpressionInterface $expression,
        ?array $routeParts,
        array &$warnings
    ): void {
        $skipped = count($rows) - count($expression->getConditions());
        if ($skipped > 0) {
            $warnings[] = (string)__('%1 rule row(s) are incomplete or unknown and were skipped.', $skipped);
        }

        if ($expression->isEmpty()) {
            $warnings[] = (string)__('The rule expression is empty — the challenge never fires.');
            return;
        }

        if ($routeParts !== null) {
            return;
        }
        foreach ($expression->getConditions() as $condition) {
            if ($condition->getFieldCode() === 'action_name') {
                $warnings[] = (string)__(
                    'The full action name could not be resolved for this URL, so "Full Action Name" conditions were evaluated against an empty value. Enter it manually for exact results.'
                );
                return;
            }
        }
    }

    /**
     * Resolve every registered field against the synthetic request for the
     * transparency panel; a field that throws reports null.
     *
     * @param FieldProviderInterface $fieldPool
     * @return array<string, string|int|float|null>
     */
    private function snapshotFieldValues(FieldProviderInterface $fieldPool): array
    {
        $values = [];
        foreach ($fieldPool->getAll() as $code => $field) {
            try {
                $values[$code] = $field->getValue();
            } catch (\Throwable $e) {
                $values[$code] = null;
            }
        }

        return $values;
    }

    /**
     * Whether the route-gate provider (override or active) is configured.
     *
     * @return bool
     */
    private function isRouteGateProviderConfigured(): bool
    {
        try {
            return $this->providerPool->getRouteGateProvider()->isConfigured();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Host (incl. non-default port) of the emulated store's base link URL.
     *
     * @return string
     */
    private function resolveStoreHost(): string
    {
        try {
            $store = $this->storeManager->getStore();
        } catch (\Throwable $e) {
            return '';
        }
        if (!$store instanceof Store) {
            return '';
        }

        $parts = parse_url($store->getBaseUrl(UrlInterface::URL_TYPE_LINK)) ?: [];
        $host = strtolower((string)($parts['host'] ?? ''));
        if ($host !== '' && isset($parts['port'])) {
            $host .= ':' . $parts['port'];
        }

        return $host;
    }

    /**
     * Code of the currently emulated store ('' when unresolvable).
     *
     * @return string
     */
    private function resolveStoreCode(): string
    {
        try {
            return (string)$this->storeManager->getStore()->getCode();
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Id of the default store view, the fallback simulation scope.
     *
     * @return int
     */
    private function resolveDefaultStoreId(): int
    {
        $store = $this->storeManager->getDefaultStoreView();

        return $store !== null ? (int)$store->getId() : 0;
    }
}
