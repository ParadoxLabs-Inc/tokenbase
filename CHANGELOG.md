# ParadoxLabs_TokenBase Changelog

## 4.5.5 - May 11, 2023
- Changed license from proprietary to Apache 2.0. Issues and contributions are welcome on GitHub.
- Fixed hyphenated transaction IDs possibly being sent to payment gateway on refund.

## 4.5.4 - Mar 10, 2023
- Added compatibility for Magento 2.4.6.
- Changed GraphQL data assignment to allow order placement in a separate mutation. (Thanks Alfredo)
- Fixed disabled CC form fields on admin checkout.
- Fixed zero-total checkout handling.
- Fixed GraphQL tokenbase_id handling during order placement. (Thanks Damien, Tony)
- Fixed transaction being voided in error if 'quote failure' event runs despite the order saving successfully. (Thanks Michael)
- Fixed possible duplicate checkout submission by keyboard input.

## 4.5.1 - Apr 13, 2022
- Fixed monolog dependency for 2.4.4.

## 4.5.0 - Apr 6, 2022
- Removed compatibility for Magento 2.2 and below. For anyone updating from Magento 2.2 or below, update this extension to the previous version before updating Magento.
- Added compatibility for Magento 2.4.4 + PHP 8.1.
- Added auto voiding of transactions at checkout when third party code throws an order processing exception.
- Added configuration to change the delay for inactive card pruning.
- Added payment_id index to stored card table to optimize duplicate card checks.
- Added security-related settings to admin checkout configuration.
- Changed card pruning delay from 120 to 180 days to reflect new Authorize.net policy.
- Fixed ability to use TokenBase methods for free orders.
- Fixed ACH tooltip syntax error on My Payment Options.
- Fixed error parameter replacement on checkout for complex error messages. (Thanks Navarr)
- Fixed handling of payment methods on free orders.
- Fixed possible PHP notice in address input processing.
- Fixed various inspection warnings.

## 4.3.8 - Aug 23, 2021
- Fixed 'please enter CVV' validation error when capturing a card modified since order placement, with require CVV enabled.
- Fixed card info not displaying in My Payment Data on `Magento/blank` and derived themes.
- Fixed expired cards not showing any indicator.
- Fixed GraphQL card create/save not syncing to the payment gateway.
- Fixed Magento 2.4.3 compatibility by replacing all deprecated escapeQuote calls. (Magento 2.1 no longer compatible)
- Fixed origData not being preserved when changing card type instance, causing excess data synchronization and saving.
- Fixed post-checkout registration also catching normal customer registration, causing 'unable to load card' errors.
- Fixed transaction info not showing on admin order view on Magento 2.4.2+.

## 4.3.7 - Apr 21, 2021
- Fixed validation error after invoice.
- Fixed internal validation not throwing CommandException.

## 4.3.6 - Mar 31, 2021
- Added profile_id/payment_id gateway tokens to GraphQL card schema.
- Changed 'Payment Data'/'My Payment Data' to 'Payment Options'/'My Payment Options'.
- Fixed checkout validation errors on Magento 2.3.3-2.4 resulting from core bug #28161.
- Fixed errors on void/cancel if card no longer exists.
- Fixed payment failed emails.

## 4.3.4 - Dec 24, 2020
- Added selected-card data to GraphQL cart SelectedPaymentMethod.
- Fixed card association and authorization issues when changing the email on admin checkout.
- Fixed IE11 compatibility issue on checkout form.
- Fixed Magento 2.2 compatibility issue since 4.3.2 (GraphQL reference).
- Fixed payment failed emails by changing checkout exceptions from PaymentException to LocalizedException, to follow

## 4.3.3 - Oct 27, 2020
- Fixed "Credit card number does not match credit card type" on admin checkout.

## 4.3.2 - Oct 20, 2020
- Fixed compatibility issue with Magento 2.4.1 and Klarna 7.1.0 that broke cart and checkout.
- Fixed CVV type validation for stored cards.
- Fixed exceptions on void preventing order cancellation.
- Fixed GraphQL not being considered a frontend area, for client IP handling.
- Fixed stored cards syncing to gateway after refund.

