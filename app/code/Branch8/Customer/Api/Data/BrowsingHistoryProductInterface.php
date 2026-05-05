<?php
namespace Branch8\Customer\Api\Data;

interface BrowsingHistoryProductInterface
{
    const SKU = 'sku';
    const NAME = 'name';
    const URL = 'url';
    const THUMBNAIL = 'thumbnail';

    /**
     * Get SKU of the product.
     *
     * @return string
     */
    public function getSku();

    /**
     * Set SKU of the product.
     *
     * @param string $sku
     * @return $this
     */
    public function setSku($sku);

    /**
     * Get name of the product.
     *
     * @return string
     */
    public function getName();

    /**
     * Set name of the product.
     *
     * @param string $name
     * @return $this
     */
    public function setName($name);

    /**
     * Get URL of the product.
     *
     * @return string
     */
    public function getUrl();

    /**
     * Set URL of the product.
     *
     * @param string $url
     * @return $this
     */
    public function setUrl($url);

    /**
     * Get thumbnail image of the product.
     *
     * @return string
     */
    public function getThumbnail();

    /**
     * Set thumbnail image of the product.
     *
     * @param string $thumbnail
     * @return $this
     */
    public function setThumbnail($thumbnail);
}
