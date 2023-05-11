[![Latest Stable Version](https://poser.pugx.org/paradoxlabs/tokenbase/v/stable)](https://packagist.org/packages/paradoxlabs/tokenbase)
[![License](https://poser.pugx.org/paradoxlabs/tokenbase/license)](https://packagist.org/packages/paradoxlabs/tokenbase)
[![Total Downloads](https://poser.pugx.org/paradoxlabs/tokenbase/downloads)](https://packagist.org/packages/paradoxlabs/tokenbase)

<p align="center">
    <a href="https://www.paradoxlabs.com"><img alt="ParadoxLabs" src="https://paradoxlabs.com/wp-content/uploads/2020/02/pl-logo-canva-2.png" width="250"></a>
</p>

TokenBase is the foundational package for most ParadoxLabs extensions for Magento&reg;. It provides a tokenized card storage mechanism that is similar to but more featureful than Magento_Vault, and abstract components of a payment gateway implementation for Magento built around those stored cards.

Requirements
------------

* Magento 2.3 or 2.4 (or equivalent version of Adobe Commerce, Adobe Commerce Cloud, or Mage-OS)
* PHP 7.3, 7.4, 8.0, 8.1, or 8.2
* composer 1 or 2

Features
--------

* Tokenized card storage
* Frontend and Admin Panel customer card management
* Stored Card service layer
* REST API coverage
* SOAP API coverage
* GraphQL API coverage
* Separated transaction logging to `tokenbase.log`
* Abstract payment method implementation
* Abstract payment gateway implementation

Installation and Usage
============

In SSH at your Magento base directory, run:

    composer require paradoxlabs/tokenbase
    php bin/magento module:enable ParadoxLabs_TokenBase
    php bin/magento setup:upgrade

**NOTE**: This is a shared library for other modules to build upon. It does not provide any functionality of its own and cannot be used without a separate supporting Magento extension such as [ParadoxLabs' Authorize.net CIM](https://github.com/ParadoxLabs-Inc/authnetcim) or [CyberSource](https://github.com/ParadoxLabs-Inc/cybersource) payment methods for Magento.

Changelog
=========

Please see [CHANGELOG.md](https://github.com/ParadoxLabs-Inc/tokenbase/blob/master/CHANGELOG.md).

Support
=======

This module is provided free and without support of any kind. You may report issues you've found in the module, and we will address them as we are able, but **no support will be provided here.**

**DO NOT include any API keys, credentials, or customer-identifying in issues, pull requests, or comments. Any personally identifying information will be deleted on sight.**

If you need personal support services, please [buy an extension support plan from ParadoxLabs](https://store.paradoxlabs.com/support-renewal.html), then open a ticket at [support.paradoxlabs.com](https://support.paradoxlabs.com).

Contributing
============

Please feel free to submit pull requests with any contributions. We welcome and appreciate your support, and will acknowledge contributors.

This module is maintained by ParadoxLabs for use in ParadoxLabs extensions. We make no guarantee of accepting contributions, especially any that introduce architectural changes.

License
=======

This module is licensed under [APACHE LICENSE, VERSION 2.0](https://github.com/ParadoxLabs-Inc/tokenbase/blob/master/LICENSE).
