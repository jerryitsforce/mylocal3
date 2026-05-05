<?php

namespace Branch8\User\Plugin\Auth;

use Magento\Framework\App\Action\HttpGetActionInterface;

class ResetPassword extends \Magento\User\Controller\Adminhtml\Auth implements HttpGetActionInterface
{
    public function aroundExecute($subject, $process){
        return $this->execute();
    }


    public function execute()
    {
        $passwordResetToken = (string)$this->getRequest()->getQuery('token');
        $userId = (int)$this->getRequest()->getQuery('id');
        try {
            $this->_validateResetPasswordLinkToken($userId, $passwordResetToken);

            // Extend token validity to avoid expiration while this form is
            // being completed by the user.
            $user = $this->_userFactory->create()->load($userId);
            $user->changeResetPasswordLinkToken($passwordResetToken);
            $user->save();

            $this->_view->loadLayout();

            $content = $this->_view->getLayout()->getBlock('content');
            /**
             * Re-set template(as the same core) to content block
             * Because it does not apply the template for block name "content"
             */
            $content->setTemplate('Magento_User::admin/resetforgottenpassword.phtml');
            if ($content) {
                $content->setData('user_id', $userId)->setData('reset_password_link_token', $passwordResetToken);
            }

            $this->_view->renderLayout();
        } catch (\Exception $exception) {
            $this->messageManager->addError(__('Your password reset link has expired.'));
            $this->_redirect('adminhtml/auth/forgotpassword', ['_nosecret' => true]);
            return;
        }
    }
}