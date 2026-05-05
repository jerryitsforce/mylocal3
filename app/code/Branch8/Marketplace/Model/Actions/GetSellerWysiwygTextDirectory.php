<?php

namespace Branch8\Marketplace\Model\Actions;

class GetSellerWysiwygTextDirectory
{
    private GetSellerInformation $getSellerInformation;
    /**
     * @param GetSellerInformation $getSellerInformation
     */
    public function __construct(GetSellerInformation $getSellerInformation)
    {
        $this->getSellerInformation = $getSellerInformation;
    }

    /**
     * @param $sellerId
     * @return string
     */
    public function execute($sellerId)
    {
        $seller = $this->getSellerInformation->get((int)$sellerId);
        if ($seller) {
            $clean = preg_replace("/[^a-zA-Z0-9 ]/", "", trim($seller['seller_code']));
            return 'wysiwygseller' . DIRECTORY_SEPARATOR . $clean;
        }
        return 'wysiwygseller';
    }
}
