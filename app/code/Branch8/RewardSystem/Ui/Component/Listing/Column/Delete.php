<?php
namespace Branch8\RewardSystem\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Branch8\RewardSystem\Model\Config\Source\Conditions;

class Delete extends Column
{
    protected $conditions;

    protected $url;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Conditions $conditions,
        \Magento\Backend\Model\UrlInterface $url,
        array $components = [],
        array $data = []
    ) {
        $this->uiComponentFactory = $uiComponentFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->conditions = $conditions;
        $this->url = $url;
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
                if (isset($item['entity_id'])) {
                    $deleteUrl = $this->url->getUrl('rewardsystem/reward/delete', ['id' => $item['entity_id']]);
                    $item['delete'] = '<a href="javascript:;" onClick="return (confirm(\'Do you want to delete the event?\')) ? window.location.href=\''.$deleteUrl.'\': false">'.__('Delete').'</a>';
                    
                }
            }
        }

        return $dataSource;
    }
}
