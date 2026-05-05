<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       09/02/2026
 */

namespace Branch8\MarketplaceProduct\Model\PostSaveProcessor;

use Magento\Framework\App\RequestInterface;

class Composite
{
    private array $processors;

    /**
     * @param array $processors
     */
    public function __construct(
        array $processors = []
    )
    {
        $this->processors = $processors;
    }

    /**
     * @param RequestInterface $request
     * @param int $productId
     * @param $wholeData
     * @return array|mixed
     */
    public function process(RequestInterface $request, int $productId, &$wholeData = [])
    {
        /**
         * @var $processor PostSaveProcessorInterface
         */
        foreach ($this->processors as $processor) {
            if ($processor instanceof PostSaveProcessorInterface) {
                $processor->process($request, $productId, $wholeData);
            }
        }
        return $wholeData;
    }
}
