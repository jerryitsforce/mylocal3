# Mage2 Module Branch8 PointMoneyCollect

    ``branch8/module-pointmoneycollect``

 - [Main Functionalities](#markdown-header-main-functionalities)
 - [Installation](#markdown-header-installation)
 - [Configuration](#markdown-header-configuration)
 - [Specifications](#markdown-header-specifications)
 - [Attributes](#markdown-header-attributes)


## Main Functionalities
PointMoneyCollect

## Installation
\* = in production please use the `--keep-generated` option

### Type 1: Zip file

 - Unzip the zip file in `app/code/Branch8`
 - Enable the module by running `php bin/magento module:enable Branch8_PointMoneyCollect`
 - Apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`

### Type 2: Composer

 - Make the module available in a composer repository for example:
    - private repository `repo.magento.com`
    - public repository `packagist.org`
    - public github repository as vcs
 - Add the composer repository to the configuration by running `composer config repositories.repo.magento.com composer https://repo.magento.com/`
 - Install the module composer by running `composer require branch8/module-pointmoneycollect`
 - enable the module by running `php bin/magento module:enable Branch8_PointMoneyCollect`
 - apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`


## Configuration




## Specifications

 - Plugin
	- afterCollectTotals - Magento\Quote\Model\Quote > Branch8\PointMoneyCollect\Plugin\Magento\Quote\Model\Quote

 - Plugin
	- beforeSetQty - Magento\Quote\Model\Quote\Item > Branch8\PointMoneyCollect\Plugin\Magento\Quote\Model\Quote\Item

 - Plugin
	- aroundCollect - Magento\Quote\Model\Quote\TotalsCollector > Branch8\PointMoneyCollect\Plugin\Magento\Quote\Model\Quote\TotalsCollector


## Attributes



