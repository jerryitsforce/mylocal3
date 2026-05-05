<?php
declare(strict_types=1);

namespace Branch8\MaskCustomerInformation\Ui\Listing;

use Branch8\MaskCustomerInformation\Model\AdminPermission;
use Branch8\MaskInformation\Model\MaskRulesComposite;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;

/**
 * Class DataProvider
 *
 * @api
 */
class MaskOrderCollectionDataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    private UrlInterface $url;
    private AdminPermission $permission;

    private MaskRulesComposite $maskRulesComposite;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ReportingInterface $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param AdminPermission $permission
     * @param MaskRulesComposite $maskRulesComposite
     * @param UrlInterface $url
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        string                $name,
        string                $primaryFieldName,
        string                $requestFieldName,
        ReportingInterface    $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface      $request,
        FilterBuilder         $filterBuilder,
        AdminPermission       $permission,
        MaskRulesComposite    $maskRulesComposite,
        UrlInterface          $url,
        array                 $meta = [],
        array                 $data = []
    )
    {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
        $this->url = $url;
        $this->permission = $permission;
        $this->maskRulesComposite = $maskRulesComposite;
    }

    protected function searchResultToOutput(SearchResultInterface $searchResult)
    {
        $arrItems = [];
        $canView = $this->permission->canView();
        $arrItems['items'] = [];
        $fieldRules = [
            'billing_address' => 'address',
            'billing_name' => 'name',
            'billing_phone' => 'phone',
            'customer_name' => 'name',
            'phone_number' => 'phone',
            'seller' => 'name',
            'shipping_address' => 'address',
            'shipping_name' => 'name',
            'shipping_phone' => 'phone',
            'customer_email' => 'email',
        ];
        foreach ($searchResult->getItems() as $item) {
            $itemData = [];
            foreach ($item->getCustomAttributes() as $attribute) {
                $itemData[$attribute->getAttributeCode()] = $attribute->getValue();
            }
            if (!$canView) {
                foreach ($fieldRules as $fieldName => $fieldRule) {
                    if (isset($itemData[$fieldName])) {
                        $itemData[$fieldName] = $this->maskRulesComposite->mask($fieldRule, $itemData[$fieldName]);
                    }
                }
            }
            $arrItems['items'][] = $itemData;
        }
        $arrItems['totalRecords'] = $searchResult->getTotalCount();
        return $arrItems;
    }


}
