<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       17/04/2026
 */

namespace Branch8\MarketPlaceProductDiscussion\Model\Actions;

use Branch8\MarketPlaceProductDiscussion\Model\Thread;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\ImageBuilder;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

abstract class AbstractFromThreadDataToRawData
{
    protected TimezoneInterface $timezone;
    protected ProductRepositoryInterface $productRepository;
    protected ImageBuilder $imageBuilder;

    /**
     * @param TimezoneInterface $timezone
     * @param ProductRepositoryInterface $productRepository
     * @param ImageBuilder $imageBuilder
     */
    public function __construct(
        TimezoneInterface          $timezone,
        ProductRepositoryInterface $productRepository,
        ImageBuilder               $imageBuilder
    )
    {
        $this->timezone = $timezone;
        $this->productRepository = $productRepository;
        $this->imageBuilder = $imageBuilder;
    }

    /**
     * @param int $id
     * @return \Magento\Catalog\Api\Data\ProductInterface|null
     */
    protected function getProduct(int $id)
    {
        try {
            return $this->productRepository->getById($id);
        } catch (\Exception $exception) {
            return null;
        }
    }

    /**
     * @param $date
     * @return string
     */
    protected function formatDate($date)
    {
        return $this->timezone->formatDateTime(
            $date,
            \IntlDateFormatter::SHORT,
            \IntlDateFormatter::SHORT,
            null,
            null,
            'Y/MM/dd'
        );
    }
}
