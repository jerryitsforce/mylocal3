<?php
declare(strict_types=1);

namespace Branch8\RmaAdminUi\Plugin\Webkul\MpRmaSystem\Block\Adminhtml\Rma\View\Tab;
/*
 *
 */

class DetailsPlugin
{
    /**
     * @param $subject
     * @param $result
     * @return string
     */
    public function afterGetTemplate($subject, $result)
    {
        return 'Branch8_RmaAdminUi::rma/view/tab/details.phtml';
    }
}
