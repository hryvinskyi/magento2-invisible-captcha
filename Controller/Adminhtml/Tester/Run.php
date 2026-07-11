<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Controller\Adminhtml\Tester;

use Hryvinskyi\InvisibleCaptcha\Api\Tester\RouteRuleTesterInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Tester\SimulationFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Psr\Log\LoggerInterface;

/**
 * AJAX endpoint of the Protection Rules "Test Rules" panel: simulates a
 * storefront request against the draft (or saved) rules and returns the
 * verdict with the per-condition trace.
 */
class Run extends Action implements HttpPostActionInterface
{
    /**
     * Matches the system-config section the rules editor lives in.
     */
    public const ADMIN_RESOURCE = 'Hryvinskyi_InvisibleCaptcha::config';

    /**
     * @param Context $context
     * @param RouteRuleTesterInterface $routeRuleTester
     * @param SimulationFactory $simulationFactory
     * @param JsonFactory $resultJsonFactory
     * @param JsonSerializer $serializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        private readonly RouteRuleTesterInterface $routeRuleTester,
        private readonly SimulationFactory $simulationFactory,
        private readonly JsonFactory $resultJsonFactory,
        private readonly JsonSerializer $serializer,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    /**
     * Run the simulation described by the POST parameters.
     *
     * @return Json
     */
    public function execute(): Json
    {
        $result = $this->resultJsonFactory->create();

        $url = trim((string)$this->getRequest()->getParam('url'));
        if ($url === '') {
            return $result->setData([
                'ok' => false,
                'message' => (string)__('Enter a URL or path to test.'),
            ]);
        }

        try {
            $simulation = $this->simulationFactory->create([
                'url' => $url,
                'method' => trim((string)$this->getRequest()->getParam('method')) ?: 'GET',
                'userAgent' => (string)$this->getRequest()->getParam('user_agent'),
                'clientIp' => trim((string)$this->getRequest()->getParam('client_ip')),
                'referer' => trim((string)$this->getRequest()->getParam('referer')),
                'actionName' => trim((string)$this->getRequest()->getParam('action_name')) ?: null,
                'storeId' => $this->resolveStoreId(),
                'draftRules' => $this->resolveDraftRules(),
            ]);

            return $result->setData(['ok' => true] + $this->routeRuleTester->test($simulation));
        } catch (\Throwable $e) {
            $this->logger->error('[InvisibleCaptcha] rule tester failed: ' . $e->getMessage(), ['exception' => $e]);

            return $result->setData([
                'ok' => false,
                'message' => (string)__('Rule test failed: %1', $e->getMessage()),
            ]);
        }
    }

    /**
     * Store id from the request, or null for the default store view.
     *
     * @return int|null
     */
    private function resolveStoreId(): ?int
    {
        $storeId = $this->getRequest()->getParam('store_id');

        return $storeId !== null && $storeId !== '' ? (int)$storeId : null;
    }

    /**
     * Draft rule rows posted by the editor, or null to test the saved rules.
     *
     * @return array<int, array<string, string>>|null
     */
    private function resolveDraftRules(): ?array
    {
        $rulesJson = trim((string)$this->getRequest()->getParam('rules'));
        if ($rulesJson === '') {
            return null;
        }

        try {
            $decoded = $this->serializer->unserialize($rulesJson);
        } catch (\InvalidArgumentException $e) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }
}
