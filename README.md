# Smart Product Emails

> The complete email marketing suite for WooCommerce store owners who want to communicate smarter, not harder.

## Description

**Smart Product Emails** transforms your standard WooCommerce order emails into a sophisticated customer communication platform. Go beyond basic transactional emails and deliver personalized, data-driven messages that increase engagement, reduce support tickets, and drive repeat purchases.

Instead of sending generic order confirmations, automatically deliver the right message to the right customer at the right time — all within WooCommerce's native email flow.

### Core Features

**Dynamic Product-Based Content** — Create custom content messages that automatically appear based on what customers purchase. From setup instructions to warranty information, your messages adapt to each order without manual intervention.

**Intelligent Placement Control** — Position your content exactly where it makes the most impact — the top of the email, before/after order details, after customer information, or at the end of the email. Your emails, your rules.

**Email Preview & Testing** *(PRO)*
See exactly how your emails will look before they go out. Send test emails to verify formatting, links, and content placement.

**Customer Segmentation** *(PRO)*
Target messages based on purchase history, order value, customer location, and more. Speak directly to first-time buyers differently than loyal customers.

**A/B Testing** *(PRO)*
Test different messages, calls-to-action, and content strategies. Data-driven insights show you what resonates with your customers.

**Performance Analytics** *(PRO)*
Track email opens, click-through rates, and conversion metrics. Know exactly which messages drive results and which need refinement.

### How It Works

1. **Create Smart Messages** – Build reusable content messages with the visual editor or custom HTML
2. **Set Targeting Rules** – Choose which products trigger each message
3. **Choose Placement** – Position content strategically within the email template
4. **Automate Everything** – Once configured, your email system runs automatically with zero ongoing effort

### Real-World Use Cases

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

### Why WooCommerce Store Owners Choose Smart Product Emails

**Professional Communication Without the Complexity** — No need for expensive third-party email platforms. Everything runs inside WooCommerce's trusted email system, maintaining brand consistency and customer trust.

**More Revenue, Less Effort** — Automate personalized communication that used to require manual work. Your email marketing runs 24/7 while you focus on growing your business.

**Data-Driven Decisions** — Stop guessing what works. Analytics and A/B testing show you exactly which messages drive sales, reduce returns, and improve customer satisfaction.

**Seamless Integration** — Works perfectly with WooCommerce's native emails — no theme conflicts, no complicated setup, no learning curve. If you can edit a WordPress page, you can master Smart Product Emails.

---

### Free vs PRO

**✅ Free Version Includes:**
- **🧾 Unlimited SPE Messages** -- Create and manage as many Smart Product Email (SPE) messages as needed, giving you full flexibility without limits.
- **🎯 Product-Based Targeting** -- Display custom email content based on the exact product purchased, ensuring customers receive relevant and personalized messaging.
- **🧩 Flexible Content Placement** -- Choose where your custom message appears within the WooCommerce email layout, allowing it to fit naturally into existing email designs.
- **🔄 40+ Dynamic Placeholders** -- Insert dynamic order, product, and customer data—such as names, order numbers, and product details—without writing any code.
- **🛡️ Duplicate Prevention** -- Automatically prevents the same message from appearing multiple times in a single email, ensuring clean and professional communication.
- **🔍 Error Log & Debugging** -- Built-in error logging system captures PHP errors, AJAX failures, and email sending issues. View, filter, search, and export logs from the admin panel to quickly diagnose and resolve issues.
- **⚡ HPOS Compatibility** -- Fully compatible with WooCommerce High-Performance Order Storage (HPOS) for reliable performance and future-proof data handling.

**⭐ PRO Version Adds:**
- **📦 Additional Order Status Support** -- Customize customer emails for **On-Hold**, **Completed**, and **Refunded** order statuses to improve communication throughout the entire order lifecycle.
- **✨ Visual Email Customizer** -- Design perfect customer emails with an intuitive visual interface. See exactly how your emails will look using real WooCommerce templates, assign multiple messages per product to different template locations (header, before order table, after order table, etc.), and control message hierarchy with simple up/down arrows. Includes live preview showing your messages positioned exactly where they'll appear in customer emails.
- **🧪 Send Test Emails** -- Send test emails to yourself or your team to verify formatting, links, and dynamic placeholders before going live.
- **📚 Quick Templates Library** -- Access a growing library of pre-written Smart Product Email templates designed for common scenarios like refunds, promotions, upsells, and shipping instructions.
- **🛟 Priority Support** -- Get faster access to support and assistance directly from the plugin developer when you need help.
- **🔄 Regular Feature Updates** -- Receive ongoing enhancements, improvements, and new features as the plugin continues to evolve.

