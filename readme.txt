=== Easy Currency ===
Contributors: themewant
Tags: currency switcher, woocommerce currency, multi-currency, currency converter, woocommerce
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Let shoppers view and switch WooCommerce product prices in multiple currencies, with automatic rates and checkout in the selected currency.

== Description ==

Easy Currency Switcher is a **free WooCommerce multi-currency plugin**. Create multiple currencies, set custom rates or auto rates, format symbols/positions, and let shoppers view and pay in their preferred currency.

<a href="https://easy-currency.themewant.com/" target="_blank">Live Demo</a> | <a href="https://easy-currency.themewant.com/product/family-suite/" target="_blank">Single Demo</a> | <a href="https://documentation.themewant.com/docs/easy-currency-switcher/installation/" target="_blank">Documentation</a> | <a href="https://codecanyon.net/item/easy-currency-switcher-woocommerce-multicurrency-plugin/59845282?s_rank=1" target="_blank">Pro Version</a> | <a href="https://app.instawp.io/launch?d=v2&t=easy-currency-switcher" target="_blank">Try Demo Admin</a>

Easy Currency comes with one of the most powerful currency switcher customizers available on the market, allowing you to design modern and unlimited switchers for your site.

We provide 2 ready-made templates for shortcode switchers and 3 different ready-made templates for sticky sidebar switchers, so you can quickly build a beautiful currency switcher without hassle.

You can also display estimated product prices on the shop, single product, cart, and checkout pages. The plugin automatically updates exchange rates and lets customers pay directly in their selected currency.

Easy Currency is available both as a shortcode and as a sticky sidebar switcher.

== Why Choose Easy Currency? ==

Easy Currency makes your WooCommerce store ready for global customers. With powerful customization, ready-made templates, and automatic exchange rates, you can create stylish currency switchers in minutes. It integrates seamlessly across shop, product, cart, and checkout pages, letting customers view prices and pay in their preferred currency.
	
== 🌟 Key Features of Easy Currency ==

**Currency Switcher on Product Page**

You can display the WooCommerce multi-currency switcher dropdown on your product page at specific positions — above or below the Add to Cart button, above the short description, or above or below the product meta. It also appears in other product popups, including Quickview, filters, and more.

**Switcher Dropdown**

Customize the currency switcher dropdown with flags, currency symbols, country codes, and currency names. Easily display it anywhere on your site using the **[easy_currency_switcher id=x]** shortcode.

You can also format product prices by setting the  Decimal Separator, and Thousand Separator to match your preferred style.

**Switcher Sticky**

You can customize our sticky templates and display them anywhere on your screen, including the left or right corners. We provide three ready-made sticky templates for you to use. You can choose any page to display the sticky switchers according to your preference.

**Shortcode generator**

A shortcode generator that allows you to easily create, edit, and delete shortcodes. You can also edit shortcodes to customize our pre-designed templates.

**Design** 

Fully customize the design with options for background, color, font, border, padding, and margin.

**Shortcode In Menus**

You can add Easy Currency shortcodes directly into WordPress navigation menus, allowing you to display currency switchers dynamically wherever needed. This feature also supports adding full HTML sections to menus, giving you complete flexibility to customize your navigation with advanced content and functionality.

**Currency Rates**

Supports more than 7 currency aggregators for automatic exchange rate updates, with the option for the admin to manually adjust rates when necessary.

**Checkout**

Enable this option to allow customers to pay in their chosen currency.

== Easy Currency Premium Features ==

* **Unlimited Currencies** – Add as many currencies as you want.
* **Auto Update Exchange Rate** – Keep exchange rates updated automatically.
* **Update Interval** – Choose how often rates update (e.g., every 5 minutes).
* **Geo IP Rule for Each Product** – Set custom prices for individual products based on the user’s country. (Fixed Price products are excluded.)  
* **Auto Select Currency by Geo IP** – Automatically convert currency based on the visitor’s country. WooCommerce and Auto Select Currency must be enabled.
* **Currency Checkout & Payment Rules** – Enable “Currency Checkout” to display currency-specific payment methods. To allow currency changes based on the selected payment method, also enable “Payment with Selected Currency” in the Options tab.
* **Payment Gateway Rules** – Change currency based on selected payment method.
* **Currency Change Mode** –
  * Instant Change: Updates immediately when payment method is selected.
  * On Place Order: Updates only after the order is placed.
