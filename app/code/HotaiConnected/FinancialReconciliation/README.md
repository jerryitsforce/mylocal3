# Mage2 Module HotaiConnected FinancialReconciliation

    ``hotaiconnected/module-financialreconciliation``

 - [Main Functionalities](#markdown-header-main-functionalities)
 - [Installation](#markdown-header-installation)
 - [Configuration](#markdown-header-configuration)
 - [Specifications](#markdown-header-specifications)
 - [Attributes](#markdown-header-attributes)


## Main Functionalities
for financial reconciliation

## Installation
\* = in production please use the `--keep-generated` option

### Type 1: Zip file

 - Unzip the zip file in `app/code/HotaiConnected`
 - Enable the module by running `php bin/magento module:enable HotaiConnected_FinancialReconciliation`
 - Apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`

### Type 2: Composer

 - Make the module available in a composer repository for example:
    - private repository `repo.magento.com`
    - public repository `packagist.org`
    - public github repository as vcs
 - Add the composer repository to the configuration by running `composer config repositories.repo.magento.com composer https://repo.magento.com/`
 - Install the module composer by running `composer require hotaiconnected/module-financialreconciliation`
 - enable the module by running `php bin/magento module:enable HotaiConnected_FinancialReconciliation`
 - apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`


## Configuration




## Specifications

 - API Endpoint
	- POST - HotaiConnected\FinancialReconciliation\Api\ReconciliationManagementInterface > HotaiConnected\FinancialReconciliation\Model\ReconciliationManagement

 - API Endpoint
	- GET - HotaiConnected\FinancialReconciliation\Api\ReconciliationManagementInterface > HotaiConnected\FinancialReconciliation\Model\ReconciliationManagement


## Attributes