## 4.3.1 - Aug 5, 2020
- Added Magento 2.4 compatibility.
- Fixed ability to repeatedly submit checkout while the CC is being tokenized.
- Fixed 'Invalid payment data' errors with new ACH info on multishipping checkout.

## 4.3.0 - May 20, 2020
- Fixed "Email already exists" error (core bug) after placing an admin order for a new customer and getting a payment failure.
- Fixed possible PHP type error during card saving under rare circumstances.
- Fixed potential false positives in address change detection.
- Fixed saved address dropdowns formatting as HTML.

## 4.2.6 - Feb 19, 2020
- Fixed critical card deletion bug when removing existing duplicate cards in deduplication process.
- Fixed incorrect ACH account number length restriction.

## 4.2.5 - Jan 30, 2020
- Fixed potential admin card edit issues with AJAX requests failing to update the page.
- Fixed card association with register-after-checkout flow on recent Magento 2.2/2.3 versions.
- Fixed Magento 2.3.4 GraphQL compatibility.
- Fixed OSC compatibility issue with checkout button disabled style.
- Fixed possible uncaught exception from invalid card billing address.

## 4.2.4 - Oct 31, 2019
- Fixed a checkout error when Magento is configured with a database prefix.

## 4.2.3 - Oct 25, 2019
- Added GraphQL checkout support.
- Fixed admin card management issues.
- Fixed API card create/update with existing payment tokens.
- Fixed extension attribute handling issues with Magento 2.3.3.
- Fixed reserved order ID not persisting upon error for customer checkouts.

## 4.2.2 - Aug 29, 2019
- Fixed 'enter' submitting checkout despite disabled button.
- Fixed a PHP error on order view with Klarna enabled on Magento 2.3.
- Fixed checkout validation issues and related conflicts with some custom checkouts.
- Fixed CVV tooltip on Magento 2.3 checkout.

## 4.2.1 - Jul 11, 2019
- Fixed admin order form validation issues.
- Fixed form validation when CVV is disabled.
- Fixed gateway syncing on REST card create/update.
- Fixed quality issues for latest Magento coding standards.

## 4.2.0 - Apr 26, 2019
- Added CC type images to card management pages.
- Added GraphQL API support for customer card management.
- Added REST API support for guest and customer card management.
- Added protection to frontend checkout to help prevent abuse. (Will now block after numerous failures.)
- Improved (completely overhauled) form processing and validation.
- Improved codebase by moving common code from gateways into the TokenBase library.
- Fixed ACH JS error on frontend card management.
- Fixed card dedupe logic for payment gateways with no profile IDs.
- Fixed handling of duplicate cards within database records.
- Fixed partially-missing server-side payment validation on account payment save.

## 4.1.6 - Jan 2, 2019
- Fixed template loading on composer installs.

## 4.1.5 - Nov 28, 2018
- Updated composer dependency versions for Magento 2.3.
- Fixed possible CC last4 issue in the presence of separators.

## 4.1.4 - Oct 2, 2018
- Fixed order status handling on ordering versus invoicing for 'save' and 'capture' payment actions.
- Fixed potential card type validation errors by changing separator from dash to space.
- Changed card save to throw PaymentException rather than CouldNotSaveException.

## 4.1.3 - Jul 18, 2018
- Added CC number input formatting.
- Fixed API delete not reaching payment gateway.
- Fixed partial invoicing with reauthorization disabled.

## 4.1.2 - May 15, 2018
- Changed API card delete behavior to queue for deletion before deleting permanently.
- Fixed incorrect OrderCommand argument.
- Fixed possible VirtualType compilation errors.
- Fixed required indicator when phone number is set to not required.

## 4.1.1 - Apr 2, 2018
Fixed a PHP 5.5 compatibility issue.

