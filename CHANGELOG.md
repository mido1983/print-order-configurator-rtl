# Changelog

## 0.1.9

- Added a PowerShell build script for the correct `print-order-configurator-rtl.zip` package structure.
- Changed the GitHub updater to require the safe release asset `print-order-configurator-rtl.zip` instead of GitHub source ZIP files.
- Improved updater install-folder normalization and added admin warnings for unsafe release packages.
- Added manual recovery instructions for wrong plugin folders and filesystem permission failures.

## 0.1.8

- Added the remaining HOTFIX scoped frontend stabilization selectors.
- Marked `.col-md-6` gallery columns when the configurator is active.
- Added exact PrintX `.tp-product-details-action-item-wrapper.d-flex` override and option-grid aliases.
- Preserved GitHub updater manual check and cache controls.

## 0.1.7

- Added a scoped gallery column marker for PrintX product rows.
- Rebalanced desktop layout to a 58% gallery and 42% configurator column ratio.
- Ensured gallery images scale to the enlarged column without cropping and preserved mobile stacking.
- Fixed GitHub update detection and added manual update check/cache controls in admin settings.

## 0.1.6

- Added scoped active-product layout classes for PrintX product pages.
- Fixed remaining narrow width constraints from the product action quantity host.
- Matched configurator, quantity, and add-to-cart widths while keeping the product image visible.

## 0.1.5

- Fixed PrintX product action flex wrapper layout so the configurator spans full width.
- Kept the add-to-cart button below the configurator instead of beside it.

## 0.1.4

- Replaced unreliable native print option dropdowns with lightweight RTL radio controls.
- Kept the existing `poc_rtl[options][...]` field names so selections continue to reach cart and order metadata.
- Improved option button click/focus states and reduced theme overlay risk.

## 0.1.3

- Added a scoped fallback that translates theme hardcoded Add To Cart labels on Hebrew configurator product pages.
- Improved Hebrew frontend detection for WPML `?lang=he` product URLs.

## 0.1.2

- Fixed product page layout regression.
- Removed unsafe gallery width override.
- Restored WooCommerce product gallery visibility.
- Improved add-to-cart button placement inside the purchase flow.

## 0.1.1

- Improved frontend configurator layout.
- Added GitHub-based update support.

## 0.1.0

- Initial MVP implementation:
  - WooCommerce dependency check.
  - Product-level print configurator settings.
  - RTL frontend configurator.
  - Add-to-cart validation.
  - Cart and order metadata.
  - Secure upload validation and protected admin downloads.
  - Design service fee.
  - Admin order panel and internal workflow status.
