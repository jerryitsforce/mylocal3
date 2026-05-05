<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model\Product;

use Magento\Framework\App\ResourceConnection;
use Branch8\MarketplaceStaging\Helper\Variation;
use Webkul\OptionsWithStockAndImages\Model\VariationsFactory;
use Webkul\OptionsWithStockAndImages\Model\SwatchFactory;

class BuildVariation
{
    public const KEY_VARIATION = 'wk_manage_variation';

    public const KEY_SWATCH = 'wk_manage_swatch';

    /**
     * @var \Branch8\MarketplaceStaging\Helper\Variation
     */
    protected Variation $variation;

    /** @var \Magento\Framework\DB\Adapter\AdapterInterface */
    protected $connection;

    /**
     * @var \Webkul\OptionsWithStockAndImages\Model\VariationsFactory
     */
    protected $variationsFactory;

    /**
     * @var \Webkul\OptionsWithStockAndImages\Model\SwatchFactory
     */
    protected $swatchFactory;

    /**
     * BuildVariation constructor.
     *
     * @param Variation $variation
     */
    public function __construct(
        Variation $variation,
        ResourceConnection $resourceConnection,
        VariationsFactory $variationsFactory,
        SwatchFactory $swatchFactory,
    )
    {
        $this->connection = $resourceConnection->getConnection();
        $this->variation = $variation;
        $this->variationsFactory = $variationsFactory;
        $this->swatchFactory = $swatchFactory;
    }

    public function saveVariationWhenApprove($product, $sellerId){
        $this->variation->saveVariationWhenApprove($product, $sellerId);
    }

    public function getCurrentVariation($productId, $isImport = false){
        if ($productId) {
            $collection = $this->variationsFactory->create()
                                    ->getCollection()
                                    ->addFieldToFilter("product_id", $productId);
            if ($collection->getSize()) {
                $data = $collection->getData();
                foreach ($data as $key => $value) {
                    $images = explode(',', $data[$key]['image']);
                    $images = array_filter($images);
                    if ($isImport) {
                        if(count($images)){
                            $data[$key]['image'] = implode(',', $images);
                        } else {
                            $data[$key]['image'] = '';
                        }
                        unset($data[$key]['is_lock_sku']);
                        unset($data[$key]['product_item_id']);
                    } else {
                        if (count($images)) {
                            $data[$key]['file'] = $images;
                            $data[$key]['image'] = $images;
                        } else {
                            $data[$key]['file'] = [];
                            $data[$key]['image'] = [];
                        }
                    }
                    if ($data[$key]['weight'] == 0) {
                        $data[$key]['weight'] = "";
                    }
                }
                return $data;
            }
        }
        return [];
    }

    public function getCurrentSwatch($productId){
        if ($productId) {
            $collection = $this->swatchFactory->create()
                                        ->getCollection()
                                        ->addFieldToFilter("product_id", $productId);
            if ($collection->getSize()) {
                return $collection->getData();
            }
        }
        return [];
    }

    public function buildSwatch($value){
        if (is_array($value)) {
            return $value;
        }
        $swatchDataArr = json_decode($value, true);
        $wkswatch = [];
        $data = [];
        foreach ($swatchDataArr as $swatchData) {
            $var = ltrim($swatchData['name'], "wkswatch");
            $var = ltrim($var, "[");
            $var = rtrim($var, "]");
            $var = explode("][", $var);
            $wkswatch[$var[0]][$var[1]] = $swatchData['value'];
        }
        foreach ($wkswatch as $keyId => $swatch) {
            if (isset($swatch['is_swatch'])
                &&
                ($swatch['is_swatch']=="on" || $swatch['is_swatch']==1 || $swatch['is_swatch']==true)
            ) {
                $swatch['is_swatch'] = 1;
            } else {
                $swatch['is_swatch'] = 0;
            }
            $data[] = $swatch;
        }
        return $data;
    }

    public function buildVariation($value){
        if (is_array($value)) {
            return $value;
        }
        $variationTemp = json_decode($value, true);
        $wkvariation = [];
        $data = [];
        foreach ($variationTemp as $variationData) {
            $var = ltrim($variationData['name'], "wkvariation");
            $var = ltrim($var, "[");
            $var = rtrim($var, "]");
            $var = explode("][", $var);
            if ($var[1] == 'file') {
                if ($var[2] != '' && isset($variationData['value'])) {
                    $wkvariation[$var[0]][$var[1]][$var[2]] = $variationData['value'];
                }
            } else {
                if(!isset($variationData['value'])){
                    continue;
                }
                $wkvariation[$var[0]][$var[1]] = $variationData['value'];
            }
        }
        foreach ($wkvariation as $variation) {
            $variationImage = [];
            if (!empty($variation['file'])) {
                foreach ($variation['file'] as $key => $file) {
                    $variationImage[$key] = rtrim($file, ".tmp");
                }
            }
            $variation['image'] = $variationImage;
            if(!isset($variation['is_sync'])){
                $variation['is_sync'] = '0';
            }
            $data[] = $variation;
        }
        return $data;
    }

    public function saveImages($data){
        foreach($data as $img){
            $img = rtrim($img, ".tmp");
            $this->variation->saveFile($img);
        }
    }

    /**
     * Process parent products
     *
     * @param Product $product
     * @return void
     */
    public function saveStock($data, $product_id)
    {
        $this->connection->beginTransaction();
        try{
            foreach($data as $variation){
                $updateData  = [
                    'stock' => $variation['stock']
                ];
                $whereUpdate = [
                    'product_id = ?' => $product_id,
                    'comb = ?'       => $variation['comb']
                ];
                $this->connection->update(
                    'wk_osi_variations',
                    $updateData,
                    $whereUpdate
                );
            }
            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    /**
     * Process parent products
     *
     * @param Product $product
     * @return void
     */
    public function saveWeight($data, $product_id)
    {
        $this->connection->beginTransaction();
        try{
            foreach($data as $variation){
                $updateData  = [
                    'weight' => $variation['weight']
                ];
                $whereUpdate = [
                    'product_id = ?' => $product_id,
                    'comb = ?'       => $variation['comb']
                ];
                $this->connection->update(
                    'wk_osi_variations',
                    $updateData,
                    $whereUpdate
                );
            }
            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }
}
