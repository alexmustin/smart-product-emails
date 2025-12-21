=== Smart Product Emails ===
Contributors: alexmustin
Author: alexmustin
Author URI: https://alexmustin.com/
Tags: woocommerce emails, custom emails, product emails, per product, product specific emails, targeted emails
Requires at least: 6.8
Tested up to: 6.9
Requires PHP: 8.2
Stable tag: 0.5.0
Version: 0.5.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The complete email marketing suite for WooCommerce store owners who want to communicate smarter, not harder.

== Description ==

Stop sending generic order confirmation emails. **Smart Product Emails** automatically adds custom, product-specific content to your WooCommerce Processing emails based on what customers purchase.

Whether you need to include setup instructions, warranty information, product care tips, or promotional offers, this plugin delivers the right message to the right customer automatically — all within WooCommerce's native email system.

== What You Get (Free Version) ==

**Unlimited Custom Messages**
Create as many product-specific email messages as you need. No limits, no restrictions.

**Product-Based Targeting**
Assign different messages to different products. When a customer purchases a product, your custom message automatically appears in their Processing order email.

**Flexible Content Placement**
Choose exactly where your message appears in the email: at the top, before the order details, after the order details, after customer information, or at the bottom.

**40+ Dynamic Placeholders**
Personalize messages with customer names, order numbers, product details, shipping addresses, order totals, and more — no coding required. Examples: `{customer_first_name}`, `{order_number}`, `{product_name}`, `{order_total}`.

**Smart Duplicate Prevention**
The plugin automatically ensures the same message never appears twice in a single email, even when customers order multiple quantities.

**Built-in Error Logging**
Diagnose issues quickly with the built-in error log that captures PHP errors, AJAX failures, and email sending problems. Filter, search, and export logs directly from your WordPress admin.

**Future-Proof Technology**
Fully compatible with WooCommerce High-Performance Order Storage (HPOS) for optimal performance and reliability.

== How It Works ==
1. **Create a Message** – Write your custom content using WordPress's Classic Editor or HTML
2. **Assign to Products** – Attach the message to any WooCommerce product from the Product Data panel
3. **Choose Placement** – Select where in the Processing email your content should appear
4. **Done** – Your custom messages automatically appear in customer emails when they purchase those products

== Real-World Use Cases ==

**Reduce Support Tickets**
- Automatically include product setup guides and FAQ links
- Send troubleshooting resources before customers need to ask
- Provide warranty registration information at point of purchase

**Increase Customer Lifetime Value**
- Recommend complementary products based on purchase history
- Share exclusive offers for repeat customers
- Build anticipation for upcoming product launches

**Enhance Customer Experience**
- Deliver personalized onboarding sequences for complex products
- Include return policies and guarantees specific to purchased items
- Provide VIP treatment messaging for high-value orders

**Drive Engagement**
- Link to product registration forms and loyalty programs
- Invite customers to communities and user groups
- Request reviews and feedback at optimal timing

== Why Choose Smart Product Emails? ==

**No Monthly Fees**
Unlike expensive third-party email platforms, Smart Product Emails is completely free. No subscriptions, no per-email costs, no hidden charges.

**Works Within WooCommerce**
Your messages are delivered through WooCommerce's native email system, maintaining brand consistency and customer trust. No external services required.

**Simple to Use**
If you can edit a WordPress page, you can create custom email messages. No coding knowledge needed, no complicated setup.

**Set It and Forget It**
Once you've assigned messages to products, everything runs automatically. Your personalized emails send 24/7 without any manual work.

**Completely Free Forever**
Create unlimited messages, assign them to unlimited products, and send unlimited emails. The free version has everything you need to get started.

---

== Want More? Upgrade to PRO ==

The free version is powerful on its own, but if you need advanced features, check out PRO:

