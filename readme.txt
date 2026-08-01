=== Adventure Treks ===
Contributors: vastarpara, techeshta, alkesh7
Tags: treks, adventure, booking, itinerary, departure cities
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

🏔️ The complete trekking management plugin for WordPress — itineraries, departure cities, live seat availability, and frontend bookings in one place.

== Description ==

**Adventure Treks** is a powerful, enterprise-grade WordPress plugin built for adventure agencies, travel companies, and trekking organizations who need more than a basic booking form. It gives you a dedicated Trek post type, multi-city departures, flexible pricing, and real-time slot availability — all manageable right from your WordPress dashboard.

Whether you're running Himalayan treks, weekend hikes, or multi-day expeditions, Adventure Treks handles the operational complexity so you can focus on the adventure. 🧗

= 🚀 Key Features =

* 🏕️ **Dedicated Trek Post Type** — a clean "Treks" section in wp-admin with full support for title, content, featured image, excerpt, and revisions.
* 🗺️ **Rich Trek Details** — difficulty, duration, altitude, region, season, distance, fitness level, age limit, and group size, all managed from an intuitive meta box.
* 📍 **Multi-City Departures** — configure unlimited departure cities per trek, each with its own transport type, reporting time, Google Maps link, and booking deadline.
* 💰 **Flexible Pricing Rules** — set base and offer prices per city, with support for per-date pricing overrides via AJAX-powered admin controls.
* 📅 **Departure Dates & Live Availability** — schedule multiple departure dates per city and track total, booked, and available seats in real time.
* 🎟️ **Frontend Booking Widget** — a dynamic, AJAX-driven booking widget that checks live slot availability so customers never book a full trip.
* 📸 **Photo Gallery Support** — showcase each trek with a rich media gallery.
* ✅ **Highlights & Things to Carry** — give travelers a clear checklist of trip highlights and packing essentials.
* ❓ **FAQ & Policies** — built-in structured sections for frequently asked questions and cancellation/booking policies.
* 🔌 **REST API Ready** — first-class REST endpoints to fetch treks, trek details, departure cities, and dates/availability for headless or app integrations.
* 🎨 **Elementor Widgets** — drag-and-drop **Trek Details**, **Trek Booking**, and **Trek Archive Grid** (with pagination) widgets under a dedicated "Adventure Treks" category.
* ✏️ **Shortcodes** — drop `[trek_details]`, `[trek_itinerary]`, `[trek_booking]`, and `[trek_archive]` anywhere to render trek content without touching code.
* 📄 **Native Archive & Single Templates** — a ready-to-use, paginated Trek archive page and a full single-trek layout that work out of the box in any theme or editor (Gutenberg, classic, or Elementor).
* 🧭 **Day-wise Itinerary Builder** — plan and display detailed, day-by-day trip itineraries.
* 💳 **Cash & UPI Payments** — a configurable Payment step (Cash or UPI with QR code + one-tap copy of the UPI ID) shown before booking confirmation.
* 📋 **Booking Status Workflow** — bookings start as Pending and can be moved to Confirmed/Cancelled from the admin Bookings screen, with seat availability kept in sync automatically.
* 📧 **Branded Email Notifications** — auto-sends styled HTML emails (with your logo and brand colors) to the customer and admin on new bookings and status changes, using a configurable From Name and Notification Email.
* 🗂️ **Bookings Admin Screen** — Add/Edit bookings, filter by status, a dedicated Payment Status column, and a detailed "View" popup for every reservation.
* 🔍 **SEO Friendly** — dedicated SEO fields and full Gutenberg/REST support out of the box.

= 🎯 Who is it for? =

* Trekking & adventure tour operators
* Travel agencies managing multiple departure cities
* Outdoor activity organizers who need live seat tracking
* Anyone who wants a booking-ready trip catalogue without a bulky travel plugin

== Installation ==

1. Upload the `adventure-treks` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Head to the new **Adventure Treks** menu to start adding treks, departure cities, dates, and pricing.
4. Use the `[trek_details]`, `[trek_itinerary]`, `[trek_booking]`, and `[trek_archive]` shortcodes — or the Elementor widgets — to display treks anywhere on your site.

== Shortcodes ==

= [trek_details] =

Displays the full specs, highlights, itinerary, FAQ, policies, and gallery for a single trek.

`[trek_details id="123"]`

* `id` — (optional) Trek post ID. Defaults to the current post when placed on a trek's own page.

= [trek_booking] =

Displays the AJAX booking widget (city/date selection, live pricing, checkout) for a single trek.

`[trek_booking id="123"]`

* `id` — (optional) Trek post ID. Defaults to the current post when placed on a trek's own page.

= [trek_itinerary] =

Displays the day-wise itinerary timeline for a departure city.

`[trek_itinerary trek_id="123" city_id="4"]`

* `trek_id` — (optional) Trek post ID. Defaults to the current post.
* `city_id` — (optional) Departure city ID. Defaults to the trek's first active city.

= [trek_archive] =

Displays a paginated grid of treks (thumbnail, starting price, duration/difficulty/region, excerpt, and a "View Details" button).

`[trek_archive posts_per_page="6" columns="3" orderby="date" order="DESC" show_excerpt="yes" show_price="yes" pagination="yes"]`

* `posts_per_page` — (optional) Number of treks per page. Default `9`.
* `columns` — (optional) Grid columns: `2`, `3`, or `4`. Default `3`.
* `orderby` — (optional) `date`, `title`, `menu_order`, or `rand`. Default `date`.
* `order` — (optional) `ASC` or `DESC`. Default `DESC`.
* `show_excerpt` — (optional) `yes` or `no`. Default `yes`.
* `show_price` — (optional) `yes` or `no`. Default `yes`.
* `pagination` — (optional) `yes` or `no`. Default `yes`.

== Frequently Asked Questions ==

= Does this plugin support multiple departure cities per trek? =

Yes! Each trek can have unlimited departure cities, each with its own pricing, transport details, and schedule.

= Can I track live seat availability? =

Yes, every departure date tracks total, booked, and available seats, and the frontend booking widget reflects availability in real time via AJAX.

= Does it work with Elementor? =

Yes, dedicated **Trek Details** and **Trek Booking** widgets are available under the "Adventure Treks" category in Elementor.

= Is there a REST API? =

Yes, Adventure Treks exposes REST endpoints for treks, trek details, departure cities, and departure dates/availability — perfect for custom front-ends or apps.

== Changelog ==

= 1.0.0 =
* Initial premium release.
