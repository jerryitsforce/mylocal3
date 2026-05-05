# Mage2 Module Branch8 Every8D

    ``branch8/module-every8d``

 - [Main Functionalities](#markdown-header-main-functionalities)
 - [Installation](#markdown-header-installation)
 - [Configuration](#markdown-header-configuration)
 - [Specifications](#markdown-header-specifications)
 - [Attributes](#markdown-header-attributes)


## Main Functionalities
Every8D SMS Service

## Installation
\* = in production please use the `--keep-generated` option

### Type 1: Zip file

 - Unzip the zip file in `app/code/Branch8`
 - Enable the module by running `php bin/magento module:enable Branch8_Every8D`
 - Apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`

### Type 2: Composer

 - Make the module available in a composer repository for example:
    - private repository `repo.magento.com`
    - public repository `packagist.org`
    - public github repository as vcs
 - Add the composer repository to the configuration by running `composer config repositories.repo.magento.com composer https://repo.magento.com/`
 - Install the module composer by running `composer require branch8/module-every8d`
 - enable the module by running `php bin/magento module:enable Branch8_Every8D`
 - apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`


## Configuration

 - sms_user (every8d/general/sms_user)

 - sms_password (every8d/general/sms_password)

 - service_url (every8d/general/service_url)


## Specifications




## Attributes



