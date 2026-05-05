<?php

namespace Branch8\HotaiCore\Helper;

use Branch8\HotaiCore\Model\Product\BarcodeType;
use Branch8\HotaiCore\Model\Product\ExchangeUrl;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Laminas\Barcode\Barcode as BarcodeDisplayer;
use Magento\Catalog\Api\ProductRepositoryInterface;

class Barcode
{
    /** @var ProductRepositoryInterface */
    protected $productRepository;

    public function __construct(
        ProductRepositoryInterface $productRepository
    ) {
        $this->productRepository = $productRepository;
    }

    /**
     * 取得商品的ExchangeUrl
     *
     * @param integer $productId
     * @return string|null
     */
    public function getExchangeUrlByProductId(int $productId): ?string
    {
        $product = $this->productRepository->getById($productId);

        $attribute = $product->getCustomAttribute(ExchangeUrl::ATTRIBUTE_CODE);

        if (!$attribute) {
            return null;
        }

        return $attribute->getValue();
    }

    /**
     * 取得商品的BarcodeType
     *
     * @param integer $productId
     * @return integer|null
     */
    public function getBarcodeTypeByProductId(int $productId): ?int
    {
        $product = $this->productRepository->getById($productId);

        $attribute = $product->getCustomAttribute(BarcodeType::ATTRIBUTE_CODE);

        if (!$attribute) {
            return null;
        }

        return (int) $attribute->getValue();
    }

    /**
     * 在畫面上生成barcode圖片
     *
     * @param integer $barcodeType
     * @param string $content
     * @return void
     */
    public function display(int $barcodeType, string $content): void
    {
        switch ($barcodeType) {
            case BarcodeType::TYPE_CODE_39:
                $this->renderBarcode('code39', $content);
                break;

            case BarcodeType::TYPE_CODE_128:
                $this->renderBarcode('code128', $content);
                break;

            case BarcodeType::TYPE_QRCODE:
                $this->renderQRcode($content);
                break;

            default:
                throw new \Exception(__("Invalid barcode type given: {$barcodeType}"));
        }
    }

    /**
     * 產出Barcode圖片
     *
     * @param string $type
     * @param string $content
     * @return void
     */
    protected function renderBarcode(string $type, string $content): void
    {
        BarcodeDisplayer::render(
            $type,
            'image',
            [
                'text' => $content,
            ],
            [
                'imageType' => 'png',
            ],
        );
    }

    /**
     * 產出QRcode圖片
     *
     * @param string $content
     * @return void
     */
    protected function renderQRcode(string $content): void
    {
        $writer = new PngWriter();
        $qrCode = QrCode::create($content);
        $result = $writer->write($qrCode);

        header('Content-Type: ' . $result->getMimeType());

        echo $result->getString();
    }
}