**Free Version Includes:**
- **Unlimited SPE Messages** -- Create and manage as many Smart Product Email (SPE) messages as needed, giving you full flexibility without limits.
- **Product-Based Targeting** -- Display custom email content based on the exact product purchased, ensuring customers receive relevant and personalized messaging.
- **Flexible Content Placement** -- Choose where your custom message appears within the WooCommerce email layout, allowing it to fit naturally into existing email designs.
- **40+ Dynamic Placeholders** -- Insert dynamic order, product, and customer data—such as names, order numbers, and product details—without writing any code.
- **Duplicate Prevention** -- Automatically prevents the same message from appearing multiple times in a single email, ensuring clean and professional communication.
- **HPOS Compatibility** -- Fully compatible with WooCommerce High-Performance Order Storage (HPOS) for reliable performance and future-proof data handling.
- **Error Log & Debugging** -- Built-in error logging system captures PHP errors, AJAX failures, and email sending issues. View, filter, search, and export logs from the admin panel to quickly diagnose and resolve issues.

**PRO Version Adds:**
- **Additional Order Status Support** -- Customize customer emails for **On-Hold**, **Completed**, and **Refunded** order statuses to improve communication throughout the entire order lifecycle.
- **Visual Email Customizer** -- Design perfect customer emails with an intuitive visual interface. See exactly how your emails will look using real WooCommerce templates, assign multiple messages per product to different template locations (header, before order table, after order table, etc.), and control message hierarchy with simple up/down arrows. Includes live preview showing your messages positioned exactly where they'll appear in customer emails.
- **Send Test Emails** -- Send test emails to yourself or your team to verify formatting, links, and dynamic placeholders before going live.
- **Quick Templates Library** -- Access a growing library of pre-written Smart Product Email templates designed for common scenarios like refunds, promotions, upsells, and shipping instructions.
- **Priority Support** -- Get faster access to support and assistance directly from the plugin developer when you need help.
- **Regular Feature Updates** -- Receive ongoing enhancements, improvements, and new features as the plugin continues to evolve.

**More PRO Features Coming Soon:**
- **Advanced Conditional Logic** -- Display or hide messages based on complex rules such as cart value, customer type, product combinations, and more.
- **Bulk Message Management** -- Assign messages to multiple products simultaneously
- **A/B Testing Framework** -- Test different message variations to identify which content performs best with your customers.
- **Email Analytics and Reporting Dashboard** -- Gain insights into how your Smart Product Emails perform with metrics like engagement and effectiveness.
- **Customer Segmentation and Targeting** -- Show different email content based on customer behavior, purchase history, order value, or other attributes.
- **Automated Follow-up Sequences** -- Trigger additional emails based on customer actions
- **Multi-language Support** -- Automatically deliver emails in the customer's preferred language

---

Ready to turn your order emails into a revenue-generating communication platform? Install Smart Product Emails today and start delivering messages that matter.

== Installation & Setup ==

1. *Backup your site* before installing any new plugin.
2. *Install the plugin*:
   - Upload to `/wp-content/plugins/smart-product-emails/`
   - *Or* install directly from the WordPress Plugins screen.
3. *Activate the plugin* in *Plugins → Installed Plugins*. A new menu item appears: *Smart Product Emails*.
4. *Create a custom message*:
   - Go to *Smart Product Emails → Add New SPE Message*
   - Add a title + content, then *Publish*
   - *(Note: Shortcodes, blocks, and auto-generated content will not appear in emails)*
5. *Assign to a product*:
   - Edit a WooCommerce product
   - Open the *Smart Product Emails* tab in Product Data
   - Under the *Processing* section, click *Select Message* and search by name
   - Choose the *Content Location* (Email Header, before/after Order Details, Order Meta, Customer Details, or Email Footer)
   - *Update the product* 
6. *Test it*: Place a test order with that product. The custom content will appear in the WooCommerce email at your chosen location.

== Frequently Asked Questions ==

