# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Added
- Import / Export treks as JSON from **Treks > Settings**.
- Gutenberg blocks: Trek Details, Trek Booking and Trek Archive.
- Bookings: travel date range filter (Flatpickr), Reset button, clean filter URLs.
- Bookings: Trash with Restore, Delete Permanently and Empty Trash; seats stay in sync.
- Bookings: A4 PDF export of the filtered list.
- Bookings: Screen Options for columns and items per page.
- WordPress.org `readme.txt`, GitHub Actions CI, issue and pull request templates,
  WordPress Playground Blueprint, `README.md`, `CONTRIBUTING.md`, `LICENSE`.

### Changed
- Checkout uses inline field validation instead of browser alerts.
- Phone numbers from any country (10 to 15 digits) are accepted.
- Redesigned, more compact booking confirmation receipt.
- Code now follows the WordPress Coding Standards (PHPCS clean) and passes the
  Plugin Check static checks.

### Security
- The booking total and transport surcharge are now calculated on the server. Previously the
  browser-submitted amounts were stored, so a customer could book at any price.
- Seat reservation is a single conditional `UPDATE`, so simultaneous bookings can no longer
  oversell the last seats.
- Booking submission validates that the city belongs to the trek, the date to the city and is
  still open, and that traveller counts are valid (no negative counts).
- Read-only admin AJAX endpoints now also require the `edit_posts` capability.
- Checkout output built in JavaScript is HTML-escaped.
- Bulk booking actions verify a nonce.

### Fixed
- Adults / Children counter styling with themes and Elementor.
- Admin modals are no longer clipped in the block editor.
- Itinerary Builder sidebar header wrapping.
- PDF export shows the rupee sign and other currency symbols.

## [1.0.9]

- Currency settings, Fitness Level field, field validation and Itinerary Builder improvements.
- Color and theme handling, Share, itinerary and policy fixes.
- Single trek page mobile layout and gallery improvements.
- Booking widget, pricing, age validation and sidebar fixes.

## [1.0.0]

- Initial release.
