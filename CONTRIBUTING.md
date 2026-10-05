# Contributing to Adventure Treks

Thanks for helping improve Adventure Treks. This guide explains how to report problems, propose
changes and get a pull request merged.

## Reporting bugs and requesting features

Use the issue templates: **Bug report** or **Feature request**. For a bug, include your WordPress,
PHP, theme and Elementor versions, the steps to reproduce it, and any error from the PHP log or the
browser console. **Security problems must not be reported in public issues**; email the maintainer
instead (see the author link in the plugin header).

## Development setup

1. Clone the repository into `wp-content/plugins/adventure-treks` of a local WordPress site.
2. Run `composer install`.
3. Turn on `WP_DEBUG` and `WP_DEBUG_LOG` in `wp-config.php`.

There is no JavaScript build step. Edit the files in `assets/` directly.

## Branches and commits

- Branch from `main`: `feature/short-name`, `fix/short-name` or `docs/short-name`.
- Keep commits small and focused. Write the subject in the imperative, for example
  `Fix seat count when a booking is restored`.
- Do not bump the plugin version in a feature branch. Maintainers do that when releasing.

## Coding standards

- PHP follows the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/).
  Run `composer lint` and fix every error and warning before opening a pull request. `composer fix`
  repairs most formatting problems.
- Every class, method and function has a DocBlock describing what it does, its `@param` and
  `@return`. Add short comments where the reason for the code is not obvious.
- Prefix global names with `adventure_treks_` (variables in templates included at global scope
  too). Class names live in the `AdventureTreks` namespace.
- All text is translatable and uses the `adventure-treks` text domain.

## Security checklist

- Check a nonce **and** a capability in every AJAX or `admin-post` handler. Public booking
  endpoints verify the nonce only, so they must validate every input on the server.
- Never trust prices, totals or IDs sent by the browser. Recalculate or look them up.
- Sanitize input (`sanitize_text_field`, `absint`, ...), escape output (`esc_html`, `esc_attr`,
  `esc_url`, `wp_kses_post`) and use `$wpdb->prepare()` with placeholders.
- In JavaScript, build markup with `textContent` or escape values before using `innerHTML`.

## Testing a change

- Run `composer lint` and `composer lint:php`.
- Check the feature in the block editor, and with Elementor if the change touches the widgets.
- For booking changes, make a booking and confirm seats, total and emails are correct.
- Run the [Plugin Check](https://wordpress.org/plugins/plugin-check/) plugin on the plugin.

## Pull requests

1. Fill in the pull request template.
2. Describe what changed and why, and link the issue.
3. Add a line under **Unreleased** in [CHANGELOG.md](CHANGELOG.md).
4. Make sure CI passes. A maintainer will review and may ask for changes.

## License

By contributing you agree that your work is released under the GPL-2.0-or-later license.
