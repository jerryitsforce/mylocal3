<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\Checkout\Controller\Address;

use Magento\Customer\Controller\AbstractAccount;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Controller\ResultFactory;

/**
 * Customer Address Form Post Controller
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ClearData extends AbstractAccount implements HttpPostActionInterface
{
    /**
     * @var Session
     */
    protected Session $_customerSession;

    public function __construct(
        Context $context,
        Session $customerSession
        )
    {
        parent::__construct($context);
        $this->_customerSession = $customerSession;
    }
    /**
     * Process address form save
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $result = [
            'success' => false,
        ];
        $responseResult = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $this->_customerSession->setStoreData(null);
            $this->_customerSession->setCheckoutAddress(null);
            $this->_customerSession->setStoreCheckoutData(null);
            $result['success'] = true;
        } catch (\Exception $e) {
            $result['msg'] = $e->getMessage();
        }

        return $responseResult->setData($result);
    }
}
