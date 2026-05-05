<?php

namespace Branch8\SellerContactInformation\Ui\Component\Listing\Columns;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Authorization\Model\ResourceModel\Role\Grid\CollectionFactory as RoleCollectionFactory;

class ContractUpload extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var RoleCollectionFactory
     */
    protected $roleCollectionFactory;

    /**
     * Constructor.
     *
     * @param ContextInterface      $context
     * @param UiComponentFactory    $uiComponentFactory
     * @param UrlInterface          $urlBuilder
     * @param RoleCollectionFactory $roleCollectionFactory,
     * @param array                 $components
     * @param array                 $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        RoleCollectionFactory $roleCollectionFactory,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->roleCollectionFactory = $roleCollectionFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source.
     *
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');

            //get admin roles data
            $roles = $this->roleCollectionFactory->create();
            $roleData = $roles->getData();
            $arrayRoleName = [];
            foreach ($roleData as $role) {
                $arrayRoleName[$role['role_id']] = $role['role_name'];
            }

            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['salesperson']) && !empty($item['salesperson'])) {
                    $item['salesperson'] = isset($arrayRoleName[$item['salesperson']]) ? $arrayRoleName[$item['salesperson']] : $item['salesperson'];
                }

                if (isset($item[$fieldName]) && !empty($item[$fieldName])) {
                    $item[$fieldName] = 1;
                } else {
                    $item[$fieldName] = 0;
                }
            }
        }

        return $dataSource;
    }
}
