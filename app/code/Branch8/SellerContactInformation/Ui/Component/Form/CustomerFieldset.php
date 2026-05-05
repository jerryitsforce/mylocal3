<?php

declare(strict_types=1);

namespace Branch8\SellerContactInformation\Ui\Component\Form;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\ComponentVisibilityInterface;

/**
 * Customer fieldset class
 */
class CustomerFieldset extends \Magento\Ui\Component\Form\Fieldset implements ComponentVisibilityInterface
{

    /**
     * @var \Webkul\Marketplace\Block\Adminhtml\Customer\Edit
     */
    protected $customerEdit;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @param ContextInterface $context
     * @param \Webkul\Marketplace\Block\Adminhtml\Customer\Edit $customerEdit
     * @param \Magento\Framework\App\Request\Http $request
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        \Webkul\Marketplace\Block\Adminhtml\Customer\Edit $customerEdit,
        \Magento\Framework\App\Request\Http $request,
        array $components = [],
        array $data = []
    ) {
        $this->context = $context;
        $this->customerEdit = $customerEdit;
        $this->request = $request;
        parent::__construct($context, $components, $data);
    }

    /**
     * Can show customer tab in tabs or not
     *
     * @return boolean
     */
    public function isComponentVisible(): bool
    {
        /**
         * Now we temporary show this tab, so Admin can update some information,
         * in phase 2, we will hide it again and create new tab for seller
         */
        return true;

        $customerId = $this->context->getRequestParam('id');
        if ($customerId) {
            $coll = $this->customerEdit->getMarketplaceUserCollection();
            $isSeller = false;
            foreach ($coll as $row) {
                $isSeller = $row->getIsSeller();
            }
            $isSellerPanel = $this->request->getParam('seller_panel');
            if ($isSeller && $isSellerPanel) {
                return false;
            }
        }

        return true;
    }
}
