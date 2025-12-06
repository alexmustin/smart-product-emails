# Smart Product Emails

> The complete email marketing suite for WooCommerce store owners who want to communicate smarter, not harder.

## Description

**Smart Product Emails** transforms your standard WooCommerce order emails into a sophisticated customer communication platform. Go beyond basic transactional emails and deliver personalized, data-driven messages that increase engagement, reduce support tickets, and drive repeat purchases.

Instead of sending generic order confirmations, automatically deliver the right message to the right customer at the right time — all within WooCommerce's native email flow.

### Core Features

**Dynamic Product-Based Content** — Create custom content blocks that automatically appear based on what customers purchase. From setup instructions to warranty information, your messages adapt to each order without manual intervention.

**Intelligent Placement Control** — Position your content exactly where it makes the most impact — before order details, after customer information, or in between. Your emails, your rules.

**Customer Segmentation** *(PRO)* — Target messages based on purchase history, order value, customer location, and more. Speak directly to first-time buyers differently than loyal customers.

**A/B Testing** *(PRO)* — Test different messages, calls-to-action, and content strategies. Data-driven insights show you what resonates with your customers.

**Performance Analytics** *(PRO)* — Track email opens, click-through rates, and conversion metrics. Know exactly which messages drive results and which need refinement.

**Email Preview & Testing** *(PRO)* — See exactly how your emails will look before they go out. Send test emails to verify formatting, links, and content placement.

### How It Works

1. **Create Smart Messages** – Build reusable content blocks with the visual editor or custom HTML
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

**Free Version Includes:**
- Unlimited custom email content blocks
- Product-based targeting
- Flexible content placement
- Dynamic placeholders
- Duplicate prevention
- HPOS compatibility

**PRO Version Adds:**
- On-Hold and Completed Order Statuses
- Email preview and testing
- Customer segmentation and targeting
- A/B testing framework
- Email analytics and reporting
- Advanced conditional logic
- Priority support
- Regular feature updates

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
   - *(Note: Shortcodes and auto-generated content will not appear in emails)*  
5. **Assign to a product**:  
   - Edit a WooCommerce product  
   - Open the **Smart Product Emails** tab in Product Data  
   - Choose the **Order Status** where the message should appear  
   - Click **Select Message**, search by name, and select it (green text = active)  
   - Choose the **Content Location** (before/after Order Details, Order Meta, or Customer Details)  
   - **Update the product**  
6. **Test it**: Place a test order with that product. The custom content will appear in the WooCommerce email at your chosen location.

## Frequently Asked Questions

### Why does my content appear in the wrong location in the email?
When there are multiple products in an Order which have the same Smart Product Email assigned, the first occurrence of a product with that Custom Email will get priority on which Content Location setting to use.

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
Smart Product Emails supports 30+ dynamic placeholders. These are automatically replaced with real order data when emails are sent.

- **Site/Store:** `{site_title}`, `{site_address}`, `{site_url}`, `{store_email}`
- **Order Info:** `{order_number}`, `{order_id}`, `{order_date}`, `{order_time}`, `{order_status}`, `{payment_method}`
- **Customer Info:** `{customer_first_name}`, `{customer_last_name}`, `{customer_name}`, `{customer_email}`, `{customer_phone}`
- **Billing Address:** `{billing_address}`, `{billing_city}`, `{billing_state}`, `{billing_postcode}`, `{billing_country}`
- **Shipping Address:** `{shipping_address}`, `{shipping_city}`, `{shipping_state}`, `{shipping_postcode}`, `{shipping_country}`
- **Order Totals - Auto-formatted with currency:** `{order_subtotal}`, `{order_total}`, `{order_tax}`, `{order_shipping}`, `{order_discount}`

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

### X.X.X - (XXX X, 202X)
* First release!
