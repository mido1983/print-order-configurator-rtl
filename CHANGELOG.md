# Changelog

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
