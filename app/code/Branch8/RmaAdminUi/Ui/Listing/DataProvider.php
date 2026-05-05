<?php
declare(strict_types=1);

namespace Branch8\RmaAdminUi\Ui\Listing;

use Branch8\Rma\Model\Rma\Status as RmaStatus;
use Branch8\RmaAdminUi\Model\Permission;
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
class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    private UrlInterface $url;
    private Permission $permission;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ReportingInterface $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param Permission $permission
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
        Permission            $permission,
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
    }

    protected function searchResultToOutput(SearchResultInterface $searchResult)
    {
        $arrItems = [];
        $arrItems['items'] = [];
        foreach ($searchResult->getItems() as $item) {
            $itemData = [];
            foreach ($item->getCustomAttributes() as $attribute) {
                $itemData[$attribute->getAttributeCode()] = $attribute->getValue();
            }
            $itemData['showApproveFinanceButton'] = $this->canSHowApproveButton($itemData);
            if ($itemData['showApproveFinanceButton']) {
                $itemData['approveFinance']['post_data'] = json_encode(
                    [
                        'action' => $this->getApproveFinanceUrl(),
                        'data' => ['id' => $itemData['id']]]
                );
                $itemData['rejectFinance']['post_data'] = json_encode(
                    [
                        'action' => $this->getRejectFinanceUrl(),
                        'data' => ['id' => $itemData['id']]]
                );

            }
            $rmaUrl=$this->url->getUrl(
                'mprmasystem/rma/edit', ['id' => $item->getId()]
            );
            $itemData['order_ref'] = '<a target="blank" href=' . $rmaUrl . '>' . $itemData['order_ref'] . '</a>';
            $arrItems['items'][] = $itemData;
        }

        $arrItems['totalRecords'] = $searchResult->getTotalCount();

        return $arrItems;
    }

    /**
     * @return string
     */
    private function getApproveFinanceUrl()
    {
        return $this->url->getUrl('mprmasystem/finance/approve');
    }

    /**
     * @return string
     */
    private function getRejectFinanceUrl()
    {
        return $this->url->getUrl('mprmasystem/finance/reject');
    }

    /**
     * @param $itemData
     * @return bool
     */
    public function canSHowApproveButton($itemData)
    {
        return $this->permission->canApproveFinanceRma()
            && ((int)$itemData['status'] === (int)RmaStatus::RETURN_FINANCIAL_REVIEW_PROCESSING);
    }
}
