# Duplicate Email Module for Magento 2

## Overview

This module allows for the duplication of customer emails in Magento 2, with specific conditions based on the customer platform. For regular customers, the module checks that the platform is not 'seller'. For vendors, the module ensures that the platform is 'seller'.

## Modifications

- Overwritten the file: `app/code/Branch8/HotaiAuth/Model/ResourceModel/Customer.php`
- Removed the email index from the customer entity table: `app/code/Branch8/HotaiAuth/Setup/Patch/Schema/CustomerDropIndex.php`

## Installation

1. **Copy the Module:**
   Copy the module code into the Magento `app/code/Branch8/HotaiAuth` directory.

2. **Run Setup Upgrade:**
   Execute the following command to update the Magento database schema:
   ```bash
   bin/magento setup:upgrade
## Details
Overwritten File: Customer.php
The following modifications have been made to the Customer.php file:

The loadByEmail method has been updated to check the platform. If the platform is 'seller', it ensures that duplicate emails are allowed only for sellers. If the platform is not 'seller', it allows duplicate emails for other platforms.
Removed Email Index: CustomerDropIndex.php
The CustomerDropIndex.php file contains the logic to drop the unique index on the email column in the customer entity table. This allows the database to store duplicate email addresses based on the platform condition.

Usage
Customer Registration:

When a customer registers, the module will check the platform attribute.
If the platform is 'seller', it will allow duplicate emails for sellers.
If the platform is not 'seller', it will allow duplicate emails for other platforms.
Vendor Registration:

When a vendor registers, the module ensures that the platform is set to 'seller' and allows duplicate emails accordingly.
