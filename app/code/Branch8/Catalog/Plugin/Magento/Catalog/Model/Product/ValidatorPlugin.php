<?php
declare(strict_types=1);

namespace Branch8\Catalog\Plugin\Magento\Catalog\Model\Product;

use Branch8\Catalog\Model\Product\Action\ValidateImageTags;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;

class ValidatorPlugin
{

    private ValidateImageTags $validateImageTags;

    /**
     * @param ValidateImageTags $validateImageTags
     */
    public function __construct(
        ValidateImageTags $validateImageTags,
    )
    {
        $this->validateImageTags = $validateImageTags;
    }

    /**
     * Validate product data
     *
     * @param Product\Validator $subject
     * @param bool|array $result
     * @param Product $product
     * @param RequestInterface $request
     * @param \Magento\Framework\DataObject $response
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterValidate(
        \Magento\Catalog\Model\Product\Validator $subject,
                                                 $result,
        \Magento\Catalog\Model\Product           $product,
        RequestInterface                         $request,
        DataObject                               $response
    )
    {
        if ($product->getMediaGalleryImages() && !$product->getMediaGalleryImages()->count()) {
            return $result;
        }
        $error = $this->validateImageTags->execute($product);
        if ($error) {
            $response->setError(
                true
            )->setMessage(
                __('Please select all required image tags')
            )->setAttributes(
                $error
            )->setCode(ValidateImageTags::ERR_CODE);
        }
        return $result;
    }

}
