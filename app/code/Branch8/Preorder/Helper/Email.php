<?php
namespace Branch8\Preorder\Helper;

use Magento\Framework\App\Area;


/**
 * CMS Page Helper
 */
class Email extends \Webkul\MarketplacePreorder\Helper\Email
{
    public function sendNotifyEmail($emailIds, $productName)
    {
        try {
            $adminEmail = $this->getAdminEmail();
            if ($adminEmail != '') {
                $area = Area::AREA_FRONTEND;
                $store = $this->_storeManager->getStore()->getId();
                $templateOptions = ['area' => $area, 'store' => $store];
                $templateVars = [
                    'store' => $this->_storeManager->getStore(),
                    'product_name' => $productName,
                ];
                $from = ['email' => $adminEmail, 'name' => 'Store Owner'];
                foreach ($emailIds as $emailId) {
                    $customer = $this->getCustomer($emailId);
                    $templateVars['customer_name'] = $customer->getName();

                    $this->_inlineTranslation->suspend();
                    $to = [$emailId];
                    $templateId = $this->getTemplateId(self::CONFIG_PATH_NOTIFICATION_EMAIL);
                    $transport = $this->_transportBuilder
                        ->setTemplateIdentifier($templateId)
                        ->setTemplateOptions($templateOptions)
                        ->setTemplateVars($templateVars)
                        ->setFrom($from)
                        ->addTo($to)
                        ->getTransport();
                    $transport->sendMessage();
                    $this->_inlineTranslation->resume();
                }
            }
        } catch (\Exception $e) {
            $this->messageManager->addError(__('Unable to send email.'));
        }
    }


    /**
     * Notification to buyer after update product in stock
     *
     * @param array $emailIds
     * @param array $productIds
     * @param array $prorderIds
     */
    public function notifyBuyers($emailIds, $productIds, $prorderIds)
    {
        try {
            $sellerId = $this->_customerSession->getCustomerId();
            $loginUrl = $this->getLogInUrl();
            $senderName = '';
            $senderEmail = '';
            if ($sellerId) {
                $sender =  $this->customerModel->create()->load($sellerId);
                $senderName = $sender->getName();
                $senderEmail = $sender->getEmail();
            } else {
                $senderName = 'Admin';
                $senderEmail = $this->getAdminEmail();
            }
            if ($senderEmail != '') {
                $area = Area::AREA_FRONTEND;
                $store = $this->_storeManager->getStore()->getId();
                $templateOptions = ['area' => $area, 'store' => $store];
                $templateVars = [
                    'store' => $this->_storeManager->getStore(),
                    'login_url' => $loginUrl,
                ];
                $from = ['email' => $senderEmail, 'name' => $senderName];
                foreach ($emailIds as $key => $emailId) {
                    $customer = $this->getCustomer($emailId);

                    $product = $this->_preorderHelper->getProduct($productIds[$key]);
                    $msg = __(
                        'Product "%1" is in stock. Please go your account to complete preorder.',
                        $product->getName()
                    );
                    $templateVars['message'] = $msg;
                    $templateVars['customer_name'] = $customer->getName();
                    $templateVars['product_name'] = $product->getName();

                    $this->_inlineTranslation->suspend();
                    $to = [$emailId];
                    $templateId = $this->getTemplateId(self::CONFIG_PATH_NOTIFICATION_EMAIL);
                    $transport = $this->_transportBuilder
                        ->setTemplateIdentifier($templateId)
                        ->setTemplateOptions($templateOptions)
                        ->setTemplateVars($templateVars)
                        ->setFrom($from)
                        ->addTo($to)
                        ->getTransport();
                    $transport->sendMessage();
                    $this->_inlineTranslation->resume();
                }
            }
            return true;
        } catch (\Exception $e) {
            $this->messageManager->addError(__('Unable to send email.'));
            return false;
        }
    }

}
