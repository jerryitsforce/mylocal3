<?php
declare(strict_types=1);
namespace Branch8\MagentoVisualMerchandiser\Controller\Adminhtml\VisualMerchandiser;

class NewConditionHtml extends \Branch8\MagentoVisualMerchandiser\Controller\Adminhtml\VisualMerchandiser
{
    /**
     * Ajax conditions
     *
     * @return void
     */
    public function execute()
    {
        $this->conditionsHtmlAction('conditions');
    }
}
