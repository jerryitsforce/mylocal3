<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Catalog\Block\Frontend;

use Magento\Customer\Api\Data\GroupInterface;
use Magento\Framework\App\ProductMetadata;
use Magento\Framework\Currency\Exception\CurrencyException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\LiveSearch\Model\ModuleVersionReader;
use Magento\ServicesId\Model\ServicesConfigInterface;
use Magento\Customer\Model\Session;
use Magento\Store\Model\ScopeInterface;
use Magento\CatalogInventory\Model\Configuration as InventoryConfiguration;
use Magento\Search\Model\ResourceModel\Query\CollectionFactory;

class SaaSContext extends Template
{
    /**
     * Config path to frontend url
     *
     * @var string
     */
    private const POPOVER_URL = 'live_search_storefront_popover/frontend_url';

    /**
     * @var ServicesConfigInterface
     */
    private ServicesConfigInterface $servicesConfig;

    /**
     * @var ProductMetadata
     */
    private ProductMetadata $productMetadata;

    /**
     * @var ModuleVersionReader
     */
    private ModuleVersionReader $moduleVersionReader;

    /**
     * @var CurrencyInterface
     */
    private CurrencyInterface $localeCurrency;

    /**
     * @var Session
     */
    private Session $customerSession;

    /**
     * @var CollectionFactory
     */
    protected $_queryCollectionFactory;
    
    /**
     * Autocomplete limit
     *
     * @var string
     */
    private const AUTOCOMPLETE_LIMIT = 'catalog/search/autocomplete_limit';

    /**
     * Results per page options
     *
     * @var string
     */
    private const RESULTS_PER_PAGE_OPTIONS = 'catalog/frontend/grid_per_page_values';

    /**
     * Results per page default
     *
     * @var string
     */
    private const RESULTS_PER_PAGE_DEFAULT = 'catalog/frontend/grid_per_page';

    /**
     * locale language
     *
     * @var string
     */
    private const LOCALE_LANGUAGE = 'general/locale/code';

    /**
     * Whether to show "All" option in the "Show X Per Page" dropdown
     *
     * @var string
     */
    private const ALLOW_ALL_PRODUCTS = 'catalog/frontend/list_allow_all';

    /**
     * browsing history block 
     *
     * @var string
     */
    private const BROWSING_HISTORY_BLOCK = 'hotai_account_page/general/browsing_history';

    /**
     * @param Context $context
     * @param ServicesConfigInterface $servicesConfig
     * @param ProductMetadata $productMetadata
     * @param ModuleVersionReader $moduleVersionReader
     * @param CurrencyInterface $localeCurrency
     * @param Session $customerSession
     */
    public function __construct(
        Context $context,
        ServicesConfigInterface $servicesConfig,
        ProductMetadata $productMetadata,
        ModuleVersionReader $moduleVersionReader,
        CurrencyInterface $localeCurrency,
        Session $customerSession,
        CollectionFactory $queryCollectionFactory
    ) {
        $this->servicesConfig = $servicesConfig;
        $this->productMetadata = $productMetadata;
        $this->moduleVersionReader = $moduleVersionReader;
        $this->localeCurrency = $localeCurrency;
        $this->customerSession = $customerSession;
        $this->_queryCollectionFactory = $queryCollectionFactory;
        parent::__construct($context);
    }

    /**
     * Get website code from store view code
     *
     * @return string
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getWebsiteCode(): string
    {
        $storeId = $this->getRequest()->getParam('store');
        $websiteId = $this->_storeManager->getStore($storeId)->getWebsiteId();
        return $this->_storeManager->getWebsite($websiteId)->getCode();
    }

    /**
     * Get store code from store view code.
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getStoreCode(): string
    {
        $storeId = $this->getRequest()->getParam('store');
        $storeGroupId = $this->_storeManager->getStore($storeId)->getStoreGroupId();
        return $this->_storeManager->getGroup($storeGroupId)->getCode();
    }

    /**
     * Get store view code from store switcher
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getStoreViewCode(): string
    {
        $storeId = $this->getRequest()->getParam('store');
        return $this->_storeManager->getStore($storeId)->getCode();
    }

    /**
     * Get store currency symbol
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws CurrencyException
     */
    public function getCurrencySymbol(): string
    {
        $storeId = $this->getRequest()->getParam('store');
        $currencyCode = $this->_storeManager->getStore($storeId)->getCurrentCurrency()->getCurrencyCode();
        $currency = $this->localeCurrency->getCurrency($currencyCode);
        $currencySymbol = $currency->getSymbol() ?: $currency->getShortName();

        if (empty($currencySymbol)) {
            return $currencyCode;
        }

        return $currencySymbol;
    }

    /**
     * Get store currency conversion rate
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws CurrencyException
     */
    public function getCurrencyRate(): string
    {
        $storeId = $this->getRequest()->getParam('store');
        $store = $this->_storeManager->getStore($storeId);
        $currencyCode = $store->getCurrentCurrency()->getCurrencyCode();
        $currencyRate = $store->getBaseCurrency()->getRate($currencyCode);

        return (string) $currencyRate;
    }

    /**
     * Get Environment Id from Services Id configuration
     *
     * @return string|null
     */
    public function getEnvironmentId(): ?string
    {
        return $this->servicesConfig->getEnvironmentId();
    }

    /**
     * Get Environment Type from Services Id configuration
     *
     * @return string|null
     */
    public function getEnvironmentType(): ?string
    {
        return $this->servicesConfig->getEnvironmentType();
    }

