<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Tester;

use Hryvinskyi\InvisibleCaptcha\Api\Tester\ActionNameResolverInterface;
use Magento\Cms\Api\GetPageByIdentifierInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\Route\ConfigInterface as RouteConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

class ActionNameResolver implements ActionNameResolverInterface
{
    private const SOURCE_HOME = 'home';
    private const SOURCE_REWRITE = 'rewrite';
    private const SOURCE_ROUTE = 'route';
    private const SOURCE_CMS_PAGE = 'cms_page';

    /**
     * @param UrlFinderInterface $urlFinder
     * @param RouteConfigInterface $routeConfig
     * @param GetPageByIdentifierInterface $getPageByIdentifier
     */
    public function __construct(
        private readonly UrlFinderInterface $urlFinder,
        private readonly RouteConfigInterface $routeConfig,
        private readonly GetPageByIdentifierInterface $getPageByIdentifier
    ) {
    }

    /**
     * @inheritDoc
     */
    public function resolve(string $path, int $storeId): ?array
    {
        $path = trim($path, '/');

        // The storefront root dispatches Magento's default route: the CMS home page.
        if ($path === '') {
            return $this->buildParts('cms', [], self::SOURCE_HOME);
        }

        $target = $this->resolveRewriteTarget($path, $storeId);
        if ($target !== null) {
            $segments = explode('/', $target);
            $routeId = (string)array_shift($segments);

            return $routeId === '' ? null : $this->buildParts($routeId, $segments, self::SOURCE_REWRITE);
        }

        return $this->resolveFromFrontName($path) ?? $this->resolveCmsPage($path, $storeId);
    }

    /**
     * CMS pages without a rewrite entry dispatch through the CMS router by
     * their identifier — mirror that as the last resolution step.
     *
     * @param string $path
     * @param int $storeId
     * @return array{route: string, controller: string, action: string, params: array<string, string>, source: string}|null
     */
    private function resolveCmsPage(string $path, int $storeId): ?array
    {
        try {
            $page = $this->getPageByIdentifier->execute($path, $storeId);
        } catch (NoSuchEntityException $e) {
            return null;
        }

        return [
            'route' => 'cms',
            'controller' => 'page',
            'action' => 'view',
            'params' => ['page_id' => (string)$page->getId()],
            'source' => self::SOURCE_CMS_PAGE,
        ];
    }

    /**
     * Look the path up in the store's URL rewrites; a redirect-type rewrite
     * is followed one hop so permanently-moved SEO URLs still resolve.
     *
     * @param string $path
     * @param int $storeId
     * @return string|null Trimmed target route path, or null when no
     *     forwarding rewrite exists
     */
    private function resolveRewriteTarget(string $path, int $storeId): ?string
    {
        $rewrite = $this->findRewrite($path, $storeId);
        if ($rewrite === null) {
            return null;
        }

        if ((int)$rewrite->getRedirectType() !== 0) {
            $rewrite = $this->findRewrite(trim((string)$rewrite->getTargetPath(), '/'), $storeId);
            if ($rewrite === null || (int)$rewrite->getRedirectType() !== 0) {
                return null;
            }
        }

        $target = trim((string)$rewrite->getTargetPath(), '/');

        return $target === '' ? null : $target;
    }

    /**
     * Find the rewrite whose request path matches exactly for the store.
     *
     * @param string $requestPath
     * @param int $storeId
     * @return UrlRewrite|null
     */
    private function findRewrite(string $requestPath, int $storeId): ?UrlRewrite
    {
        if ($requestPath === '') {
            return null;
        }

        return $this->urlFinder->findOneByData([
            UrlRewrite::REQUEST_PATH => $requestPath,
            UrlRewrite::STORE_ID => $storeId,
        ]);
    }

    /**
     * Interpret the path as a literal route path: the first segment is a
     * front name mapped to its route id via the frontend route config.
     *
     * @param string $path
     * @return array{route: string, controller: string, action: string, params: array<string, string>, source: string}|null
     */
    private function resolveFromFrontName(string $path): ?array
    {
        $segments = explode('/', $path);
        $frontName = (string)array_shift($segments);

        $routeId = $this->routeConfig->getRouteByFrontName($frontName, Area::AREA_FRONTEND);
        if (!$routeId) {
            return null;
        }

        return $this->buildParts((string)$routeId, $segments, self::SOURCE_ROUTE);
    }

    /**
     * Assemble route parts from a route id and the remaining path segments:
     * controller and action default to "index", trailing segment pairs become
     * request params — matching how the standard router fills the request.
     *
     * @param string $routeId
     * @param string[] $segments
     * @param string $source
     * @return array{route: string, controller: string, action: string, params: array<string, string>, source: string}
     */
    private function buildParts(string $routeId, array $segments, string $source): array
    {
        $controller = ($segments[0] ?? '') !== '' ? $segments[0] : 'index';
        $action = ($segments[1] ?? '') !== '' ? $segments[1] : 'index';

        $params = [];
        $tail = array_slice($segments, 2);
        for ($i = 0, $count = count($tail); $i < $count; $i += 2) {
            $params[$tail[$i]] = $tail[$i + 1] ?? '';
        }

        return [
            'route' => $routeId,
            'controller' => $controller,
            'action' => $action,
            'params' => $params,
            'source' => $source,
        ];
    }
}
