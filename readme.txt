=== TrekPilot – Trek Booking & Management ===
Contributors: vastarpara, techeshta, alkesh7, bhaveshdesai
Tags: trek, travel, booking, itinerary, tour
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Trekking and adventure trip management: treks, departure cities and dates, itineraries, pricing, live seat availability and online bookings.

== Description ==

**TrekPilot** turns your WordPress site into a complete trekking and adventure booking system. Create treks with full details, schedule departures from multiple cities, build day-wise itineraries, set prices and let visitors book their seats online. Manage every reservation from the admin area.

Everything works with the default WordPress block editor, with shortcodes, and with Elementor.

= How it works =

1. Add a trek and fill in its specifications, gallery, FAQ and policies.
2. Add one or more departure cities, each with its own price, pickup points, dates, seats and itinerary.
3. Show the trek with the Trek Details block, the Trek Booking block, or the matching shortcodes.
4. Visitors choose a city and date, pick the number of travellers and book.
5. You receive the booking, and manage its status and payment in **Treks > Bookings**.

== Key Features ==

* **Dedicated Trek post type** - a clean "Treks" section in wp-admin with its own archive and single trek pages.
* **Rich trek details** - difficulty, duration, altitude, region, best season, distance, fitness level, age limit and group size, plus highlights, exclusions, things to carry, FAQ, policies and a photo gallery.
* **Multiple departure cities** - unlimited cities per trek, each with its own price, offer price, transport options, reporting time and map link.
* **Departure dates and live availability** - schedule many dates per city with seat counts that update as bookings come in.
* **Pricing rules** - adult and child prices, group discounts, extra charges, optional add-ons and transport options.
* **Pickup points** - add pickup locations with time, map link and instructions per city.
* **Day-wise itinerary builder** - build each day with timed activities, icons and images, with drag-and-drop ordering.
* **AJAX booking widget** - checks availability and calculates the price live, with inline validation and a booking receipt. Phone numbers from any country are accepted.
* **Bookings manager** - filter by trek, status and travel date range (date range picker), search customers, edit bookings, and use Screen Options to choose columns and items per page.
* **Trash for bookings** - move bookings to the Trash and restore them, with seat availability kept in sync.
* **PDF export** - download the filtered bookings list as an A4 PDF with customer, phone, city, pickup point, seats, payment status and balance.
* **Import / Export treks** - move treks, with their cities, itineraries, dates and pricing, between sites using a JSON file.
* **Gutenberg blocks** - Trek Details, Trek Booking and Trek Archive blocks with block settings, plus Elementor widgets for the same content.
* **Currency settings** - symbol, position, thousand and decimal separators and number of decimals.
* **Cash and UPI payments** - show a UPI ID and QR code to customers at checkout, or take payment in cash.
* **Branded emails** - booking confirmation and status update emails with your logo and sender name.
* **Theme-friendly colors** - set primary and secondary colors, or leave them empty to follow your theme.
* **SEO ready** - optional Schema.org structured data for treks.
* **REST API** - read-only endpoints for treks, cities and dates.
* **Translation ready** - all text uses the `trekpilot` text domain.

== Installation ==

1. Upload the `trekpilot` folder to the `/wp-content/plugins/` directory, or install the plugin from **Plugins > Add New**.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Go to **Treks > Settings** and set your currency, payment method, notification email and colors.
4. Go to **Treks > Add New** to create your first trek. Use the **Departure Cities** box to add cities, dates, pricing, pickup points and the itinerary.
5. Show your treks on any page using the TrekPilot blocks or the shortcodes below. If you use pretty permalinks and the Trek pages return a 404, visit **Settings > Permalinks** and click **Save Changes** once.

== Shortcodes ==

= [trekpilot_list] =
Shows a grid of treks.

* `posts_per_page` - number of treks per page (default `9`).
* `columns` - number of columns, 1 to 6 (default `3`).
* `orderby` - `date`, `title`, `menu_order` or `rand` (default `date`).
* `order` - `ASC` or `DESC` (default `DESC`).
* `include` - comma separated trek IDs to show, in that order (disables pagination).
* `show_excerpt` - `yes` or `no` (default `yes`).
* `show_price` - `yes` or `no` (default `yes`).
* `pagination` - `yes` or `no` (default `yes`).

Example: `[trekpilot_list columns="3" posts_per_page="6" orderby="title" order="ASC"]`

= [trekpilot_details] =
Shows the full details of a trek: specifications, itinerary, gallery, FAQ and policies.

* `id` - the trek ID. Defaults to the current trek page.

Example: `[trekpilot_details id="12"]`

= [trekpilot_booking] =
Shows the booking widget for a trek.

* `id` - the trek ID. Defaults to the current trek page.

Example: `[trekpilot_booking id="12"]`

= [trekpilot_itinerary] =
Shows the day-wise itinerary of a trek city.

* `trek_id` - the trek ID. Defaults to the current trek page.
* `city_id` - the departure city ID.
* `date` - optional departure date.

== Frequently Asked Questions ==

= Does it work with the block editor? =

Yes. Search for "Trek" in the block inserter to find the **Trek Details**, **Trek Booking** and **Trek Archive** blocks. Shortcodes and Elementor widgets are also available.

= Is Elementor required? =

No. Elementor is optional. If it is active, the TrekPilot category with Trek Details, Trek Booking and Trek Archive widgets appears in the Elementor panel.

= How do customers pay? =

The booking is created as pending. At checkout the customer sees the payment method you chose in **Treks > Settings > Payment**: cash, or UPI with your UPI ID and QR code. You mark a booking as paid in **Treks > Bookings**.

= Where do booking emails go? =

Notification emails go to the address in **Treks > Settings > Email**, and customers receive a confirmation and a status update email. Emails are sent with `wp_mail()`, so they work with any SMTP plugin.

= Can I change the currency? =

Yes. Choose the currency, its position, separators and decimals in **Treks > Settings > Currency**. The same format is used on the website, in emails and in the admin.

= Can I move my treks to another site? =

Yes. Use **Treks > Settings > Import / Export** to download your treks as a JSON file and import it on another site. Images are downloaded again on import. Bookings are not exported.

= Can I export bookings? =

Yes. In **Treks > Bookings**, apply the filters you need and click **Download PDF**.

= What happens when I delete a booking? =

It moves to the Trash and its seats are released. You can restore it, delete it permanently, or empty the Trash.

= Does the plugin delete my data when uninstalled? =

No. By default your treks, bookings and database tables are kept when the plugin is deleted.

= Is there a REST API? =

Yes. Read-only endpoints are available under `/wp-json/trekpilot/v1/` for treks, a trek's cities, and a city's dates.

= Does it support other languages? =

Yes. The plugin is translation ready and uses the `trekpilot` text domain. Names in non-Latin scripts are supported everywhere except the PDF export, which uses a font without Gujarati and Hindi characters.

== Screenshots ==

1. Single trek page with gallery, specifications, itinerary and booking widget.
2. Booking widget: departure city, date, travellers, transport and live price.
3. Checkout form with inline validation.
4. Booking confirmation receipt.
5. Trek archive grid.
6. Trek editor: specifications and Departure Cities manager.
7. Itinerary Builder with timed activities.
8. Bookings screen with date range filter, Trash and PDF download.
9. Settings: currency, payment, email and colors.
10. Trek Details, Trek Booking and Trek Archive blocks in the block editor.

== Changelog ==

= 1.0 =
* Initial release.
