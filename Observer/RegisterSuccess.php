<?php
/**
 * Copyright © Swarming Technology, LLC. All rights reserved.
 */
namespace Buzzi\PublishCustomerRegistration\Observer;

use Magento\Framework\Event\Observer;
use Buzzi\PublishCustomerRegistration\Model\DataBuilder;

class RegisterSuccess implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Buzzi\Publish\Model\Config\Events
     */
    protected $configEvents;

    /**
     * @var \Buzzi\Publish\Api\QueueInterface
     */
    protected $queue;

    /**
     * @var \Buzzi\PublishCustomerRegistration\Model\DataBuilder
     */
    protected $dataBuilder;

    /**
     * @var \Magento\Store\Api\StoreResolverInterface
     */
    protected $storeResolver;

    /**
     * @param \Buzzi\Publish\Model\Config\Events $configEvents
     * @param \Buzzi\Publish\Api\QueueInterface $queue
     * @param \Buzzi\PublishCustomerRegistration\Model\DataBuilder $dataBuilder
     * @param \Magento\Store\Api\StoreResolverInterface $storeResolver
     */
    public function __construct(
        \Buzzi\Publish\Model\Config\Events $configEvents,
        \Buzzi\Publish\Api\QueueInterface $queue,
        \Buzzi\PublishCustomerRegistration\Model\DataBuilder $dataBuilder,
        \Magento\Store\Api\StoreResolverInterface $storeResolver
    ) {
        $this->configEvents = $configEvents;
        $this->queue = $queue;
        $this->dataBuilder = $dataBuilder;
        $this->storeResolver = $storeResolver;
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

        if (!$this->configEvents->isEventEnabled(DataBuilder::EVENT_TYPE, $storeId)) {
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
