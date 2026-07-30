# Paint Chip Inventory Sync

Imports the POS **General Inventory Full Master List** report, shows every change before it lands, applies stock through WooCommerce CRUD, and can roll a whole batch back.

## Install

1. Copy the `paintchip-inventory` folder to `wp-content/plugins/`.
2. Activate it. Three tables are created: `{prefix}pci_runs`, `{prefix}pci_items`, `{prefix}pci_journal`.
3. Go to **Inventory Sync → Suppliers** and confirm the policy for each supplier code.
4. Go to **Inventory Sync → Settings** and choose what "hide" should do.
5. Upload a report from **Inventory Sync → Batches**.

Requires WooCommerce and PHP 7.4+. Uploaded reports are stored in
`wp-content/uploads/pci-inventory/` with an `.htaccess` deny rule, because the
report contains the client's full cost book.

## Flow

```
upload → parse → classify → REVIEW → apply → (roll back)
```

Nothing touches a product until someone presses **Apply this batch**. Every
product that is touched gets a before-snapshot written to `pci_journal` in the
same request, and rollback replays those snapshots.

## Verified against the 13-Jul-25 report

Run through the real 3.2 MB report and the live 3,395-SKU catalogue:

| | |
|---|---|
| Records parsed | 9,808 |
| Unpaired lines | 0 |
| Parse time | 130 ms |
| Classify time | 62 ms |

Resolved actions (MA active, to match the independent analysis):

| Action | SKUs |
|---|---|
| update | 2,486 |
| hide | 593 |
| remove | 8 |
| new | 2,072 |
| ignore (legacy) | 2,269 |
| flag_nokey | 373 |
| flag_negative | 262 |
| flag_ambiguous | 52 |
| flag_dupsku | 10 |
| flag_review | 4 |
| **total** | **8,129** |

`update` and `hide` are 8 and 2 lower than a naive pass because the
duplicate-SKU guard pulls those 10 SKUs out before they can write the same
quantity to two different products.

With `MA` set to discontinued (the shipped default), `new` drops from 2,072 to
**1,396** — 676 MacPherson's products that will not be created.

## Decisions encoded in the code

**The report is fixed-width.** Offsets live in `PCI_Parser::MASTER` and
`::DETAIL`, derived by finding every character column that is blank across all
9,808 records and cross-checking the header row. The old CodeIgniter tool split
on runs of whitespace, which made the field count depend on whichever optional
columns happened to be filled, and silently dropped 1,996 of 9,808 records.

**The join key is the Alternate column, matched verbatim against `_sku`.**
That hit 3,129 of 3,381 live products (92.5%). Item ID and Barcode both matched
zero. Fuzzy fallbacks were measured and rescued 8 products out of 252, so they
are deliberately absent.

**The SKU prefix is a brand code, not a distributor.** `LQ` Liquitex, `TB`
Tombow, `RY` Royal — all arriving via `SS` or `MA`. Supplier attribution comes
from the Vend column only.

**Absence from the report never means discontinued.** 252 live products are
absent, 116 of them a single `DEDA` family whose vendor code appears nowhere in
the file. There is no code path that infers removal from absence.

**Everything is written through CRUD.** `wc_get_product()` →
`set_stock_quantity()` → `save()`, never a raw `UPDATE wp_postmeta`. The old
tool wrote `_stock` directly and never recalculated `_stock_status`, which is
why 366 products sat at zero stock while still advertised as in stock, and why
`wp_wc_product_meta_lookup` drifted.

**Stock management is forced on.** Roughly 38% of the catalogue had
`_manage_stock = 'no'` with a NULL `_stock`, and WooCommerce ignores a quantity
on those products entirely. Any product this plugin manages gets
`set_manage_stock(true)` first.

**Prices are off by default.** When enabled, only `_regular_price` is written
and WooCommerce recalculates the effective price, so an active sale survives.

**Supplier policy overrides the file.** MacPherson's still shows Max > 0 on
1,980 of 2,194 rows and a reorder point on 1,857, because nobody zeroed the POS
after the contract ended. Intent has to come from a human.

## Safety

- **Threshold guard.** A batch that would hide or remove more than 25%
  (configurable) of published products refuses to apply until someone ticks an
  override box. That check would have caught whatever went wrong before.
- **Duplicate-SKU guard.** A SKU mapping to more than one product is flagged and
  skipped, never written.
- **Removals are trashed, not deleted.** Recoverable from Products → Trash and
  by rollback.
- **Flagged rows export to CSV** so nothing is quietly lost.

## Scrapers

`PCI_Scraper` is the per-supplier contract: given a SKU, return title,
description, UPC, MSRP, image URL and categories, or a `WP_Error`.

`PCI_Scraper_SLS` (Vend code `SS`) is implemented and needs **no login**:

- `viewitem.asp?slssku=SKU` — item detail
- `visual_right.asp?txtfind=SKU` — category breadcrumb

Images follow `images/Product Images/Regular Images/{first 2 chars}/{rest}.jpg`,
so `MW200630` → `MW/200630.jpg`.

