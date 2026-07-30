# Paint Chip Spotlight Artists

A lightweight, dependency-free (no ACF required) plugin for managing monthly
Spotlight Artist exhibitions and the artists who show at The Paint Chip.

## What it adds

- **Artists** (custom post type): name, mediums, website, Instagram, Facebook,
  bio (main editor), portrait (featured image), and a gallery of work images.
- **Exhibitions** (custom post type): title (auto-generated if left blank),
  month, "2nd Friday ArtAbout" yes/no, an event date/time line, description
  (main editor), an image (or automatic fallback to the artist's portrait/work),
  and a searchable picker for attaching one or more Artists.
- A **"Current Spotlight Artist" block** you can drop into the homepage. By
  default it auto-detects whichever Exhibition is tagged with the current
  month; you can also pin it to a specific Exhibition ID.
- A **Spotlight Artists archive page** at `/spotlight-artists/` (the CPT's
  archive URL) listing all exhibitions, newest first, with a plain GET-based
  search by artist/exhibition name or by month -- works even with JS off.
- A **WP-CLI backfill command** to reconstruct historical Exhibitions/Artists
  from your old homepage content.

## Installation

1. Upload the `paintchip-spotlight-artists` folder to `/wp-content/plugins/`.
2. Activate it in wp-admin > Plugins.
3. You'll see a new **Spotlight Artists** menu with **Artists** and
   **Exhibitions** sub-menus.
4. Add your artists first, then create an Exhibition and use the search box
   in the "Featured Artist(s)" box to attach them.
5. Edit your homepage and add the **Current Spotlight Artist** block wherever
   the manually-written section used to live. Delete the old manual content.
6. Create a new Page (or just visit `/spotlight-artists/` directly -- the
   archive works out of the box) to link to from your nav menu.

## Data model notes / assumptions I made

- **Bio and Description use WordPress's native content editor** (rather than
  a custom textarea) so you get the normal WYSIWYG toolbar for free -- the
  meta box above it is relabeled "Bio" / "Description" accordingly.
- **Multiple artists per exhibition** are fully supported, but historically
  your posts have almost always featured one artist -- the auto-generated
  title and sentence ("...to see work by X") adapt automatically for 2+.
- **Exhibition image fallback**: if you don't upload an image directly on
  the Exhibition, it pulls the first attached artist's featured image, then
  their first "images of work" gallery image. You can always override.
- The **Spotlight Artists archive search is server-rendered (GET params)**,
  not AJAX, to keep the plugin simple and avoid a build step / React
  dependency for the public-facing search. If you'd like it to feel more
  "instant" later, that's a straightforward AJAX upgrade on top of this.

## Backfilling historical exhibitions

Run this from SSH on your server, from the WordPress root:

```bash
wp paintchip backfill --from=2021-01 --to=2026-06 --dry-run
```

Drop `--dry-run` once the log output looks right to actually create the
staged drafts.

### How it decides what happened each month

For each month in the range, it targets the **20th** of that month and tries,
in order:

1. **Your homepage's own revision history** (`wp_posts` where
   `post_type = 'revision'`), if WordPress kept it. This is exact and doesn't
   touch the internet at all -- it's the best source when available.
2. **The Wayback Machine's snapshot closest to that date**, as a fallback for
   months where no revision survives.

### Staging, not auto-filling

Because the homepage's layout has changed multiple times since 2021 (columns
vs. no columns, artist name in an h1 vs. buried in an h3, title-first vs.
artist-first, etc.), the backfill command **does not** try to guess which
heading is the artist name, the exhibition title, or the month blurb. Instead,
for each month it:

