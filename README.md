# Contao Podcast Bundle

A full-featured podcast extension for [Contao CMS](https://contao.org) 5.3+. Manage podcast channels and episodes with built-in RSS feed generation, Schema.org structured data, and audio player support.

## Features

- **Podcast Channels** — Organize episodes into channels with dedicated settings for feeds, images, languages, and access protection.
- **Episodes** — Full episode management with titles, descriptions, cover images, audio files, episode numbers, duration, and publishing dates.
- **RSS / Atom Feed** — Auto-generated podcast feeds at `/podcast/feed/{alias}` with iTunes-compatible RSS 2.0 including `<enclosure>`, `<itunes:image>`, and `<itunes:duration>`.
- **Schema.org** — Automatic `PodcastEpisode` and `PodcastSeries` JSON-LD structured data for better SEO.
- **Content Elements & Modules** — Display episodes via content elements or dedicated frontend modules (channel list, episode reader).
- **Access Protection** — Member-group-based protection on channels.
- **Multi-language** — Built-in English and Persian (Farsi) translations.
- **Cover Images** — Per-episode and per-channel cover artwork with Contao's image size support.
- **Twig Templates** — Fully customizable Twig-based templates for episode display.
- **Sitemap** — Episodes are automatically included in the XML sitemap.

## Requirements

| Dependency | Version |
|---|---|
| PHP | `^8.3` |
| Contao Core Bundle | `^5.3` |
| Symfony | `^6.4` |

## Installation

Install via Composer:

```bash
composer require respinar/contao-podcast
```

### Clear the cache

After installation or any update, clear the Symfony cache:

```bash
php vendor/bin/contao-console cache:clear --env=prod
```

## Setup

### 1. Create a Podcast Channel

Go to the **Podcasts** back-end module and create a new channel:

- **Title** — Name of your podcast
- **Overview page** — Page used for the episode list
- **Jump-to page** — Page used for individual episode detail views
- **Feed** — Enable RSS feed generation for this channel
- **Feed alias** — URL-safe alias for the RSS feed (e.g. `my-podcast`)
- **Language** — Podcast language (e.g. `en`, `fa`)
- **Cover image** — Channel artwork

### 2. Add Episodes

Inside a channel, create episodes:

- **Title** — Episode title
- **Episode number** — Numeric episode identifier
- **Date** — Publication date
- **Audio file** (`podcastSRC`) — The actual podcast audio (MP3, M4A, OGG, etc.)
- **Cover image** (`coverSRC`) — Episode-specific artwork
- **Duration** — Length in seconds (used for RSS and display)
- **Description / Page title** — SEO metadata
- **Subheadline & Teaser** — Optional teaser content

### 3. Configure Front-End Modules

#### Channel List (`podcast_channel`)

Lists episodes from a selected channel with:

- Pagination
- Featured/unfeatured filtering or featured-first sorting
- Episode count limit and skip-first offset
- Custom image sizes

#### Episode Reader (`podcast_episode`)

Displays a single episode detail page. Automatically reads the `auto_item` parameter for SEO-friendly URLs.

#### Content Element (`podcast`)

Embeds a single selected episode directly into article content.

### 4. RSS Feed URL

If a channel has **Feed** enabled and a **Feed alias** set, the feed is available at:

```
https://example.com/podcast/feed/{feedAlias}
```

Example:
```
https://example.com/podcast/feed/my-podcast
```

The feed supports the `feedBase` field for custom base URLs (e.g. CDN or subdomain).

## Templates

Copy the default templates from `contao/templates/` to your project `templates/` directory and customize them:

| Template | Purpose |
|---|---|
| `podcast_full.html.twig` | Single episode / detail view |
| `podcast_teaser.html.twig` | Episode list item / teaser view |

### Available template variables

**Episode template (`podcast_full`):**

- `{{ title }}` — Episode title
- `{{ subheadline }}` — Episode subheadline
- `{{ teaser|raw }}` — Teaser HTML content
- `{{ date }}` — Formatted publication date
- `{{ author }}` — Author name string
- `{{ duration }}` — Human-readable duration
- `{{ figure }}` — Cover image `Figure` object (use `{% with {figure: figure} %}...{% endwith %}`)
- `{{ file.name }}`, `{{ file.path }}`, `{{ file.mime }}` — Audio file info
- `{{ caption }}` — Audio file caption
- `{{ schemaOrgData }}` — JSON-LD array (injected via `{% do add_schema_org(schemaOrgData) %}`)

## Permissions

Back-end users can be restricted via:

| Permission | Key |
|---|---|
| Edit archives | `contao_user.podcasts` |
| Create archives | `contao_user.podcastp.create` |
| Delete archives | `contao_user.podcastfeedp.delete` |
| Edit feeds | `contao_user.podcastfeeds` |
| Create feeds | `contao_user.podcastfeedp.create` |
| Delete feeds | `contao_user.podcastfeedp.delete` |

## Development

### Code style

```bash
composer run cs-fixer
```

### Tests

```bash
composer run unit-tests
```

## License

This bundle is released under the [MIT license](LICENSE).

## Credits

Developed by [Hamid Peywasti](https://respinar.com/) — [Respinar](https://respinar.com/)