* **Shipping & Billing Currency Settings** – Determine how currency updates based on customer address.
* **Currency on Billing** – Change currency by billing country or shipping country on checkout.

== Installation ==

1. Go to the Plugins Menu in WordPress
2. Search for "Easy Currency"
3. Click "Install Now" and then "Activate"

== Screenshots ==

== External services ==

This plugin connects to multiple external services to fetch live currency exchange rates. 
These services are required to provide accurate and up-to-date currency information in the plugin. 

No user personal data is sent. The plugin only requests currency symbols or API key data when fetching exchange rates.

---

**1. Yahoo Finance API**  
This service is used to fetch live forex rates.  
No user personal data is sent. The plugin only requests currency symbols from the Yahoo Finance API whenever rates need to be updated.  

Service provider: Yahoo Finance  
- Terms of Use: https://legal.yahoo.com/us/en/yahoo/terms/otos/index.html  
- Privacy Policy: https://policies.yahoo.com/us/en/yahoo/privacy/index.htm  

---

**2. CryptoCompare API**  
This service is used to fetch cryptocurrency exchange rates.  
No user personal data is sent. The plugin only requests the selected cryptocurrency symbols when rates are fetched.  

Service provider: CryptoCompare  
- Terms of Use: https://www.cryptocompare.com/terms-conditions/ 
- Privacy Policy:  https://www.cryptocompare.com/privacy-policy/

---

**3. European Central Bank (ECB)**  
This service provides Euro-based currency exchange rates.  
No user personal data is sent. The plugin only requests currency rates from the ECB API whenever rates need to be updated.  

Service provider: European Central Bank  
- Terms of Use: https://www.ecb.europa.eu/services/data-protection/privacy-statements/html/ecb.terms_identity_portal.en.html
- Privacy Policy: https://www.ecb.europa.eu/services/data-protection/privacy-statements/html/index.en.html

---

**4. apilayer / Exchangerates API**  
This service is used to fetch live currency exchange rates via API key.  
No user personal data is sent. The plugin only requests the selected currency symbols along with the API key.  

Service provider: apilayer  
- Privacy Policy link: https://www.ideracorp.com/legal/APILayer
- Terms of Use link: https://www.ideracorp.com/Legal/Terms-of-Use 

---

**5. PrivatBank API**  
This service is used to fetch UAH-based currency exchange rates.  
No user personal data is sent. The plugin only requests currency symbols when rates need to be updated.  

Service provider: PrivatBank  
- Terms of Use: https://static.privatbank.ua/files/0000003515410882.pdf
- Privacy Policy: https://privatbank.ua/en/personal-information 

---

**6. Hungarian National Bank (MNB) API**  
This service is used to fetch HUF-based currency exchange rates via SOAP.  
No user personal data is sent. The plugin only requests currency rates from the MNB API whenever currency data needs to be updated.  

Service provider: Hungarian National Bank (MNB)  
- Terms of Use: https://www.mnb.hu/en/the-central-bank/about-the-mnb/terms-of-use
- Privacy Policy: https://www.mnb.hu/en/privacy-notice

---

**7. Open Exchange Rates API**  
This service is used to fetch live currency exchange rates via API key.  
No user personal data is sent. The plugin only requests the selected currency symbols along with the API key.  

Service provider: Open Exchange Rates  
- Terms of Use: https://openexchangerates.org/terms
- Privacy Policy: https://openexchangerates.org/privacy

= 1.0.7 =
* Fixed: Plugin Review fix

= 1.0.6 =
* Fixed: Code updated

= 1.0.5 =
* Added: Settings Color opiton added

= 1.0.4 =
* Added: Welcome Currency add
* Added: Fixed Price rule each product
* Fixed: Shortcode Style saved issue fix
* Fixed: Currency table Update

= 1.0.3 =
* Fixed: Base Currency Converter issue
* Fixed: Default Currency Select issue

= 1.0.2 =
* Added: Custom currency symbol support.
* Fixed: Resolved issue with currency symbol positioning.

= 1.0.1 =
* Added: Sticky Side currency added.
* Added: Sticky side 3 layout style added.
* Added: Dropdown view 2 layout style added.
* Added: Dropdown view shortcode repeater generator added.
* Added: Dropdown single page shortcode system added.
* Added: Currency customization feature added.
* Added: Currency Shortcode Menu support
* Fixed: Minor bugs fix.
* Fixed currency symbol change issue fix
* Fixed order page price details issue fix
* Fixed: Css Issue fix.

= 1.0.0 =	
* Initial release