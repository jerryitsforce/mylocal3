<?php
namespace Branch8\Cms\Helper;

use Magento\Cms\Model\Page\CustomLayoutManagerInterface;
use Magento\Cms\Model\Page\CustomLayoutRepositoryInterface;
use Magento\Cms\Model\Page\IdentityMap;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\Page as ResultPage;

class Page extends \Magento\Cms\Helper\Page
{
    /**
     * @var CustomLayoutManagerInterface
     */
    private $customLayoutManager;
    /**
     * @var CustomLayoutRepositoryInterface
     */
    private $customLayoutRepo;

    /**
     * @var IdentityMap
     */
    private $identityMap;

    protected $mobileDetect;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Cms\Model\Page $page,
        \Magento\Framework\View\DesignInterface $design,
        \Magento\Cms\Model\PageFactory $pageFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Branch8\HotaiCore\Model\Detection\MobileDetect $mobileDetect,
        ?CustomLayoutManagerInterface $customLayoutManager = null,
        ?CustomLayoutRepositoryInterface $customLayoutRepo = null,
        ?IdentityMap $identityMap = null
    ) {
        parent::__construct($context, $messageManager, $page, $design, $pageFactory, $storeManager, $localeDate, 
            $escaper, $resultPageFactory,$customLayoutManager, $customLayoutRepo, $identityMap);
        $this->customLayoutManager = $customLayoutManager
            ?? ObjectManager::getInstance()->get(CustomLayoutManagerInterface::class);
        $this->customLayoutRepo = $customLayoutRepo
            ?? ObjectManager::getInstance()->get(CustomLayoutRepositoryInterface::class);
        $this->identityMap = $identityMap ?? ObjectManager::getInstance()->get(IdentityMap::class);

        $this->mobileDetect = $mobileDetect;
    }

    public function prepareResultPage(ActionInterface $action, $pageId = null)
    {
        if ($pageId !== null && $pageId !== $this->_page->getId()) {
            $delimiterPosition = strrpos((string)$pageId, '|');
            if ($delimiterPosition) {
                $pageId = substr($pageId, 0, $delimiterPosition);
            }

            $this->_page->setStoreId($this->_storeManager->getStore()->getId());
            if (!$this->_page->load($pageId)) {
                return false;
            }
        }

        if (!$this->_page->getId()) {
            return false;
        }

        /** Validate App/ web */
        $pageVisibility = $this->_page->getData('visibility');
        $currentVisibility = $this->mobileDetect->isHotaiApp();
        if($currentVisibility){
            if(!in_array($pageVisibility, [\Branch8\Cms\Model\Config\Source\CmsAgent::TYPE_UNRETRICTED, \Branch8\Cms\Model\Config\Source\CmsAgent::TYPE_APP])){
                return false;
            }
        }else{/** Web */
            if(!in_array($pageVisibility, [\Branch8\Cms\Model\Config\Source\CmsAgent::TYPE_UNRETRICTED, \Branch8\Cms\Model\Config\Source\CmsAgent::TYPE_WEB])){
                return false;
            }
        }

        $this->identityMap->add($this->_page);

        $inRange = $this->_localeDate->isScopeDateInInterval(
            null,
            $this->_page->getCustomThemeFrom(),
            $this->_page->getCustomThemeTo()
        );

        if ($this->_page->getCustomTheme()) {
            if ($inRange) {
                $this->_design->setDesignTheme($this->_page->getCustomTheme());
            }
        }
        /** @var ResultPage $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $this->setLayoutType($inRange, $resultPage);
        $resultPage->addHandle('cms_page_view');
        $pageHandles = [
            'id' => $this->_page->getIdentifier() === null ? '' : str_replace('/', '_', $this->_page->getIdentifier())
        ];
        //Selected custom updates.
        try {
            $this->customLayoutManager->applyUpdate(
                $resultPage,
                $this->customLayoutRepo->getFor($this->_page->getId())
            );
            // phpcs:disable Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
        } catch (NoSuchEntityException $exception) {
            //No custom layout selected
        }

        $resultPage->addPageLayoutHandles($pageHandles);

        $this->_eventManager->dispatch(
            'cms_page_render',
            ['page' => $this->_page, 'controller_action' => $action, 'request' => $this->_getRequest()]
        );

        if ($this->_page->getCustomLayoutUpdateXml() && $inRange) {
            $layoutUpdate = $this->_page->getCustomLayoutUpdateXml();
        } else {
            $layoutUpdate = $this->_page->getLayoutUpdateXml();
        }
        if (!empty($layoutUpdate)) {
            $resultPage->getLayout()->getUpdate()->addUpdate($layoutUpdate);
        }

        $contentHeadingBlock = $resultPage->getLayout()->getBlock('page_content_heading');
        if ($contentHeadingBlock) {
            $contentHeading = $this->_escaper->escapeHtml($this->_page->getContentHeading());
            $contentHeadingBlock->setContentHeading($contentHeading);
        }

        return $resultPage;
    }
}
