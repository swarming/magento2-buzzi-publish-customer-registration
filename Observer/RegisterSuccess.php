<?php
/**
 * Copyright © Swarming Technology, LLC. All rights reserved.
 */
namespace Buzzi\PublishCustomerRegistration\Observer;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Buzzi\PublishCustomerRegistration\Model\DataBuilder;

class RegisterSuccess implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Buzzi\Publish\Model\Config\Events
     */
    private $configEvents;

    /**
     * @var \Buzzi\Publish\Api\QueueInterface
     */
    private $queue;

    /**
     * @var \Buzzi\PublishCustomerRegistration\Model\DataBuilder
     */
    private $dataBuilder;

    /**
     * @var \Magento\Store\Api\StoreResolverInterface
     */
    private $storeResolver;

    /**
     * @var \Buzzi\Publish\Helper\Customer
     */
    private $customerHelper;

    /**
     * @param \Buzzi\Publish\Model\Config\Events $configEvents
     * @param \Buzzi\Publish\Api\QueueInterface $queue
     * @param \Buzzi\PublishCustomerRegistration\Model\DataBuilder $dataBuilder
     * @param \Magento\Store\Api\StoreResolverInterface $storeResolver
     * @param \Buzzi\Publish\Helper\Customer|null $customerHelper
     */
    public function __construct(
        \Buzzi\Publish\Model\Config\Events $configEvents,
        \Buzzi\Publish\Api\QueueInterface $queue,
        \Buzzi\PublishCustomerRegistration\Model\DataBuilder $dataBuilder,
        \Magento\Store\Api\StoreResolverInterface $storeResolver,
        \Buzzi\Publish\Helper\Customer $customerHelper = null
    ) {
        $this->configEvents = $configEvents;
        $this->queue = $queue;
        $this->dataBuilder = $dataBuilder;
        $this->storeResolver = $storeResolver;
        $this->customerHelper = $customerHelper ?: ObjectManager::getInstance()->get(\Buzzi\Publish\Helper\Customer::class);
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Customer\Api\Data\CustomerInterface $customer */
        $customer = $observer->getData('customer');
        $storeId = $this->storeResolver->getCurrentStoreId();

        if (!$this->configEvents->isEventEnabled(DataBuilder::EVENT_TYPE, $storeId)
            || !$this->customerHelper->isExceptsMarketing($customer)
        ) {
            return;
        }

        $payload = $this->dataBuilder->getPayload($customer->getId());

        if ($this->configEvents->isCron(DataBuilder::EVENT_TYPE, $storeId)) {
            $this->queue->add(DataBuilder::EVENT_TYPE, $payload, $storeId);
        } else {
            $this->queue->send(DataBuilder::EVENT_TYPE, $payload, $storeId);
        }
    }
}
