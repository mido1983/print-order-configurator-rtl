# Manual Plugin Recovery

Use this only if a previous update installed the plugin into the wrong folder or WordPress cannot remove the old plugin folder.

1. Connect to the server by hosting file manager, SFTP, or SSH.
2. Open `wp-content/plugins/`.
3. Remove wrong plugin folders such as:
   - `print-order-configurator-rtl-main`
   - `print-order-configurator-rtl-main (3)`
   - `print-order-configurator-rtl-0.1.7`
4. Keep or recreate only this folder name:
   - `print-order-configurator-rtl`
5. Upload the release asset ZIP named:
   - `print-order-configurator-rtl.zip`
6. Confirm the main plugin file exists at:
   - `wp-content/plugins/print-order-configurator-rtl/print-order-configurator-rtl.php`
7. Activate `Print Order Configurator RTL for WooCommerce` in the WordPress Plugins page.

If WordPress reports that it cannot remove the current version, fix ownership/permissions on:

`wp-content/plugins/print-order-configurator-rtl`

The web server user must be able to delete and replace that folder during plugin updates.
