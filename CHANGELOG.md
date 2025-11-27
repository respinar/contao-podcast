# Changelog

## [Unreleased] - 2025-07-10

### Added
- **RSS Feed Controller** (`src/Controller/FrontendModule/PodcastFeedController.php`)
  - New route `/podcast/feed/{alias}` generates RSS 2.0 feeds with iTunes extensions.
  - Supports `<enclosure>` tags for audio files, `<itunes:image>`, `<itunes:duration>`, and channel metadata.
  - Respects channel settings: `feedAlias`, `feedBase`, `maxItems`, `language`, `coverSRC`.
  - Route registered via `ContaoManager\Plugin::getRouteCollection()` for maximum compatibility.
- **Translation key** `MSC.podcastOverview` added to English and Persian language files.
- **`autoload-dev`** section added to `composer.json` for PSR-4 test autoloading.

### Fixed
- **Critical Bug: PodcastChannelController variable overwrite**
  - `$page` was overwritten with a pagination integer, then passed to `parseEpisodes()` expecting a `PageModel`. Fixed by storing pagination in a separate `$pageNumber` variable.
- **Critical Bug: Empty audio source in template**
  - `Contao\File` does not implement `\_\_isset()`, so Twig's PropertyAccess silently failed to resolve `file.mime` and `file.path`. Fixed by passing file data as a plain array to the template instead of the raw object.
- **Critical Bug: SitemapListener never registered**
  - Used `@ServiceTag` annotation from `terminal42/service-annotation-bundle`, which was not a declared dependency. Replaced with native Symfony `#[AsEventListener]` attribute.
- **Critical Bug: Null pointer crashes in PodcastEpisodeController**
  - `$objEpisode->getRelated('pid')` and `PageModel::findById()` could return `null`; added null-safety guards before chaining method calls.
- **Broken test reference**
  - `PluginTest.php` referenced non-existent `RespinarContaoPodcastBundle`; corrected to `RespinarPodcastBundle`.
- **Staleness issue after code changes**
  - Documented need to clear Symfony container cache when constructor signatures change.

### Changed
- **PodcastUtil converted from static utility to Symfony service**
  - Now receives `Symfony\Bundle\SecurityBundle\Security` via constructor injection.
  - `isProtected()` and `sortOutProtected()` are no longer static methods.
  - Eliminated `System::getContainer()->get('security.helper')` service locator anti-pattern.
- **PodcastParser & PodcastSchema**
  - Now inject `PodcastUtil` service instead of using static method calls.
- **PodcastEpisodeController**
  - Injects `ResponseContextAccessor` and `HtmlDecoder` instead of `System::getContainer()->get()` service locators.
- **PodcastChannelController**
  - Injects `PodcastUtil` service.
  - Replaced `empty($objChannel)` on model objects with proper `null === $objChannel` check.
  - Replaced raw `header('HTTP/1.1 404 Not Found')` with `PageNotFoundException`.
  - Removed `global $objPage` usage; uses `$this->getPageModel()` consistently.
  - `intval()` calls modernized to `(int)` casts.
- **EpisodeModel**
  - Fixed copy-paste docblocks: "product items" → "episodes".
  - Unified time checks to use `Date::floorToMinute()` instead of mixed `time()` usage.
  - Modernized nullable parameter syntax: `$blnFeatured=null` → `?bool $blnFeatured = null`.
  - Modernized default array syntax: `array()` → `[]`.
- **SitemapListener**
  - Switched from `@ServiceTag` annotation to `#[AsEventListener]`.
  - Cleaned up copy-paste terminology ("catalog" → "channel").
- **PreviewUrlConvertListener**
  - Removed unused/accidental imports (`Spatie\SchemaOrg\Episode`, `Respinar\PodcastBundle\Podcast`).
  - Extracted assignment from `if` conditional for improved readability.
- **PreviewUrlCreateListener**
  - Fixed copy-paste comment: "product category" → "episode list".
- **Plugin (ContaoManager)**
  - `getRouteCollection()` now properly registers the podcast feed route.
  - Added explicit nullable return type declaration.

### DCA / Database
- **tl_content**
  - Fixed `podcast_episode` SQL type from `blob NULL` to `int(10) unsigned NULL` (foreign key select, not a blob).
- **tl_podcast_channel**
  - Removed 8 dead/unused comment-related field definitions: `allowComments`, `notify`, `sortOrder`, `perPage`, `moderate`, `bbcode`, `requireLogin`, `disableCaptcha`.
  - Removed commented-out `allowComments` subpalette line.
- **tl_podcast_episode**
  - Removed unused `noComments` field.
  - Fixed class docblock: removed erroneous `@property News $News`.
  - Fixed `generateAlias` docblock: "news alias" → "episode alias".

### Templates
- **podcast_full.html.twig**
  - Removed undefined `{{ containerClass }}` attribute from `<figure>` tag.

### Translations
- **English (en)**
  - Added `MSC.podcastOverview`.
  - Fixed typos: "Podacsts" → "Podcasts", "deatil" → "detail".
- **Persian (fa)**
  - Synced typo fixes.
  - Added Persian translation for `MSC.podcastOverview`.

### Known Limitations / Notes
- The RSS feed generates basic RSS 2.0 with iTunes namespace extensions. Full podcast standard compliance (e.g. iTunes categories, explicit tags, episode-level images) can be added in future iterations.
- `featured_first` sorting is now implemented: when selected, episodes sort by `featured DESC` first, then by the chosen secondary order (defaulting to `date DESC`).