## 4.1.0 - Mar 22, 2018
- Added support for $0 checkout.
- Improved performance of Manage Cards with many cards and orders (thanks Steve).
- Fixed field validation stripping dashes from addresses.
- Fixed logging issues in Magento 2.2.
- Fixed order status handling on 'save' payment action and some other edge cases.
- Fixed possible unserialize address errors on 4.0 upgrade.
- Fixed possible validation JS errors on CC forms.
- Fixed stored card association on post-register checkout.
- Fixed stored card validation with no expiration date given.
### BACKWARDS-INCOMPATIBLE CHANGES:
- Changed param type of setMethodInstance() in ParadoxLabs\TokenBase\Api\Data\CardInterface.

## 4.0.1 - Sep 25, 2017
- Added tokenbase_id to API order collection load.
- Improved card save/update handling via API.
- Fixed tokenbase_id API ACL.

## 4.0.0 - Sep 11, 2017
- Changed DI proxy argument handling for Magento 2.2 compatibility.
- Changed order status handling to plugin for Magento 2.2 compatibility.
- Changed payment command classnames to fix PHP 7.1 compatibility.
- Fixed admin card 'delete' button deleting rather than queuing deletion.
- Fixed ExtensionAttribute implementation on Card model.
- Fixed possible error on order view if no TokenBase payment methods are present.
- Fixed possible PHP error from improper Address helper inheritance chain.
- Fixed possible PHP error on admin order create in compiled multi-store environments.
- Fixed possible static content deploy issues with template comments.
- Fixed REST API permission handling.
- Fixed restricted order statuses being selectable as payment method 'New Order Status'.

### BACKWARDS-INCOMPATIBLE CHANGES:
- Changed argument type of ParadoxLabs\TokenBase\Api\Data\CardInterface::setExtensionAttributes().
- Changed card 'address' and 'additional' from serialized to JSON storage.
- Changed Proxy constructor arguments throughout module to inject Proxy via DI configuration.
- Removed Unserialize constructor argument from ParadoxLabs\TokenBase\Model\Card\Context.

## 3.1.3 - Aug 3, 2017
- Added split DB support.
- Added settings check for corrupted API credentials.
- Added protection to frontend My Payment Data page to help prevent abuse. (Will now require order history to use, and block after numerous failures.)
- Added browser CC autofill attributes to form fields.
- Fixed validation error on admin checkout with new card.

## 3.1.2 - May 24, 2017
- Fixed order status being overwritten after invoicing an order.
- Refactored Magento_Vault implementation to fix compatibility with Enterprise Cloud Edition.
- Fixed CCV validation for stored cards with 'Require CCV' enabled.
- Fixed config scope issue when checking active payment methods in admin.
- Fixed a possible PHP error on card edit.
- Fixed leading-zero issues on CCV input.

## 3.1.1 - Mar 2, 2017
- Fixed Magento 2.0 compatibility issues.

## 3.1.0 - Feb 17, 2017
- Changes for Marketplace Level 2 extension verification.

## 3.0.4 - Oct 5, 2016
- Fixed 2.1 checkout not displaying payment errors.
- Fixed CCV validation issue on multishipping checkout.
- Fixed transaction info being included on admin-triggered order emails.
- Added card interface compatibility with Magento Vault (2.1+).

## 3.0.3 - Jul 22, 2016
- Compatibility fixes for Magento 2.1.
- Fixed issue with auto-assigning 'pending' order status.
- Fixed compilation errors in 2.0.6.
- Fixed adding a new card on checkout that was previously stored failing to restore it as active.
- Fixed voiding a partially-invoiced order with reauthorization disabled potentially canceling a valid capture.
- Fixed missing error messages on checkout (workaround for apparent core issue).
- Fixed a core bug with Magento failing to apply sort order to transactions, breaking ability to perform online partial captures.
- Fixed a potential API error.
- Fixed a card type error on multishipping checkout.

## 3.0.0 - Nov 16, 2015
- Initial release for Magento 2.
