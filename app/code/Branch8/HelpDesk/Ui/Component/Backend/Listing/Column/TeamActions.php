<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Ui\Component\Backend\Listing\Column;

use Branch8\HelpDesk\Model\Ticket\AclRole;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

/**
 * Team Actions for Team Listing
 */
class TeamActions extends Column
{
    /** Url path */
    const EDIT_URL = 'helpdesk/team/edit';
    const DELETE_URL = 'helpdesk/team/delete';

    /** @var UrlInterface */
    protected $urlBuilder;

    /**
     * @var string
     */
    private $editUrl;
    /**
     * @var string
     */
    private $deleteUrl;
    /**
     * @var \Magento\Framework\AuthorizationInterface
     */
    private $authorization;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
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
                if (isset($item['team_id'])) {
                    if ($this->authorization->isAllowed(AclRole::MANAGE_TEAM)) {
                        $item[$name]['edit'] = [
                            'href' => $this->urlBuilder->getUrl(
                                $this->editUrl, ['team_id' => $item['team_id']]
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