= Why does my content appear in the wrong location in the email? =
When there are multiple products in an Order which have the same SPE Message assigned, the first occurrence of a product with that SPE Message will get priority on which Content Location setting to use.

= What HTML tags are allowed? =
You can use any HTML allowed in the Classic Editor. These are:
-   *Headings:* `<h1>`, `<h2>`, `<h3>`, `<h4>`, `<h5>`, `<h6>`
-   *Paragraphs:* `<p>`
-   *Text Formatting:* `<strong>` (bold), `<em>` (italic), `<u>` (underline), `<del>` (strikethrough), `<code>` (code snippet), `<blockquote>` (blockquote)
-   *Lists:* `<ul>` (unordered list), `<ol>` (ordered list), `<li>` (list item)
-   *Links:* `<a>` (anchor tag for hyperlinks)
-   *Images:* `<img>` (for embedding images)
-   *Breaks:* `<br>` (line break)
-   *Horizontal Rule:* `<hr>`
-   *Divisions and Spans:* `<div>`, `<span>` (for structural and styling purposes)
-   *Tables:* `<table>`, `<thead>`, `<tbody>`, `<tr>`, `<th>`, `<td>`

= What Placeholders can I use in the SPE Messages content? =
Smart Product Emails supports 40+ dynamic placeholders. These are automatically replaced with real order data when emails are sent.

- **Site/Store:** `{site_title}`, `{site_address}`, `{site_url}`, `{store_email}`
- **Product Info:** `{product_id}`, `{product_name}`, `{product_sku}`, `{product_url}`, `{product_price}`, `{product_regular_price}`, `{product_sale_price}`, `{product_short_description}`, `{product_description}`, `{product_categories}`, `{product_tags}`
- **Order Info:** `{order_number}`, `{order_id}`, `{order_date}`, `{order_time}`, `{order_status}`, `{payment_method}`
- **Customer Info:** `{customer_first_name}`, `{customer_last_name}`, `{customer_name}`, `{customer_email}`, `{customer_phone}`
- **Billing Address:** `{billing_address}`, `{billing_city}`, `{billing_state}`, `{billing_postcode}`, `{billing_country}`
- **Shipping Address:** `{shipping_address}`, `{shipping_city}`, `{shipping_state}`, `{shipping_postcode}`, `{shipping_country}`
- **Order Totals:** `{order_subtotal}`, `{order_total}`, `{order_tax}`, `{order_shipping}`, `{order_discount}`

= Official Website =
Please see the official website for further reference:
https://smartproductemails.com

= How do I request a feature or report a bug? =
Have you found something wrong with the plugin? Thought of a helpful feature to add? Please see the Issues section on GitHub:
https://github.com/alexmustin/smart-product-emails/issues/

= Haven’t I seen this plugin before? =
Yes! This plugin was previously released under the name `Woo Custom Emails Per Product.` The original version was removed due to copyright concerns around its name. The new version, now called *Smart Product Emails*, has been rebuilt with improved functionality, better performance, and enhanced features, making it more powerful than ever!

= Something Else? =
If you are having any issues, please post in the WordPress Plugin Support Forum.

== Screenshots ==

1. Plugin menu: "Smart Product Emails"
2. 'Smart Product Emails' Settings page
3. 'Smart Product Email' example - using the Classic Editor
4. New Tab in the WooCommerce Product Data section: "Smart Product Emails"
5. Use the search fields to find your SPE Message, then assign it to the Order Status email. Click the Title of your Message to assign it, then Update/Publish the Product to save your settings.
6. Smart Email Message in the Customer email 
7. Error Log system to diagnose issues

== Changelog ==

### X.X.X - Initial Release
* Complete product-based email customization system
* 40+ dynamic placeholders for personalized content
* Flexible content placement within WooCommerce emails
* Duplicate message prevention
* HPOS compatibility and optimization
* Error Log & Debugging system with filtering, search, and CSV export