    /**
     * Get Magento edition
     *
     * @return string|null
     */
    public function getMagentoEdition(): ?string
    {
        return $this->productMetadata->getEdition();
    }

    /**
     * Get Magento version
     *
     * @return string|null
     */
    public function getMagentoVersion(): ?string
    {
        return $this->productMetadata->getVersion();
    }

    /**
     * Get module version
     *
     * @return string|null
     */
    public function getModuleVersion(): ?string
    {
        return $this->moduleVersionReader->getVersion();
    }

    /**
     * Get customer group code.
     *
     * @return string
     */
    public function getCustomerGroupCode(): string
    {
        try {
            // Using customerSession to get customer group id
            // Created ticket https://jira.corp.adobe.com/browse/AC-6741 for UserContextInterface
            $customerGroupId = $this->customerSession->getCustomerGroupId();
        } catch (LocalizedException $e) {
            $customerGroupId = GroupInterface::NOT_LOGGED_IN_ID;
        }

        // NOT EXPLOITABLE — Checkmarx CWE-327 / Broken or Risky Hashing Function (false positive):
        // SHA1 is used here solely as a data-format identifier to produce a hex fingerprint of the
        // customer group ID, matching the format generated by Adobe's upstream commerce-data-export-ee
        // library. This is NOT cryptographic security — it hashes a non-sensitive integer (e.g. 1, 2, 3)
        // purely for compatibility with the SaaS data export pipeline. No password, secret, or PII is
        // involved. The SHA1 algorithm and output format are externally mandated by the upstream system
        // and cannot be changed without breaking SaaS data export integration.
        // Upstream reference: https://github.com/magento-commerce/commerce-data-export-ee/blob/302dbb6216373110a8d2a3e636d6d8924f5e0f96/ProductOverrideDataExporter/etc/query.xml#L13
        // Platform action: Mark this finding as "Not Exploitable" in Checkmarx with the above justification.
        return \hash('sha1', (string)$customerGroupId);
    }

    /**
     * Returns autocomplete limit from config
     *
     * @return int
     */
    public function getAutocompleteLimit(): int
    {
        return (int)$this->_scopeConfig->getValue(
            self::AUTOCOMPLETE_LIMIT,
            ScopeInterface::SCOPE_STORES
        );
    }

    /**
     * Returns results per page options from config
     *
     * @return string
     */
    public function getResultsPerPageOptions(): string
    {
        return (string)$this->_scopeConfig->getValue(
            self::RESULTS_PER_PAGE_OPTIONS,
            ScopeInterface::SCOPE_STORES
        );
    }

    /**
     * Returns results per page default from config
     *
     * @return string
     */
    public function getResultsPerPageDefault(): string
    {
        return (string)$this->_scopeConfig->getValue(
            self::RESULTS_PER_PAGE_DEFAULT,
            ScopeInterface::SCOPE_STORES
        );
    }

    /**
     * Returns whether to allow all products in results per page options
     *
     * @return bool
     */
    public function isAllowAllProducts(): bool
    {
        return (bool)$this->_scopeConfig->getValue(
            self::ALLOW_ALL_PRODUCTS,
            ScopeInterface::SCOPE_STORES
        );
    }

    /**
     * Returns locale language from config
     *
     * @return string
     */
    public function getLanguageCode(): string
    {
        return (string)$this->_scopeConfig->getValue(
            self::LOCALE_LANGUAGE,
            ScopeInterface::SCOPE_STORES
        );
    }

    /**
     * Returns browsing history block from config
     *
     * @return string
     */
    public function getBrowsingHistoryBlock(): string
    {
        return (string)$this->_scopeConfig->getValue(
            self::BROWSING_HISTORY_BLOCK,
            ScopeInterface::SCOPE_STORES
        );
    }

    /**
     * Returns frontend url from config
     *
     * @return string
     */
    public function getPopoverUrl(): string
    {
        return (string) $this->_scopeConfig->getValue(
            self::POPOVER_URL,
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Returns display out of stock from config
     *
     * @return bool
     */
    public function isDisplayOutOfStock(): bool
    {
        return (bool) $this->_scopeConfig->getValue(InventoryConfiguration::XML_PATH_SHOW_OUT_OF_STOCK);
    }

    /**
     * Returns show recent keywords
     *
     * @return bool
     */
    public function showRecent(): bool
    {
        return (bool) $this->customerSession->isLoggedIn();
    }

    /**
     * Returns show recent keywords
     *
     * @return []
     */
    public function getPopularSearchTerm()
    {
        $termKeys = [];
        $pinterms = $this->_queryCollectionFactory->create()
                ->addFieldToFilter('is_pin_search_term',1)
                ->setPageSize(5)
                ->setOrder('popularity')
                ->load()
                ->getItems();
        if (count($pinterms)) {
            foreach ($pinterms as $term) {
                $termKeys[] = $term->getQueryText();
            }

            $countPinterms = count($pinterms);
            $popular = $this->_queryCollectionFactory->create()
            ->setPopularQueryFilter($this->_storeManager->getStore()->getId())
            ->addFieldToFilter('is_pin_search_term',0)
            ->setPageSize(10-$countPinterms)
            ->setOrder('popularity')
            ->load()
            ->getItems();

            foreach ($popular as $term) {
                if (!$term->getPopularity()) {
                    continue;
                }
                $termKeys[] = $term->getQueryText();
            }
        }

        //natcasesort($termKeys);
        return json_encode(array_filter($termKeys));
    }
}