**🚀 More PRO Features Coming Soon:**
- **⚙️ Advanced Conditional Logic** -- Display or hide messages based on complex rules such as cart value, customer type, product combinations, and more.
- **🗂️ Bulk Message Management** -- Assign messages to multiple products simultaneously
- **🧪 A/B Testing Framework** -- Test different message variations to identify which content performs best with your customers.
- **📊 Email Analytics and Reporting Dashboard** -- Gain insights into how your Smart Product Emails perform with metrics like engagement and effectiveness.
- **🎯 Customer Segmentation and Targeting** -- Show different email content based on customer behavior, purchase history, order value, or other attributes.
- **🔁 Automated Follow-up Sequences** -- Trigger additional emails based on customer actions
- **🌍 Multi-language Support** -- Automatically deliver emails in the customer's preferred language

---

Ready to turn your order emails into a revenue-generating communication platform? Install Smart Product Emails today and start delivering messages that matter.

## Installation & Setup

1. **Backup your site** before installing any new plugin.
2. **Install the plugin**:
   - Upload to `/wp-content/plugins/smart-product-emails/`
   - *Or* install directly from the WordPress Plugins screen.
3. **Activate the plugin** in **Plugins → Installed Plugins**. A new menu item appears: **Smart Product Emails**.
4. **Create a custom message**:
   - Go to **Smart Product Emails → Add New SPE Message**
   - Add a title + content, then **Publish**
   - *(Note: Shortcodes, blocks, and auto-generated content will not appear in emails)*
5. **Assign to a product**:
   - Edit a WooCommerce product
   - Open the **Smart Product Emails** tab in Product Data
   - Choose the **Order Status** where the message should appear
   - Click **Select Message**, search by name, and select it (green text = active)
   - Choose the *Content Location* (Email Header, before/after Order Details, Order Meta, Customer Details, or Email Footer)
   - **Update the product**
6. **Test it**: Place a test order with that product. The custom content will appear in the WooCommerce email at your chosen location.

## Frequently Asked Questions

### Why does my content appear in the wrong location in the email?
When there are multiple products in an Order which have the same SPE Message assigned, the first occurrence of a product with that SPE Message will get priority on which Content Location setting to use.

### What HTML tags are allowed?
You can use any HTML allowed in the Classic Editor. These are:
-   **Headings:** `<h1>`, `<h2>`, `<h3>`, `<h4>`, `<h5>`, `<h6>`
-   **Paragraphs:** `<p>`
-   **Text Formatting:** `<strong>` (bold), `<em>` (italic), `<u>` (underline), `<del>` (strikethrough), `<code>` (code snippet), `<blockquote>` (blockquote)
-   **Lists:** `<ul>` (unordered list), `<ol>` (ordered list), `<li>` (list item)
-   **Links:** `<a>` (anchor tag for hyperlinks)
-   **Images:** `<img>` (for embedding images)
-   **Breaks:** `<br>` (line break)
-   **Horizontal Rule:** `<hr>`
-   **Divisions and Spans:** `<div>`, `<span>` (for structural and styling purposes)
-   **Tables:** `<table>`, `<thead>`, `<tbody>`, `<tr>`, `<th>`, `<td>`

### What Placeholders can I use in the SPE Messages content?
Smart Product Emails supports 40+ dynamic placeholders. These are automatically replaced with real order data when emails are sent.

- **Site/Store:** `{site_title}`, `{site_address}`, `{site_url}`, `{store_email}`
- **Product Info:** `{product_id}`, `{product_name}`, `{product_sku}`, `{product_url}`, `{product_price}`, `{product_regular_price}`, `{product_sale_price}`, `{product_short_description}`, `{product_description}`, `{product_categories}`, `{product_tags}`
- **Order Info:** `{order_number}`, `{order_id}`, `{order_date}`, `{order_time}`, `{order_status}`, `{payment_method}`
- **Customer Info:** `{customer_first_name}`, `{customer_last_name}`, `{customer_name}`, `{customer_email}`, `{customer_phone}`
- **Billing Address:** `{billing_address}`, `{billing_city}`, `{billing_state}`, `{billing_postcode}`, `{billing_country}`
- **Shipping Address:** `{shipping_address}`, `{shipping_city}`, `{shipping_state}`, `{shipping_postcode}`, `{shipping_country}`
- **Order Totals:** `{order_subtotal}`, `{order_total}`, `{order_tax}`, `{order_shipping}`, `{order_discount}`

### Official Website
Please see the official website for further reference:
https://smartproductemails.com

### How do I request a feature or report a bug?
Have you found something wrong with the plugin? Thought of a helpful feature to add? Please see the Issues section on GitHub:
[https://github.com/alexmustin/smart-product-emails/issues/](https://github.com/alexmustin/smart-product-emails/issues/)

### Haven’t I seen this plugin before?
Yes! This plugin was previously released under the name *“Woo Custom Emails Per Product.”* The original version was removed due to copyright concerns around its name. The new version, now called **Smart Product Emails**, has been rebuilt with improved functionality, better performance, and enhanced features, making it more powerful than ever!

### Something Else?
If you are having any issues, please post in the WordPress Plugin Support Forum.

## Changelog

### X.X.X - Initial Release
* Complete product-based email customization system
* 40+ dynamic placeholders for personalized content
* Flexible content placement within WooCommerce emails
* Duplicate message prevention
* HPOS compatibility and optimization
* Error Log & Debugging system with filtering, search, and CSV export
