<?php

namespace Branch8\Marketplace\Override\Controller\Index;

class Index extends \Webkul\Marketplace\Controller\Index\Index
{
    public function execute()
    {
        $this->getRequest()->initForward();
        $this->getRequest()->setActionName('noroute');
        $this->getRequest()->setDispatched(false);

        return false;
    }

}