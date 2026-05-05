<?php

namespace Branch8\CatalogCustom\Helper;

class Data extends \Webkul\MpMassUpload\Helper\Data
{
    /**
     * Save Product
     * Custom save product with staging update import
     *
     * @param int $sellerId
     * @param int $row
     * @param array $wholeData
     *
     * @return array
     */
    public function saveProduct($sellerId, $row, $wholeData)
    {
        $profile = $this->_profileRepository->get($wholeData['profile_id']);
        $profileAttributeId =  $profile['attribute_set_id'];
        $attributeSetCollection = $this->_attributeSetCollection
            ->create()
            ->addFieldToFilter(
                'attribute_set_id',
                ['in' => $profileAttributeId]
            );
        $result = ['error' => 0, 'config_error' => 0, 'msg' => ''];
        if ($attributeSetCollection->getSize()) {
            $outputImportArr = [];
            try {
                /* Check if authorized seller */
                if (!empty($wholeData['is_multiple'])) {
                    $isMultiple = true;
                    $outputImportArr = $this->validateForMultiImport($wholeData, $row, $sellerId);
                    $wholeData = $outputImportArr['wholeData'];
                    $sellerId = isset($wholeData['product']['seller_id'])?$wholeData['product']['seller_id']:$sellerId;
                }
                if (!empty($wholeData['id']) && empty($wholeData['error'])) {
                    $productId = $wholeData['id'];
                    if (empty($sellerId)) {
                        $wholeData['msg'] = __(
                            'Skipped row %1. No seller has been assigned to this product.
                        Please update csv with seller id value',
                            $row
                        );
                        $wholeData['error'] = 1;
                    }
                    $rightseller = $this->isRightSeller($productId, $sellerId);
                    if (!$rightseller) {
                        $wholeData['msg'] = __(
                            'Skipped row %1. Product is already assigned to other seller.',
                            $row
                        );
                        $wholeData['error'] = 1;
                    }
                }
                if ($row == 1) {
                    $this->_customerSession->setSuccesProductCount(0);
                }
                $uploadedPro = $wholeData['total_row_count'];
                $successProCount = (int) $this->_customerSession->getSuccesProductCount();
                $area = $this->state->getAreaCode();
                /*Set Product Add Status According to seller Group*/
                if ($this->isSellerGroupEnable() && !$this->checkProductAllowedStatus($uploadedPro, $successProCount)) {
                    $result['error'] = 1;
                    if ($this->getAllowedProductQty()) {
                        $result['message'] =
                            __('You are not allowed to add more than %1 Product(s)', $this->getAllowedProductQty());
                    } else {
                        $result['message'] = __('YOUR GROUP PACK IS EXPIRED...');
                    }
                } elseif ($this->isSellerMembershipEnable() && $area == 'frontend' &&
                    ($this->getConfigFeeAppliedFor() == 0 && !$this->isMembershipFeePaid())) {
                    $erroFlag = 1;
                    $data = $this->isMembershipFeePaid($erroFlag);
                    if ($data['status']) {
                        $result['error'] = 1;
                        $result['message'] = __('Seller Membership : %1 ', $data['msg']);
                    }
                } else {
                    if (!empty($wholeData['error'])) {
                        $result['error'] = $wholeData['error'];
                        $result['msg'] = $wholeData['msg'];
                    } else {
                        $prodQty = 0;
                        if ($wholeData['type'] == 'configurable') {
                            $prodQty = $wholeData['product']['quantity_and_stock_status']['qty'];
                            unset($wholeData['product']['quantity_and_stock_status']['qty']);
                        }
                        $result = $this->_saveProduct->saveProductData($sellerId, $wholeData, $profile);
                        $isInStock = 1;
                        $productID = $result['product_id'];
                        if ($area != 'frontend' && isset($wholeData['store_to_upload'])) {
                            $this->_mpProduct->create()->getCollection()
                                ->addFieldToFilter('mageproduct_id', $productID)->getFirstItem()
                                ->setData('store_id', $wholeData['store_to_upload'])->save();
                        } else {
                            $this->_mpProduct->create()->getCollection()
                                ->addFieldToFilter('mageproduct_id', $productID)->getFirstItem()
                                ->setData('store_id', 0)->save();
                        }
                        if ($wholeData['type'] == 'configurable') {
                            $wholeData['product']['quantity_and_stock_status']['qty'] = $prodQty;
                        }
                        if (!(int)$wholeData['product']['quantity_and_stock_status']['qty']) {
                            $isInStock = 0;
                        }
                        $result['is_in_stock'] = $isInStock;
                        if (!empty($result['product_id'])) {
                            $productId = (int) $result['product_id'];
                            $successProCount = (int) $this->_customerSession->getSuccesProductCount();
                            $successProCount++;
                            $this->_customerSession->setSuccesProductCount($successProCount);
                        }
                    }
                }
            } catch (\Exception $e) {
                $result['msg'] = __('Skipped row %1. %2', $row, $e->getMessage());
                $result['error'] = 1;
            }
            $result['total_row_count'] = $wholeData['total_row_count'];
            $result['row'] = $row;
            if ($wholeData['total_row_count'] != $row) {
                $nextRow = $row+1;
                $result['next_row_data'] = $this->calculateProductRowData(
                    $sellerId,
                    $wholeData['profile_id'],
                    $nextRow,
                    $wholeData['type']
                );
                $result['next_row_data']['profile_id'] = $wholeData['profile_id'];
                $result['next_row_data']['row'] = $nextRow;
                $result['next_row_data']['total_row_count'] = $wholeData['total_row_count'];
                $result['next_row_data']['seller_id'] = $sellerId;
                if (!empty($wholeData['store_to_upload'])) {
                    $result['next_row_data']['store_to_upload'] = $wholeData['store_to_upload'];
                }
                if (!empty($wholeData['is_multiple'])) {
                    $result['next_row_data']['is_multiple'] = $isMultiple;
                }
            }
            if ($result['error'] == 1) {
                if (!empty($result['message'])) {
                    $result['msg'] = $result['message'];
                }
                return $result;
            } else {
                if (empty($result['product_id'])) {
                    $result['product_id'] = 0;
                }
                $productId = (int) $result['product_id'];
            }
            if ($productId == 0) {
                $result['error'] = 1;
                $result['msg'] = __('Skipped row %1. error in importing product.', $row);
            }
        } else {

            $result['error'] = 1;
            $result['msg'] = __('Skipped row %1. Error in importing
        product selected attribute set does not exist.', $row);
        }
        return $result;
    }
}
