<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\ParentOrder\Pdf;
/**
 *
 */
abstract class AbstractPdf extends \Magento\Sales\Model\Order\Pdf\AbstractPdf
{

    protected $_localeResolver = null;

    /**
     * @return \Magento\Framework\Locale\ResolverInterface
     */
    protected function getLocaleResolver()
    {
        if ($this->_localeResolver == null) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $this->_localeResolver = $objectManager->get(\Magento\Framework\Locale\ResolverInterface::class);
        }
        return $this->_localeResolver;
    }
}
