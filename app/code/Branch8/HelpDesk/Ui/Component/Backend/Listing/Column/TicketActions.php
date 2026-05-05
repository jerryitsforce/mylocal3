<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Ui\Component\Backend\Listing\Column;

use Branch8\HelpDesk\Model\Ticket\AclRole;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

/**
 *
 */
class TicketActions extends Column
{
    /** Url path */
    const EDIT_URL = 'helpdesk/ticket/edit';
    const DELETE_URL = 'helpdesk/ticket/delete';

    /** @var UrlInterface */
    protected $urlBuilder;

    /**
     * @var string
     */
    private $editUrl;
    private $deleteUrl;
    private $authorization;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlBuilder $actionUrlBuilder
     * @param UrlInterface $urlBuilder
     * @param \Magento\Framework\AuthorizationInterface $authorization
     * @param array $components
     * @param array $data
     * @param string $editUrl
     * @param string $deleteUrl
     */
    public function __construct(
        ContextInterface                          $context,
        UiComponentFactory                        $uiComponentFactory,
        UrlInterface                              $urlBuilder,
        \Magento\Framework\AuthorizationInterface $authorization,
        array                                     $components = [],
        array                                     $data = [],
        string                                    $editUrl = self::EDIT_URL,
        string                                    $deleteUrl = self::DELETE_URL
    )
    {
        $this->authorization = $authorization;
        $this->urlBuilder = $urlBuilder;
        $this->editUrl = $editUrl;
        $this->deleteUrl = $deleteUrl;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $name = $this->getData('name');
                if (isset($item['ticket_id'])) {
                    if ($this->authorization->isAllowed(AclRole::VIEW_TICKET)) {
                        $item[$name]['edit'] = [
                            'href' => $this->urlBuilder->getUrl(
                                $this->editUrl, ['ticket_id' => $item['ticket_id']]
                            ),
                            'label' => __('View')
                        ];
                    }
                }
            }
        }
        return $dataSource;
    }
}
