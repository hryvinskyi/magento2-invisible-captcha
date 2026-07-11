<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Tester;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\FieldProviderFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Rebuilds the registered rule-field pool around a synthetic request, so the
 * real field implementations (single source of truth for value resolution)
 * evaluate the simulated request instead of the live one.
 *
 * The object manager is used strictly as an instantiation backend for this
 * factory: fields are re-created by class with the synthetic request
 * substituted for their `request` constructor argument; every other argument
 * resolves through DI as usual. Limitation: a field registered as a virtual
 * type is re-created from its concrete class, losing virtual-type argument
 * overrides.
 */
class SimulatedFieldPoolFactory
{
    /**
     * @param ObjectManagerInterface $objectManager
     * @param FieldProviderInterface $fieldProvider Live field registry to mirror
     * @param FieldProviderFactory $fieldProviderFactory
     */
    public function __construct(
        private readonly ObjectManagerInterface $objectManager,
        private readonly FieldProviderInterface $fieldProvider,
        private readonly FieldProviderFactory $fieldProviderFactory
    ) {
    }

    /**
     * Create a field registry whose fields read from the given request.
     *
     * @param RequestInterface $simulatedRequest
     * @return FieldProviderInterface
     */
    public function create(RequestInterface $simulatedRequest): FieldProviderInterface
    {
        $fields = [];
        foreach ($this->fieldProvider->getAll() as $code => $field) {
            $fields[$code] = $this->objectManager->create(
                get_class($field),
                ['request' => $simulatedRequest]
            );
        }

        return $this->fieldProviderFactory->create(['fields' => $fields]);
    }
}
