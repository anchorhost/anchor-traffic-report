# Anchor Traffic Report

A WordPress plugin for editorial-style viral-post traffic recap reports.

The plugin is a thin data layer plus a renderer. It does **not** fetch
anything. An AI agent (or a human with `curl`) populates the data over
WP-CLI or REST, and the renderer emits a self-contained HTML file you can
host as a static report.

Screenshots of the rendered output:
- [godaddy-viral-report.html](https://anchor.host/reports/godaddy-viral-report.html)
- [wp-plugin-backdoor-report.html](https://anchor.host/reports/wp-plugin-backdoor-report.html)


## Why

When a blog post goes viral, you want a single page that captures what
happened: a chart of the spike, where the traffic came from, what people
said, and which press outlets picked it up. Doing that in HTML by hand
takes ~30 edits per refresh and gets stale fast. This plugin reduces that
to a handful of `wp atr` commands plus a render.

Snapshots are first-class. Each refresh can be saved as a point-in-time
copy of every metric, and the rendered report ships an interactive
scrubber so a reader can flip the whole page back to "Day 1" or
"Day 4" with a click.


## Quick start

```bash
# Activate
wp plugin activate anchor-traffic-report

# Create a report
wp atr report create --input=report.json

# Load data
wp atr traffic   <slug> --input=traffic.json
wp atr dim       <slug> --kind=referrer --input=referrers.json
wp atr dim       <slug> --kind=country  --input=countries.json
wp atr platform  add <slug> --input=platforms.json
wp atr comment   add <slug> --input=comments.json
wp atr timeline  add <slug> --input=timeline.json

# Optional: freeze the current state as a snapshot
wp atr snapshot save <slug> --captured-at="2026-04-29 11:00:00" --label="Apr 29"

# Render to <abspath>/reports/<slug>-report.html
wp atr render <slug>
```

The output is a single self-contained HTML file (inline CSS + JS, no
external assets). Upload it wherever — `scp` to a static directory, drop
it in S3, paste it into Cloudflare R2.


## Concepts

A **report** is a single viral post. It has a `slug`, a `title`, a hero
section, a section-ledes JSON blob, and a totals JSON blob.

A **snapshot** is a point-in-time copy of the entire report state
(totals, traffic, dimensions, platforms, comments, press, timeline).
Snapshots are immutable JSON payloads. The renderer ships every snapshot
as a separate body in the HTML and a scrubber UI lets the reader
switch between them. Snapshot timestamps are arbitrary — capture at
24 hours, a week, a month later, whatever you want.

The renderer auto-detects whether traffic data is hourly or daily based
on the time delta between rows, so you can use the same `traffic` table
for a 24-hour spike (hourly bars) or a 30-day arc (daily bars).


## Data model

| Table | Purpose |
|-------|---------|
| `wp_atr_reports` | Top-level row per report — slug, title, hero, totals JSON, ledes, theme config. |
| `wp_atr_traffic_hourly` | Time-series visits + pageviews. Stores hourly **or** daily rows. |
| `wp_atr_dimensions` | Referrers, countries, browsers, devices. Replace-in-place on each refresh. |
| `wp_atr_platforms` | "By Platform" cards (HN, X, Reddit threads, etc.) with a 4-stat grid each. |
| `wp_atr_comments` | Notable quotes — featured + regular. |
| `wp_atr_press_pickups` | Outlet, author, URL, publish date. (Storage now; render block coming.) |
| `wp_atr_timeline` | Sequence-of-events cards. Optional `chart_marker` flag draws a labeled vertical line on the chart. |
| `wp_atr_snapshots` | Frozen full-state JSON per capture. Powers the scrubber UI. |

Schema is created via `dbDelta` on plugin activation. Bumping
`ATR_DB_VERSION` in the bootstrap file triggers a migration on the next
plugins-loaded.


## WP-CLI reference

All commands accept JSON via `--input=<path|->`. Pass `-` to read from
stdin. Inline flags also flow into the row, so you can mix.

```bash
# Reports
wp atr report create  --input=report.json
wp atr report update  <slug> --input=report.json
wp atr report show    <slug>
wp atr report list
wp atr report delete  <slug>
wp atr report refresh <slug>           # bumps refreshed_at

# Hourly/daily traffic — replaces all rows
wp atr traffic <slug> --input=traffic.json
wp atr traffic <slug> --clear

# Dimensions — replaces all rows of the given kind
wp atr dim <slug> --kind=referrer|country|browser|device --input=rows.json
wp atr dim <slug> --kind=referrer --clear

# Platforms / Comments / Press / Timeline — append-only
wp atr platform add   <slug> --input=cards.json   # accepts single or array
wp atr platform list  <slug>
wp atr platform clear <slug>

wp atr comment  add|list|clear  <slug> [--input=...]
wp atr press    add|list|clear  <slug> [--input=...]
wp atr timeline add|list|clear  <slug> [--input=...]

# Snapshots
wp atr snapshot save   <slug> --captured-at="YYYY-MM-DD HH:MM:SS" --label="..." [--note="..."]
wp atr snapshot list   <slug>
wp atr snapshot show   <id>
wp atr snapshot delete <id>
wp atr snapshot clear  <slug>

# Render
wp atr render <slug>                  # -> <abspath>/reports/<slug>-report.html
wp atr render <slug> --out=/some/path
wp atr render <slug> --stdout         # write to stdout for piping
```

> **Note on `--input` (not `--json`):** WP-CLI reserves `--json` as a
> shorthand for `--format=json` on its own commands, and the value gets
> swallowed silently. We use `--input` instead.


## REST API

Auth: `manage_options` for write endpoints (use a WordPress
application password). Read endpoints are public.

```
GET    /wp-json/atr/v1/reports
GET    /wp-json/atr/v1/reports/<slug>
POST   /wp-json/atr/v1/reports
PATCH  /wp-json/atr/v1/reports/<slug>
DELETE /wp-json/atr/v1/reports/<slug>

PUT    /wp-json/atr/v1/reports/<slug>/traffic                  body: array of hourly rows
PUT    /wp-json/atr/v1/reports/<slug>/dimensions/<kind>        body: array of dim rows
DELETE /wp-json/atr/v1/reports/<slug>/dimensions/<kind>

POST   /wp-json/atr/v1/reports/<slug>/platforms                body: object or array
DELETE /wp-json/atr/v1/reports/<slug>/platforms

POST   /wp-json/atr/v1/reports/<slug>/comments                 body: object or array
DELETE /wp-json/atr/v1/reports/<slug>/comments

POST   /wp-json/atr/v1/reports/<slug>/press                    body: object or array
POST   /wp-json/atr/v1/reports/<slug>/timeline                 body: object or array

GET    /wp-json/atr/v1/reports/<slug>/render                   returns HTML
POST   /wp-json/atr/v1/reports/<slug>/render                   body: {"out": "/path"}; writes file
```

`GET /reports/<slug>` returns the report row plus all related data,
which is convenient for syncing state to an external store.


## Payload shapes

### Report

```json
{
  "slug": "godaddy-viral",
  "title": "How a GoDaddy Story Hit the Front Page",
  "post_url": "https://example.com/post/",
  "post_published_at": "2026-04-26 14:32:00",
  "status": "active",
  "kicker": "Viral Recap · 70 Hours",
  "headline_html": "A GoDaddy story hit the<br><span class=\"accent\">Hacker News front page.</span>",
  "hero_subtitle_html": "One blog post drove <strong>11,830 visits</strong>...",
  "context_callout_html": "For context: <strong>~2,186 visits/day</strong> baseline...",
  "hero_stats": [
    {"value": "13,650", "label": "Pageviews", "note": "across 70 hours"}
  ],
  "totals": {"visits": 11830, "pageviews": 13650, "peak_value": 1921},
  "section_ledes": {
    "timeline_title": "How the spike actually unfolded",
    "timeline": "Eastern Time. The story slow-rolled on X for hours...",
    "platforms_title": "Where the traffic actually came from",
    "platforms": "...",
    "referrers_title": "...",
    "comments_title": "...",
    "audience_title": "..."
  },
  "config": {
    "brand": "Anchor Hosting · Traffic Report",
    "date_label": "April 26–29, 2026",
    "sources_label": "SOURCES · Fathom · HN · Reddit · X · The Register"
  },
  "refreshed_at": "2026-04-29 11:00:00"
}
```

### Traffic row

```json
{ "hour_utc": "2026-04-26 18:00:00", "visits": 923, "pageviews": 949, "partial": 0 }
```

The renderer detects hourly vs daily granularity from the gap between
the first two rows. Anything ≥ 12 hours = daily mode (chart bars become
day labels, day boundary row collapses to a month label).

### Dimension row

```json
{
  "key": "news.ycombinator.com",
  "label": "news.ycombinator.com",
  "note": "Hacker News",
  "visits": 5000,
  "pageviews": 5280,
  "meta": {"flag": "🇺🇸"}
}
```

`meta.flag` is used on country rows; `meta.icon` is used on device
rows.

### Platform card

```json
{
  "kind": "hn",
  "label": "Hacker News",
  "badge": "Y",
  "accent": "hn",
  "url": "https://news.ycombinator.com/item?id=47911780",
  "posted_label": "Apr 26 · 1:16 PM EST",
  "headline_html": "\"...\" <em>— front page</em>",
  "stats": [
    {"value": "682", "label": "Points"},
    {"value": "253", "label": "Comments"},
    {"value": "5,000", "label": "Visits driven"},
    {"value": "42%", "label": "Of all traffic"}
  ],
  "meta_html": "Submitted by <span class=\"mono\">jamesponddotco</span>",
  "size": "large",
  "position": 1
}
```

`accent` controls border tint and badge color. Built-in values: `hn`
(orange), `x` (sky-blue), `rd` (red, for Reddit), `elreg` (red, for
The Register). The first two cards by `position` render side-by-side at
the top of the platforms grid; remaining cards fill a row below.

### Timeline event

```json
{
  "event_at": "2026-04-26 18:16:00",
  "label": "Submitted to Hacker News",
  "description_html": "jamesponddotco posts the link.",
  "marker": "fire",
  "chart_marker": true,
  "chart_label": "HN submit · 1:16 PM",
  "position": 2
}
```

`marker: "fire"` highlights the timeline card with an accent top border.
`chart_marker: true` draws a vertical dashed line on the visits chart at
the event's time, labeled with `chart_label` (or auto-formatted from
`label` + time).

### Comment

```json
{
  "source_kind": "hn",
  "author": "An_Old_Dog",
  "role_label": "El Reg",
  "body_html": "<span class=\"hl\">'GoDaddy: ...'</span>",
  "url": "https://forums.theregister.com/...",
  "source_label": "The Register · 8 upvotes",
  "avatar": "A",
  "featured": 0,
  "position": 5
}
```

The first featured comment renders as the large hero quote (with a
warm gradient). Wrap key phrases in `<span class="hl">…</span>` for an
accent-tinted highlight.


## Render output

The default destination is `<abspath>/reports/<slug>-report.html`. The
HTML is fully self-contained:

- All CSS is inlined.
- All JavaScript is inlined.
- No external font, image, or asset requests beyond URLs in
  user-supplied content (links to source posts).

The output respects `prefers-color-scheme` on first visit, then persists
the user's choice to `localStorage` under `report-theme`.


## Snapshot timeline

If the report has zero snapshots, the renderer emits a single body and
no scrubber. As soon as a second snapshot exists, the renderer:

1. Renders one `<div class="snap-body" data-snap="...">` per snapshot,
   chronologically.
2. Adds a horizontal scrubber bar with one dot per snapshot, positioned
   proportionally by `captured_at`.
3. Hides every body except the latest by default.
4. Registers click + arrow-key handlers that swap visible bodies and
   update the masthead's right-side label to the active snapshot.

Snapshots that fall on nearly the same timestamp are spaced apart
visually so dots never stack. Each snapshot has its own SVG gradient
ids so charts don't share fills across frames.


## Project layout

```
anchor-traffic-report/
├── anchor-traffic-report.php             bootstrap, hooks, version
├── includes/
│   ├── helpers.php                       UTC→EST conversion, formatting
│   ├── class-atr-schema.php              dbDelta install + maybe_upgrade
│   ├── class-atr-reports.php             reports CRUD
│   ├── class-atr-traffic.php             hourly/daily traffic CRUD
│   ├── class-atr-dimensions.php          referrer / country / browser / device
│   ├── class-atr-platforms.php           "By Platform" cards
│   ├── class-atr-comments.php            notable comments
│   ├── class-atr-press.php               press pickups
│   ├── class-atr-timeline.php            timeline events
│   ├── class-atr-snapshots.php           full-state snapshot save + payload
│   ├── class-atr-renderer.php            chart geometry, frame builder, write
│   ├── class-atr-rest.php                REST controllers
│   └── class-atr-cli.php                 WP-CLI commands
└── templates/
    ├── report.php                        masthead + scrubber + frames + footer
    └── partials/
        ├── body.php                      one frame's hero + chart + sections
        └── styles.css.php                inline CSS (gets emitted into a <style> tag)
```


## Requirements

- WordPress 6.0+
- PHP 7.4+
- WP-CLI (for the `wp atr` commands; the REST API works without it)


## Contributing

Commits follow [Emoji-Log](https://github.com/ahmadawais/Emoji-Log):

- `📦 NEW: ...` — new features, files, capabilities
- `👌 IMPROVE: ...` — refactors, design improvements, perf
- `🐛 FIX: ...` — bug fixes
- `📖 DOC: ...` — documentation
- `🚀 RELEASE: ...` — version bumps
- `✅ TEST: ...` — tests


## License

MIT. See [LICENSE](LICENSE).
