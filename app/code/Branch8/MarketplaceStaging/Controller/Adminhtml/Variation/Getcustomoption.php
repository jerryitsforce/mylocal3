<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\MarketplaceStaging\Controller\Adminhtml\Variation;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Api\Data\ProductCustomOptionInterfaceFactory;

class Getcustomoption extends \Magento\Backend\App\Action implements HttpPostActionInterface
{

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;
    /**
     * @var Json
     */
    protected $serializer;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ProductCustomOptionInterfaceFactory
     */
    protected $productCustomOption;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param Json $json
     * @param LoggerInterface $logger
     * @param ProductCustomOptionInterfaceFactory|null $productCustomOption
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        Json $json,
        LoggerInterface $logger,
        ProductCustomOptionInterfaceFactory $productCustomOption = null
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->serializer = $json;
        $this->logger = $logger;
        $this->productCustomOption = $productCustomOption ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(ProductCustomOptionInterfaceFactory::class);
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        try {
            $params = $this->getRequest()->getParam('product');
            $productOptions = [];
            if (isset($params['options'])) {
                $productOptions = $params['options'];
            }
            $data = $this->manageProductOptionData($productOptions);
            
            $resultJson = $this->resultJsonFactory->create();
            return $resultJson->setData($data);
        } catch (LocalizedException $e) {
            $resultJson = $this->resultJsonFactory->create();
            return $resultJson->setData(['error' => true, 'message' => $e->getMessage()]);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $resultJson = $this->resultJsonFactory->create();
            return $resultJson->setData(['error' => true, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Merge product and default options for product.
     *
     * @param array $productOptions   product options
     * @param array $overwriteOptions default value options
     *
     * @return array
     */
    public function mergeProductOptions($productOptions, $overwriteOptions)
    {
        if (!is_array($productOptions)) {
            return [];
        }

        if (!is_array($overwriteOptions)) {
            return $productOptions;
        }

        foreach ($productOptions as $index => $option) {
            $optionId = $option['option_id'];
            if (!isset($overwriteOptions[$optionId])) {
                continue;
            }

            foreach ($overwriteOptions[$optionId] as $fieldName => $overwrite) {
                if ($overwrite && isset($option[$fieldName]) && isset($option['default_'.$fieldName])) {
                    $productOptions[$index][$fieldName] = $option['default_'.$fieldName];
                }
            }
        }

        return $productOptions;
    }

    public function manageProductOptionData($productOptions)
    {
        $customOptions = [];
        if ($productOptions) {
            // mark custom options that should to fall back to default value
            $options = $this->mergeProductOptions(
                $productOptions,
                []
            );

            foreach ($options as $customOptionData) {
                if (empty($customOptionData['is_delete'])) {
                    if (empty($customOptionData['option_id'])) {
                        $customOptionData['option_id'] = null;
                    }
                    if (isset($customOptionData['values'])) {
                        $customOptionData['values'] = array_filter(
                            $customOptionData['values'],
                            function ($valueData) {
                                return empty($valueData['is_delete']);
                            }
                        );
                        $values = $customOptionData['values'];
                        usort($values, function($a, $b) {
                            return (int)$a['sort_order'] <=> (int)$b['sort_order'];
                        });
                        $customOptionData['values'] = $values;
                    }
                    $customOptions[] = $customOptionData;
                }
            }
        }
        return $customOptions;
    }

}

