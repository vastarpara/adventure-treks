# TrekPilot

[![CI](https://github.com/vastarpara/trekpilot/actions/workflows/ci.yml/badge.svg)](https://github.com/vastarpara/trekpilot/actions/workflows/ci.yml)
![License](https://img.shields.io/badge/license-GPL--2.0--or--later-blue)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4)
![WordPress](https://img.shields.io/badge/WordPress-6.0%20to%207.1-21759b)

Trekking and adventure trip management for WordPress: treks, departure cities and dates, day-wise itineraries, pricing, live seat availability and online bookings.

Works with the block editor, shortcodes and Elementor.

[Try it in WordPress Playground](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/vastarpara/trekpilot/main/blueprint.json)

## Features

- **Trek post type** with specifications, gallery, FAQ, policies, highlights and things to carry.
- **Departure cities** with their own price, offer price, transport options, pickup points, dates, seats and itinerary.
- **Pricing rules**: adult and child prices, group discounts, extra charges, optional add-ons and transport surcharges. The price is always recalculated on the server at checkout.
- **Booking widget** (AJAX) with live availability, inline validation, UPI or cash payment details and a confirmation receipt.
- **Bookings manager**: filters (trek, status, travel date range), search, edit, Trash and restore, Screen Options, and an A4 PDF export of the filtered list.
- **Import / Export** treks (with cities, itineraries, dates and pricing) as JSON.
- **Gutenberg blocks**: Trek Details, Trek Booking and Trek Archive. Elementor widgets and shortcodes are available too.
- **Settings**: currency format, payment method, email sender, logo, theme-friendly colors.
- **REST API** (read-only) under `trekpilot/v1`.

## Requirements

| | |
|---|---|
| WordPress | 6.0 or later (tested with 7.1) |
| PHP | 7.4 or later |
| Elementor | Optional (tested with 4.3) |

## Installation

1. Download the latest release zip, or clone this repository into `wp-content/plugins/trekpilot`.
2. Activate **TrekPilot** in **Plugins**.
3. Open **Treks > Settings** and configure currency, payment and email.
4. Add a trek under **Treks > Add New** and fill in its **Departure Cities**.

## Usage

### Blocks

Search for "Trek" in the block inserter: **Trek Details**, **Trek Booking**, **Trek Archive**.

### Shortcodes

| Shortcode | Purpose | Main attributes |
|---|---|---|
| `[trekpilot_list]` | Grid of treks | `posts_per_page`, `columns`, `orderby`, `order`, `include`, `show_excerpt`, `show_price`, `pagination` |
| `[trekpilot_details]` | Full trek details | `id` |
| `[trekpilot_booking]` | Booking widget | `id` |
| `[trekpilot_itinerary]` | Day-wise itinerary | `trek_id`, `city_id`, `date` |

## Development

```bash
composer install     # installs WordPress Coding Standards
composer lint        # run PHP_CodeSniffer
composer fix         # auto-fix what phpcbf can
```

The plugin has no build step: the block editor script is plain JavaScript.

Project layout:

```
admin/        Admin controllers and views
public/       Frontend controllers and views
includes/     Core classes (post type, database, REST, blocks, Elementor, PDF)
blocks/       block.json for each block
assets/       CSS and JavaScript (admin, public, blocks, vendor)
```

See [CONTRIBUTING.md](CONTRIBUTING.md) for the workflow and [CHANGELOG.md](CHANGELOG.md) for release notes.

## Third-party libraries

- [Flatpickr](https://flatpickr.js.org) (MIT), date picker.
- [tFPDF](https://github.com/Setasign/tFPDF), PDF generation.
- [DejaVu Sans Condensed](https://dejavu-fonts.github.io), PDF font.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
