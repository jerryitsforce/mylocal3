# Mage2 Module Branch8 LimitPurchased

    ``branch8/module-limitpurchased``

 - [Main Functionalities](#markdown-header-main-functionalities)
 - [Installation](#markdown-header-installation)
 - [Configuration](#markdown-header-configuration)
 - [Specifications](#markdown-header-specifications)
 - [Attributes](#markdown-header-attributes)


## Main Functionalities
LimitPurchased

## Installation
\* = in production please use the `--keep-generated` option

### Type 1: Zip file

 - Unzip the zip file in `app/code/Branch8`
 - Enable the module by running `php bin/magento module:enable Branch8_LimitPurchased`
 - Apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`

### Type 2: Composer

 - Make the module available in a composer repository for example:
    - private repository `repo.magento.com`
    - public repository `packagist.org`
    - public github repository as vcs
 - Add the composer repository to the configuration by running `composer config repositories.repo.magento.com composer https://repo.magento.com/`
 - Install the module composer by running `composer require branch8/module-limitpurchased`
 - enable the module by running `php bin/magento module:enable Branch8_LimitPurchased`
 - apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`


## Configuration




## Specifications

 - Helper
	- Branch8\LimitPurchased\Helper\Data


## Attributes

 - Product - Enable Limit Purchased (enable_limit_purchased)

 - Product - Limit Purchased Qty (limit_purchased_qty)

 - Product - Limit Purchased Customer Group (limit_purchased_customer_group)

 - Product - Limit Purchased Start Time (limit_purchased_start_time)

 - Product - Limit Purchased End Time (limit_purchased_end_time)