Fetching stages the data on the item row for review. **It does not create
products** — that step is still to be built, and needs the category mapping
decided first.

> The SLS parser was written against the rendered text of a live item page. The
> regexes should be validated against raw HTML for a single-item SKU (the page
> verified was an assortment) before a bulk run.

## Detecting a convention change

The classification rule — Max and quantity both zero means gone for good — is an
*inference* from one report, not something the report states. It can go stale
silently. So every run is also fingerprinted, and the preview screen shows the
evidence next to the conclusions.

**Column profile.** What each column actually contains this month. Six columns
carry no signal in the baseline, and nothing reads them yet:

| Column | Baseline |
|---|---|
| Deleted | `No` on 100% |
| Active | `Yes` on 100% |
| Multiplier | `0` on 100% |
| Fixed | `0` on 100% |
| QCo | `0` on 99.96% |
| Tax | `Yes` on 99.98% |

Those six are the likeliest place a new convention appears. If one starts
varying, the preview says so in red before anyone approves the batch.

**Run-to-run transitions.** Two conventions for "gone for good" look identical
in a single report and completely different across two:

- row stays, Max drops to 0 → `still_listed_max_zeroed`
- row disappears from the export → `dropped_from_report`

If the first is zero and the second is large, the POS deletes records rather
than flagging them, the Max rule will almost never fire, and the plugin says so
explicitly rather than reporting a confident "8 removals".

Verified by simulation against the real report: flipping 400 rows to
`Deleted=Yes` raises a high-severity warning; a new `Dept` code is reported;
churning 500 barcodes and notes reports **nothing**, because high-cardinality
columns are only sampled and diffing them would emit noise every month.

**Pluggable rules.** When the real convention is known, hook
`pci_resolved_item` rather than forking the classifier:

```php
add_filter( 'pci_resolved_item', function ( $item, $sku, $rows ) {
    // Every parsed column is in $rows, including the ones no rule reads.
    if ( 'Yes' === $rows[0]['deleted'] ) {
        $item['action'] = PCI_Classifier::REMOVE;
    }
    return $item;
}, 10, 3 );
```

## Product sourcing

Three stages. **Nothing runs on a schedule — every stage is started by a person.**

**1. Fetch.** Sourcing screen → *Fetch next 10* or *Keep fetching until done*. Each
row gets one request to `viewitem.asp?slssku=<sku>`, and the result is parked on
the staged row as JSON. Responses cache for 24 hours; 0.4s between requests.
Resumable — re-running only fetches what is still missing. **Creates nothing.**

**2. Preview.** A card per product: image, title, SKU, price, quantity, UPC,
category match, and where the image came from. Look at ten; if they are right,
move on.

**3. Create drafts.** Choose how many. Products are created with
`post_status = draft`, so nothing is visible on the site. Then the review screen
gives you *Publish*, *Edit* or *Reject* per product, plus *Publish all*.
Rejected products go to Trash.

### Decisions encoded

- **Price comes from the POS report**, not SLS MSRP. The MSRP is shown on the
  card for reference. This is a catalog of what the store stocks, so the store's
  price is the right one.
- **Titles are sentence case.** SLS shouts; `MOLOTOW ACRYLIC PAINT MARKER 4MM` →
  `Molotow acrylic paint marker 4mm`. Titles already in mixed case are left
  alone. `8 X 10` is tidied to `8x10`. Works with or without mbstring.
- **Categories are matched, never created.** SLS breadcrumbs are compared against
  existing `product_cat` terms by name, slug, and punctuation-stripped name —
  the site tree was built from the SLS categories, so most match directly.
  Anything unmatched is named on the card and the product is left uncategorised.
- **Images**: the SLS image is used when it is at least 300px wide (configurable).
  Otherwise Barcode Spider is queried by UPC, exactly as the legacy tool did, and
  the first image at or above the threshold wins. The card says which source was
  used. Without a token the fallback is skipped and the card says so.
- **Provenance is recorded.** Every created product gets `_pci_vend`,
  `_pci_item_id`, `_pci_source_url` and `_pci_created_run`, so future runs never
  have to infer where a product came from. `_wpm_gtin_code` gets the UPC.
- **Existing SKUs are never duplicated** — `wc_get_product_id_by_sku` is checked
  before creation.

### Coverage

Only SLS (`SS`) has an adapter: 433 of the 1,396 new products. The rest —
`NO` 369, `LW` 82, `PA` 71, `AD` 61, `BC` 57 and others — have no catalog source
and will report *No adapter for supplier X*. Add one by implementing
`PCI_Scraper` and registering it.

## Still open

- What "hide" should mean — check `woocommerce_hide_out_of_stock_items`.
- The 116 `DEDA` products from a vendor absent from the report.
- Policy for the 262 negative-quantity SKUs.
- Whether one Alternate with several sizes is one website product or several.
- Product creation from staged scrape data, including category mapping.
- Fix the 13 duplicate-SKU rows by hand before the first real run.
