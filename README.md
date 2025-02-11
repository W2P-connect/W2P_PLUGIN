=== W2P: Pipedrive CRM Integration for WooCommerce ===
**Contributors:** Tristan  
**Tags:** pipedrive, crm, woocommerce, integration, automation  
**Requires PHP:** 8.0  
**Requires at least:** 6.2  
**Tested up to:** 6.7  
**Stable tag:** 1.0.0  
**License:** GPLv3 or later  
**License URI:** http://www.gnu.org/licenses/gpl-3.0.html

<<<<<<< HEAD
Thank you for using the W2P Plugin! For detailed setup instructions, please visit our official setup guide:  
[Setup Guide](https://www.woocommerce-to-pipedrive.com/setup-guide)

## Releases

### Version 1.0.1 (Dec 13, 2024)

- **User Interface Optimization**:
  - Improved pagination system.
  - Enhanced UX with the addition of a search field by source ID in the history.
  - Adjusted interface parameters for better usability.
- **Cart Functionality**:
  - Beta testing for the "Updated Cart" feature.
- **Error Handling**:
  - Updated traceback parsing to correctly retrieve the last error instead of the first.
- **Query Management**:
  - Adjusted order display in case of missing `target_id`.
  - Added proper `deal_id` support for orders.
  - Better management of dates and timezone differences (switched from `date` to `gmdate`).

This version brings significant improvements in functionality, error handling, and user experience. Thank you for your continued support!
=======
**Integrate your WooCommerce store with Pipedrive CRM** to streamline your sales process and automate customer relationship management.

== Description ==

**W2P: WooCommerce to Pipedrive Integration** is a powerful plugin designed to seamlessly connect your WooCommerce store with **Pipedrive CRM**.  
Automatically **sync your orders, customers, and products**, ensuring your sales team has access to the most **up-to-date information**, enabling better decision-making and customer interactions.

**Learn More here** :  [https://woocommerce-to-pipedrive.com/](https://woocommerce-to-pipedrive.com/)

== Features ==

- **Seamless WooCommerce to Pipedrive Sync:** Automatically sync orders, customers, and products.
- **Load Custom Fields from Pipedrive:** Automatically fetch all your Pipedrive settings, such as **custom fields, users, and pipelines**, making it easier to configure your WooCommerce integration accurately.
- **WooCommerce Hooks Integration:** Trigger and configure various user actions to send data to Pipedrive.
- **Complete site automation:** Sync previous customers and orders made before the plugin installation.
- **Customizable Field Values:** Customize field values for precise data mapping to Pipedrive, use conditions and additional settings to perfectly match your specific needs.
- **Access to Historical Sync:** Access past sync to verify data integrity and adjust settings.
- **Order Status Tracking:** Track WooCommerce order status within Pipedrive.
- **Multiple Pipedrive Pipelines:** Assign WooCommerce orders to specific Pipedrive pipelines.
- **Error Logging & Debugging:** Monitor synchronization errors with detailed logs.

== Use this plugin to ==

- Gain a **centralized view** of your WooCommerce sales in Pipedrive.
- **Automate sales processes** and follow-ups.
- Enhance **customer relationship management**.
- **Analyze and track sales performance** with better insights.

== Installation ==

### Automatic Installation

1. Go to your **WordPress dashboard**.
2. Navigate to **"Plugins > Add New"**.
3. Search for **"W2P WooCommerce to Pipedrive"**.
4. Click **"Install Now"** and then **"Activate"**.

== Screenshots ==

1. **Selection of available hooks for synchronization triggers**  
   Screenshot of the settings page where users can choose various hooks to trigger synchronization processes.

2. **Synchronization history access.**  
   Overview of the **synchronization history**, showing past wordpress data sync attempts and their status within the plugin interface

3. **Custom field mapping interface.**  
   Interface showing how users can customize field mappings.

== Frequently Asked Questions ==

**Q: How can I set up the plugin?**  
You can find all our setup tutorials on this page:  
[https://woocommerce-to-pipedrive.com/setup-guide](https://woocommerce-to-pipedrive.com/setup-guide)

**Q: What data gets synced to Pipedrive?**  
The plugin syncs order details, customer information, product details, and custom metadata.

**Q: Can I customize field mappings?**  
Yes, the plugin allows you to customize mappings between WooCommerce and Pipedrive fields.

**Q: What happens if the sync fails?**  
The plugin provides **detailed logs** and **email notifications** for failed sync attempts.

**Q: Can I use the plugin for free?**  
Yes, you can try the plugin for free with a **15-day trial period**.

**Q: Does the plugin sync previous data?**  
Yes, the plugin supports syncing customers and orders made before the installation.  
   This feature is included in the **15-day free trial**.

== Changelog ==

**= 1.0.0 - 2025-01-22 =**  

- Initial release with order, customer, and product sync.  
- Added **custom field mapping** functionality.  
- Implemented **logging and error handling**.  
- Tested compatibility with **WooCommerce 9.6.0**.

== Upgrade Notice ==

**= 1.0.0 =**  
Initial release. Please configure your API settings under **WooCommerce > Settings > W2P Integration** after installation.
>>>>>>> dev