1. Scans every column-like block on the page for any of three anchor
   phrases: `2nd Friday ArtAbout`, `Spotlight Artist`, `Featured Artist`
   (case-insensitive, anywhere in the block's text).
2. For every matching block, collects **all** heading text, **all**
   paragraph text, and **all** image URLs in it.
3. Creates one Exhibition post per month, titled `[NEEDS REVIEW] <Month
   Year>`, status **draft**, with the collected text dropped into the
   Description box (clearly separated if more than one block matched) and
   every image sideloaded and attached to the post.
4. Saves the original matched HTML to a read-only reference box at the
   bottom of the Exhibition Details metabox, so you can glance at the real
   source while you fill in the actual fields.
5. Flags the post with a "Needs review" status shown in the Exhibitions
   list in wp-admin (there's a **Status** column for this).
6. **Never touches a month that already has a published Exhibition** --
   only creates/updates drafts, so re-running the command is safe.

Your job per staged month: open the draft Exhibition and you'll see:

- **Exhibition Details** -- set the Title, Exhibition Month, whether it's a
  2nd Friday ArtAbout (the event date auto-computes to that month's actual
  2nd Friday the moment you set the month -- you only need to type the time,
  e.g. "5-6 PM"), and the Description (the normal WYSIWYG editor above).
- **Staged Images** -- thumbnails of every image found for that month, each
  with its URL shown in a copyable text box underneath. Click a thumbnail to
  set it as the Exhibition Image directly, or copy a URL to paste into an
  artist row below.
- **Artists for This Exhibition** -- one repeatable block per artist showing
  in the exhibition: Name, Mediums, Website, Instagram, Facebook, Bio, and a
  repeatable list of Image URL + Image Title rows (just paste URLs, no need
  to upload manually). Click **"+ Add another artist"** for multi-artist
  shows -- each filled-in row becomes its own real Artist record when you
  save, matched by name against existing artists so you don't create
  duplicates across multiple shows.
- **Link an Existing Artist** (sidebar) -- if an artist already in the
  database is showing again, search for them here instead of re-entering
  their details.
- **Exhibition Image** (sidebar) -- choose "a specific image" (pick one
  directly) or "use artwork from the artist(s) in this show." The latter
  **only ever pulls from the images you entered for THIS exhibition** in the
  Artists box above -- not an artist's full historical gallery from other
  past shows, even if they've exhibited many times.

Once everything looks right, clear the `[NEEDS REVIEW]` prefix from the
title (the "Needs review" flag clears itself automatically) and publish.

This trades full automation for reliability: it does the copy/paste, image
discovery, and month bucketing for you, but leaves the judgment calls (whose
name is whose, which image belongs to which artist, splitting multi-artist
shows into individual records) to a human, since guessing wrong silently
would be worse than asking.

### About images specifically

The command resolves each image URL it finds to a Media Library attachment,
**preferring an existing attachment over re-downloading** (via
`attachment_url_to_postid()`) since these images were almost certainly
uploaded to your Media Library already when the original monthly post was
created. It only falls back to `media_sideload_image()` (an actual HTTP
download) for images it can't find locally -- which matters on EC2, where a
self-referential HTTPS request back to your own public domain can be slow or
blocked depending on your VPC/security group setup. If you see "image
URL(s) found but ALL failed" in the log, that's a sideload/network problem,
not a matching problem -- the `WP_CLI::warning()` lines right above it will
show the actual error per image.

### Before you run it for real

- **Check revision depth first**, in wp-admin: open the Home page editor and
  look at the revisions list (or run
  `wp post list --post_type=revision --post_parent=<home_id>` via WP-CLI) --
  if it only goes back a few months, most of the range will fall through to
  Wayback.
- **Confirm your host allows outbound HTTP requests** to `archive.org` from
  PHP (`wp_remote_get`) -- some restrictive hosts block this; if so, the
  Wayback fallback will silently return nothing and every month will need
  manual entry or a run from a different environment.
- **Find your home page ID** ahead of time (Pages list in wp-admin, hover the
  title, the ID is in the URL) in case auto-detection via
  `page_on_front` doesn't resolve it, and pass `--home-page-id=123`.

## Recent additions

- **Staged images now show as checkboxes** in each artist block in "Artists
  for This Exhibition" -- check the ones that belong to that artist instead
  of copy/pasting URLs (manual URL rows are still there too, for anything
  not caught during backfill). Each checked image gets its own editable
  title field, defaulting to **"Untitled"**.
- **Duplicate images fixed**: the backfill parser now keeps only the first
  `<img>` per `<figure>`, since the original markup often has several
  size-variants of the same picture in one figure.
- **Publishing an Exhibition also publishes its linked Artists** if they're
  still drafts, so you don't have to publish each one separately.
- **Artist names are auto-linked** to their Artist page wherever they appear
  in an Exhibition's description -- on the single Exhibition page and in the
  homepage block -- pointing to a proper `single-paintchip_artist.php`
  template with their portrait, bio, mediums, socials, and gallery.
- **A per-exhibition image gallery** renders automatically on the single
  Exhibition page, pulling only the images tagged for that show, each
  captioned `Artist Name, Image Title`.

## Bug fixes and further refinements

- **Fixed: "Add another image" button did nothing.** The JS selector was
  wrong (`.siblings()` on an element with no siblings); it now correctly
  finds the images list to append to.
- **Fixed: stale cached assets after updates.** JS/CSS files are now
  versioned by file modification time instead of a static version string,
  so every time you scp+unzip an update, browsers are forced to fetch the
  new file instead of serving a stale cached copy. If you still don't see a
  change after updating, also try a hard-refresh once (Cmd/Ctrl+Shift+R).
- **Image captions are now detected and used as the default title.** If the
  original page had `<figcaption class="wp-element-caption">A Walk Above
  the Beach</figcaption>` next to an image, that text becomes the image's
  title automatically (falls back to "Untitled" only if there's no caption).
- **A "pick an artist who's shown before" dropdown** now sits next to the
  Artist Name field in each row. Selecting someone locks the name field and
  hides the Mediums/Website/Instagram/Facebook/Bio fields (since that data's
  already on file) while keeping the Staged Images and Additional Images
  sections -- so you can still tag new images to a returning artist. Saving
  never overwrites an existing artist's details with blank values, even if
  those fields happen to still be present in the form.

### If you still don't see checkboxes for a given month

The staged-image checklist only appears if that Exhibition actually has
`_pc_staged_image_ids` populated, which depends on the backfill run finding
and resolving images for that specific month. If a month was backfilled
*before* the image-resolution fix (the one where we switched to
`attachment_url_to_postid()`-first), it may have ended up with zero staged
images. Check with:

```bash
wp post meta get <exhibition_id> _pc_staged_image_ids --path=/var/www/html/paintchip
```

If that's empty, re-running the backfill for just that month will
repopulate it (and pick up the new caption-detection while it's at it):

```bash
wp paintchip backfill --from=2024-08 --to=2024-08 --path=/var/www/html/paintchip
```

## Gallery, lightbox, and hero-image refinements

- **Event time now defaults to "6-8:30pm"** on new exhibitions (still fully
  editable).
- **No more duplicate single image.** Both the single Exhibition page and
  single Artist page now count *unique* images first. If there's only one
  (very common -- the event image and the artist's only work image are often
  the same file), it's shown once, large, with its caption underneath, and
  the gallery grid is skipped entirely. The gallery only appears when there
  are genuinely 2+ distinct images.
- **Built-in lightbox, no extra plugin needed.** Clicking any gallery (or
  single hero) image opens it full-size over an 80%-opacity black backdrop;
  clicking anywhere closes it (Escape works too). It's a small vanilla-JS/CSS
  addition (`assets/js/front-lightbox.js`), no dependencies.
- **Artist page**: if an artist has more than one gallery image, the
  standalone portrait is skipped (since it's almost always one of the
  gallery images already) and the full gallery shows instead. Exactly one
  gallery image (or none, falling back to the featured image) still shows
  as a single large image.
- **New "Events at The Paint Chip featuring `<Artist Name>`" section** on the
  Artist page, listing every published Exhibition that artist appears in,
  most recent first.

## Events archive + Artists index + breadcrumb

- **Exhibitions archive** is now titled "The Paint Chip Art Events" (or
  "The Paint Chip Art Events - `<Year>`" when a year is selected). The old
  text-search and date fields are gone, replaced by two dropdowns that act
  instantly on change (no submit button): **Exhibitions by year** (filters
  the card list, updates the URL to `?pc_year=2024`) and **Participating
  Artists** (an alphabetical jump-list straight to that artist's page).
- **New Artists archive** at `/artists/` -- a simple two-column alphabetical
  index, needed as a real destination for the new breadcrumb (see below).
  **Important:** since this is a brand-new archive URL, you need to visit
  **Settings > Permalinks** in wp-admin and click **Save Changes** once
  (this flushes WordPress's rewrite rules) or it'll 404.
- **Single Artist page** reordered: Name, then mediums/website/Instagram/
  Facebook (only shown if any are filled in), then bio, then their artwork
  (one large image if there's only one, otherwise a 2-column gallery that
  fills the content width), then an `<hr>`, then an `<h3>` "Events at The
  Paint Chip featuring `<Name>`" list.
- **Breadcrumb** added to the top of the Artist page:
  Home / Exhibitions / Artists / `<Artist Name>`.

## Homepage block, archive title, and cleanup

- **Archive title** is now "Art Events at The Paint Chip" (plus " - `<Year>`"
  when filtered).
- **Dropdowns are now 50/50 width** on the archive page, each including its
  label.
- **Removed the breadcrumb** from the Artist singular page and the Artists
  archive, since the site already has one elsewhere.
- **Homepage block simplified**: it no longer reconstructs synthetic
  headings/sentences -- it shows the Exhibition's own Title, then its actual
  Description content exactly as entered, then the image(s) below using the
  same single-image-vs-gallery rule as the Exhibition page. A bold, normal-
  sized "The Paint Chip Spotlight Artist Series" link (to the Exhibitions
  archive) now sits above all of it.
- **Month fallback tightened**: if there's no exhibition for the current
  calendar month, it now specifically falls back to the most recent
  *past* month with one (not just "whatever's newest," which could
  theoretically have picked a future-dated draft).
- **Reminder**: since the block now detects the right exhibition
  automatically, you can remove whatever hardcoded homepage content you'd
  been manually swapping in each month and just place the "Current Spotlight
  Artist" block there instead -- it'll always show the right one going
  forward.

## Shortcode alternative to the block

Besides the "Current Spotlight Artist" Gutenberg block, there's now a
`[paintchip_spotlight]` shortcode that does exactly the same thing --
detects the current month's exhibition (falling back to the most recent
past one) and renders it the same way. Use `[paintchip_spotlight id="123"]`
to pin it to a specific exhibition instead.

To use it: paste `[paintchip_spotlight]` directly into the homepage content
(a Custom HTML block, a Classic Editor text area, a text widget, etc.). To
remove it later, just delete that line of text -- no need to find and
delete a specific block in the editor.

## Upload an image directly into the exhibition editor

Above the artist rows in "Artists for This Exhibition," there's now an
**"Upload an image for this exhibition"** button. Clicking it opens the
normal WordPress media uploader (upload a new file or pick an existing one),
then prompts you for a caption. Once confirmed, that image immediately shows
up as a checkbox option in every artist row already on the page -- exactly
like a backfill-staged image -- so you just check whichever artist it
belongs to. If you add more artist rows afterward, they'll include it too.

No separate save step is needed for this -- it uses the same checkbox +
title mechanism as staged images, so it's picked up automatically when you
save the Exhibition as normal.

## Event page reorder + caption rule

- **Single Exhibition page order** is now: Title, the exhibition's content
  (description), date/2nd-Friday line, Featuring, artwork image(s), then the
  back link.
- **Artist name only shows in a caption if there's an actual caption.** If
  an image was never given a real title (still the "Untitled" default, or
  genuinely blank), no caption line is shown at all -- not even just the
  artist's name on its own. This applies to both the single hero image and
  the gallery grid on the Exhibition page.

## Content width

All four plugin page templates (Exhibitions archive, Artists archive,
single Exhibition, single Artist) now share one CSS class,
`.paintchip-content-width` (in `assets/css/front.css`), set to
`max-width: 1100px`. To change the width later, edit that one rule instead
of hunting through each template's PHP file.

## Fixed: editing an exhibition didn't show existing artists/images

Reopening an already-saved Exhibition previously always showed one blank
artist row, regardless of what was actually linked -- because that box was
only ever designed for adding brand-new artists. Now:

- **One row per already-linked artist** is pre-filled automatically (name
  locked, since it's an existing record), with their currently-assigned
  images already checked.
- **To edit a caption**: find the image's checkbox (it'll already be
  checked) and just change the text in the title field next to it, then
  save.
- **Unchecking an image now actually removes that association** on save,
  instead of silently sticking around forever (a real bug in the old
  merge-only logic -- it only ever added, never removed).
- This also explains why the "Use artwork from the artist(s)" option on the
  sidebar Exhibition Image box looked empty -- it had nothing to pull from
  if the per-exhibition image map was never populated. Should populate
  correctly now once you check the pre-filled boxes and save.

You can still add brand-new artists with "+ Add another artist" as before.

## Backfill review aids hidden once published

The "Staged Images" preview list and the raw-scrape reference textarea (both
purely for reviewing backfilled content) now only show while an Exhibition
is still a draft/pending review. Once it's published, they disappear
automatically -- the functional image checkboxes inside each artist row are
unaffected and still work the same regardless of publish state.
