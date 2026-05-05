<?php
declare(strict_types=1);

namespace Branch8\MaskCustomerInformationToSeller\Plugin\Webkul\Marketplace\Block;

use Branch8\MaskCustomerInformation\Model\PermissionInterface;
use Branch8\MaskCustomerInformation\ViewModel\MaskPersonal;
use Magento\Framework\App\ObjectManager;

class ViewPlugin
{
    private PermissionInterface $permission;

    /**
     * @param PermissionInterface $permission
     */
    public function __construct(
        PermissionInterface $permission
    )
    {
        $this->permission = $permission;
    }

    /**
     * Override template
     * @param $subject
     * @param $originTemplate
     * @return mixed|string
     */
    public function afterGetTemplate($subject, $originTemplate)
    {
        $checkBlocks = [
            'marketplace_order_view',
            'marketplace_order_info',

            'marketplace_order_invoice_viewlist',
            'marketplace_order_invoice_view',
            'marketplace_order_invoice_create',

            'marketplace_order_shipment_viewlist',
            'marketplace_order_shipment_create',
            'marketplace_order_shipment_view',

            'marketplace_order_creditmemo_viewlist',
            'marketplace_order_creditmemo_create',
            'marketplace_order_creditmemo_view'
        ];
        if ($this->permission->canView()) {
            return $originTemplate;
        }
        if (!in_array($subject->getNameInlayout(), $checkBlocks)) {
            return $originTemplate;
        }
        $subject->setData('mask_view_model', ObjectManager::getInstance()->get(MaskPersonal::class));
        return $this->getTemplate($originTemplate);
    }

    /**
     * @param $originTemplate
     * @return string
     */
    private function getTemplate($originTemplate)
    {
        switch ($originTemplate) {
            case "order/manageorder.phtml":
                return "Branch8_MaskCustomerInformationToSeller::order/manageorder.phtml";
            case "Webkul_Marketplace::order/info.phtml":
                return "Branch8_MaskCustomerInformationToSeller::order/info.phtml";

                //invoice
            case "order/invoice/list.phtml":
                return "Branch8_MaskCustomerInformationToSeller::order/invoice/list.phtml";
            case "Webkul_Marketplace::order/invoice/new.phtml":
                return "Branch8_MaskCustomerInformationToSeller::order/invoice/new.phtml";
            case "Webkul_Marketplace::order/invoice/view.phtml":
                return "Branch8_MaskCustomerInformationToSeller::order/invoice/view.phtml";

                //shipment
            case "order/shipment/list.phtml":
                return "Branch8_MaskCustomerInformationToSeller::order/shipment/list.phtml";
            case "Webkul_Marketplace::order/shipment/new.phtml":
                return "Branch8_MaskCustomerInformationToSeller::order/shipment/new.phtml";
            case "Webkul_Marketplace::order/shipment/view.phtml":
                return "Branch8_MaskCustomerInformationToSeller::order/shipment/view.phtml";

                //order
            case "order/creditmemo/list.phtml":
                return "Branch8_MaskCustomerInformationToSeller::order/creditmemo/list.phtml";
            case "Webkul_Marketplace::order/creditmemo/new.phtml":
                return "Branch8_MaskCustomerInformationToSeller::order/creditmemo/new.phtml";
            case "Webkul_Marketplace::order/creditmemo/view.phtml":
                return "Branch8_MaskCustomerInformationToSeller::order/creditmemo/view.phtml";
            default:
                return $originTemplate;
        }
    }
}
