# TC Events

A complete WordPress event management plugin with RSVP, REST API, notifications, WP-CLI support, and more.

## Features

- **Custom Post Type** – `tc_event` with `event_type` taxonomy
- **Meta Fields** – Start/end date, location, capacity, card hover color
- **RSVP System** – Custom database table with capacity checks and AJAX toggle
- **REST API** – Full CRUD endpoints at `/wp-json/tc-events/v1/`
- **Email Notifications** – Admin notified on publish; attendees notified on updates
- **Shortcode** – `[tc_events]` with filtering by type, date range, search
- **Template System** – WooCommerce-style overrides via `your-theme/tc-events/`
- **Transient Caching** – Automatic cache invalidation on data changes
- **WP-CLI** – `wp tc-events generate --count=10` and `wp tc-events stats`
- **i18n Ready** – Full translation support with `.pot` file
- **Unit Tests** – PHPUnit tests for CPT, RSVP, REST API, and shortcode

## Requirements

- WordPress 6.0+
- PHP 7.4+

## Installation

1. Upload the plugin via **WP Admin → Plugins → Add Plugin → Upload**, or copy the `tc-events` directory to `wp-content/plugins/`
2. Activate via WP Admin → Plugins
3. Navigate to **Events** in the admin menu
4. Event archive is available at `/events/`

## Shortcode

```
[tc_events type="conference" date_from="2026-01-01" limit="6" columns="3" order="ASC"]
```

| Attribute   | Default      | Description                          |
|-------------|--------------|--------------------------------------|
| `type`      | (all)        | Event type slug(s), comma-separated  |
| `date_from` | (none)       | Filter events from this date (Y-m-d) |
| `date_to`   | (none)       | Filter events until this date        |
| `search`    | (none)       | Keyword search                       |
| `limit`     | 12           | Number of events                     |
| `columns`   | 3            | Grid columns (1–4)                   |
| `orderby`   | `event_date` | Sort by: `date`, `title`, `event_date` |
| `order`     | `ASC`        | `ASC` or `DESC`                      |

## REST API

| Method | Endpoint                          | Auth Required | Description       |
|--------|-----------------------------------|---------------|-------------------|
| GET    | `/tc-events/v1/events`            | No            | List events       |
| GET    | `/tc-events/v1/events/{id}`       | No            | Single event      |
| POST   | `/tc-events/v1/events/{id}/rsvp`  | No            | Register RSVP     |
| GET    | `/tc-events/v1/events/{id}/attendees` | Editor+   | List attendees    |

### Query Parameters (GET /events)

- `page` – Page number (default: 1)
- `per_page` – Results per page (default: 10)
- `type` – Filter by event type slug
- `search` – Search keyword

## WP-CLI

```bash
# Generate sample events
wp tc-events generate --count=10

# View statistics
wp tc-events stats
```

## Template Overrides

Copy any template from `tc-events/templates/` to `your-theme/tc-events/` to customize:

- `single-event.php` – Single event page
- `archive-event.php` – Event archive/listing
- `shortcode-events.php` – Shortcode output

## Running Tests

```bash
# Set up WordPress test suite
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest

# Run tests
phpunit
```

## Uninstall

Deactivating the plugin preserves data. Deleting it via WP Admin removes:
- All events and their meta
- The `tc_event_rsvps` database table
- All event type taxonomy terms
- Plugin options and transients
