<?php

declare(strict_types=1);

namespace Branch8\HelpDesk\Block\Adminhtml\Ticket\Edit;

use Branch8\HelpDesk\ViewModel\TicketData;

/**
 * Tabs Class
 */
class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    private $ticketData;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param TicketData $ticketData
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context  $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Backend\Model\Auth\Session      $authSession,
        TicketData                               $ticketData,
        array                                    $data = []
    )
    {
        parent::__construct($context, $jsonEncoder, $authSession, $data);
        $this->ticketData = $ticketData;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('ticket_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Ticket Information'));
    }

    /**
     * @return string
     *
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareLayout()
    {
        $this->addTab(
            'general',
            [
                'label' => __('General'),
                'content' => $this->getLayout()->createBlock('Branch8\HelpDesk\Block\Adminhtml\Ticket\Edit\Tab\General')->toHtml()
            ]
        );
        $this->addTab(
            'team_information',
            [
                'label' => __('Team Information'),
                'content' => $this->getLayout()->createBlock('Branch8\HelpDesk\Block\Adminhtml\Ticket\Edit\Tab\AdminInformation')->toHtml()
            ]
        );
        $this->addTab(
            'customer_information',
            [
                'label' => __('Customer Information'),
                'content' => $this->getLayout()->createBlock('Branch8\HelpDesk\Block\Adminhtml\Ticket\Edit\Tab\Customer')->toHtml()
            ]
        );

        if ($this->_request->getParam('ticket_id')
            && $this->ticketData->enableAdminChatMessage()
        ) {
            $this->addTab(
                'message',
                [
                    'label' => __('Message'),
                    'content' => $this->getLayout()->createBlock('Branch8\HelpDesk\Block\Adminhtml\Ticket\Edit\Tab\Messages')->toHtml()
                ]
            );
        }
    }
}
