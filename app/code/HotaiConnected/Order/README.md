# Mage2 Module HotaiConnected Order

    ``hotaiconnected/module-order``

 - [Main Functionalities](#markdown-header-main-functionalities)
 - [Installation](#markdown-header-installation)
 - [Configuration](#markdown-header-configuration)
 - [Specifications](#markdown-header-specifications)
 - [Attributes](#markdown-header-attributes)


## Main Functionalities
order for 1.0 order 

## Installation
\* = in production please use the `--keep-generated` option

### Type 1: Zip file

 - Unzip the zip file in `app/code/HotaiConnected`
 - Enable the module by running `php bin/magento module:enable HotaiConnected_Order`
 - Apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`

### Type 2: Composer

 - Make the module available in a composer repository for example:
    - private repository `repo.magento.com`
    - public repository `packagist.org`
    - public github repository as vcs
 - Add the composer repository to the configuration by running `composer config repositories.repo.magento.com composer https://repo.magento.com/`
 - Install the module composer by running `composer require hotaiconnected/module-order`
 - enable the module by running `php bin/magento module:enable HotaiConnected_Order`
 - apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`


## Configuration




## Specifications

 - API Endpoint
	- POST - HotaiConnected\Order\Api\OrderManagementInterface > HotaiConnected\Order\Model\OrderManagement

 - API Endpoint
	- GET - HotaiConnected\Order\Api\OrderManagementInterface > HotaiConnected\Order\Model\OrderManagement


## Attributes



