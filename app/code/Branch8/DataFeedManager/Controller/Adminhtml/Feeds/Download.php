<?php

declare(strict_types=1);

namespace Branch8\DataFeedManager\Controller\Adminhtml\Feeds;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Catalog\Api\ProductAttributeOptionManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Model\Entity\AttributeFactory;
use Magento\Eav\Model\Entity\TypeFactory as AttributeTypeFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection as AttributeOptionValueCollection;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Wyomind\DataFeedManager\Controller\Adminhtml\Feeds\AbstractFeeds;
use Wyomind\DataFeedManager\Helper\Attributes as AttributesHelper;
use Wyomind\DataFeedManager\Helper\Data;
use Wyomind\DataFeedManager\Helper\Parser as ParserHelper;
use Wyomind\DataFeedManager\Model\Feeds;
use Wyomind\DataFeedManager\Model\Product\Collection as ProductCollection;
use Wyomind\DataFeedManager\Model\ResourceModel\Variables\CollectionFactory as VariablesCollectionFactory;
use Wyomind\Framework\Helper\Download as FrameworkExport;
use Wyomind\Framework\Helper\Heartbeat;

class Download extends AbstractFeeds
{
    /**
     * @var FileFactory
     */
    private FileFactory $fileFactory;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param Registry $coreRegistry
     * @param AttributeFactory $attributeFactory
     * @param PageFactory $resultPageFactory
     * @param ForwardFactory $resultForwardFactory
     * @param Config $config
     * @param DirectoryList $directoryList
     * @param Reader $directoryReader
     * @param ProductCollection $productCollection
     * @param AttributeOptionValueCollection $attributeOptionValueCollection
     * @param ProductFactory $productFactory
     * @param Heartbeat $framework
     * @param FrameworkExport $frameworkExport
     * @param Data $dfmHelper
     * @param Feeds $dfmModel
     * @param AttributeTypeFactory $attributeTypeFactory
     * @param ParserHelper $parserHelper
     * @param AttributeRepositoryInterface $attributeRepository
     * @param ProductRepositoryInterface $productRepository
     * @param ProductAttributeOptionManagementInterface $productAttributeRepository
     * @param ReadFactory $directoryRead
     * @param VariablesCollectionFactory $variablesCollectionFactory
     * @param AttributesHelper $attributesHelper
     * @param FileFactory $fileFactory
     */
    public function __construct(
        Context                                   $context,
        Registry                                  $coreRegistry,
        AttributeFactory                          $attributeFactory,
        PageFactory                               $resultPageFactory,
        ForwardFactory                            $resultForwardFactory,
        Config                                    $config,
        DirectoryList                             $directoryList,
        Reader                                    $directoryReader,
        ProductCollection                         $productCollection,
        AttributeOptionValueCollection            $attributeOptionValueCollection,
        ProductFactory                            $productFactory,
        Heartbeat                                 $framework,
        FrameworkExport                           $frameworkExport,
        Data                                      $dfmHelper,
        Feeds                                     $dfmModel,
        AttributeTypeFactory                      $attributeTypeFactory,
        ParserHelper                              $parserHelper,
        AttributeRepositoryInterface              $attributeRepository,
        ProductRepositoryInterface                $productRepository,
        ProductAttributeOptionManagementInterface $productAttributeRepository,
        ReadFactory                               $directoryRead,
        VariablesCollectionFactory                $variablesCollectionFactory,
        AttributesHelper                          $attributesHelper,
        FileFactory                               $fileFactory
    ) {
        parent::__construct($context, $coreRegistry, $attributeFactory, $resultPageFactory, $resultForwardFactory,
            $config, $directoryList, $directoryReader, $productCollection, $attributeOptionValueCollection,
            $productFactory, $framework, $frameworkExport, $dfmHelper, $dfmModel, $attributeTypeFactory,
            $parserHelper, $attributeRepository, $productRepository, $productAttributeRepository, $directoryRead,
            $variablesCollectionFactory, $attributesHelper);
        $this->fileFactory = $fileFactory;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            $model = $this->_objectManager->create($this->model);
            $model->load($id);

            $path = trim($model->getPath(), '/');
            $name = $model->getName();
            $ext = $this->dfmHelper->getExtFromType($model->getType());

            $fileName = ltrim($path . '/' . $name . $ext, '/');

            $rootDir = $this->_directoryList->getPath(DirectoryList::ROOT);
            $filePath = $rootDir . '/' . $fileName;

            if (!file_exists($filePath)) {
                $this->messageManager->addErrorMessage(__($this->errorDoesntExist));
                return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/');
            }

            return $this->fileFactory->create(
                basename($fileName),
                @file_get_contents($filePath),
                DirectoryList::VAR_DIR,
                'application/xml'
            );
        } else {
            $this->messageManager->addErrorMessage(__($this->errorDoesntExist));
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/');
        }
    }
}
