---
paths:
  - 'app/{Actions/CalculateRecallCounters.php,Models/Card.php,DTO/RecallCounters.php}'
  - 'app/{Actions/StoreCardImage.php,Actions/DeleteCardImage.php,Models/Card.php}'
  - 'app/{Actions/FindCardsByTag.php,Actions/FindCardsByCategory.php,Actions/GetAvailableTags.php,Actions/GetAvailableCategories.php,Models/Card.php}'
  - 'app/{Actions/InsertCardsBulk.php,Actions/ParseCsvCards.php}'
---

# App Actions

## Recall counters replaced SM-2 — CalculateRecallCounters and Card::scopeToReinforce are the only sources of truth
There is no spaced-repetition scheduling anymore. Each card just tracks `aced_count` (lembrei) and `missed_count` (não lembrei). `CalculateRecallCounters` is the single pure action that increments the right one for a `ReviewResult` — no DB, no clock reads — used by `AnswerCardOrchestrator` when persisting a real answer. "Needs reinforcing" is defined exactly once, as `Card::scopeToReinforce` (`missed_count > aced_count`, not `missed_count > 0`, so a card corrects itself back off the list as it's answered right) — every action that needs this list (`CountCardsToReinforce`, `GetRecentDecksSummary`, `FindCardsToStudy`'s ordering) goes through the scope rather than re-deriving the comparison.

## Card images use the default filesystem disk, no per-object visibility
`Card::image_path` is stored on whatever disk `config('filesystems.default')` resolves to (env `FILESYSTEM_DISK`, `public` locally). `StoreCardImage` always calls plain `store()`, never `storePublicly()`/`storePubliclyAs()` — Laravel Cloud Object Storage buckets are Cloudflare R2, which sets visibility at the bucket level and rejects per-object ACL headers with a `NotImplemented` error. In production, mark the attached bucket "public" in Laravel Cloud instead. `Card::imageUrl()` wraps `Storage::url($this->image_path)` on the default disk — never hardcode a disk name when reading/writing card images.

## LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK must stay local, never the S3/object-storage disk
`Image::fromUpload()` reads the uploaded file via `$file->getContent()` -> `getRealPath()`. On Livewire's `TemporaryUploadedFile`, `getRealPath()` resolves through `Storage::disk($tempDisk)->path($path)`, which for an S3-driver disk just returns the raw object key (no real filesystem path exists), so `file_get_contents()` fails with "No such file or directory". Livewire's temp-upload disk defaults to `config('filesystems.default')` when `LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK` isn't set — so once `FILESYSTEM_DISK` points at a bucket (prod, via Laravel Cloud Object Storage), image uploads break. Fix/prevention: `LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK` must always be `local`, in every environment, regardless of what the app's default disk is. Only the final, already-processed image (stored explicitly via `StoreCardImage`'s `->store()` call) goes to the default/bucket disk — temp uploads never should.

## Deck-from-tag page is mode-driven by `pos` vs `category`, not two pages
Cards can be grouped into a deck either by `pos` (classe gramatical: substantivo, verbo…) or `category` (categoria lexical: partes do corpo, objetos de casa…) — both are free-text string(50) columns filled in via a datalist on the card form, no fixed enum. The `pages::deck-from-tag` Livewire page (routes/web.php `decks.from-tag`) is generic: a `#[Url] public string $by = 'pos'` picks which of {GetAvailableTags,GetAvailableCategories} / {FindCardsByTag,FindCardsByCategory} to call — don't fork this into a second page. The "Baralho por tema" entry points (app-nav.blade.php, decks.blade.php) open the shared `x-deck-from-tag-modal` component (registered once in layouts/app.blade.php) which lets the user pick pos vs category before navigating to `route('decks.from-tag', ['by' => ...])`.

## CSV import: bulk insert bypasses Eloquent, language falls back deck → CSV → user
InsertCardsBulk uses Card::query()->insert() (bulk), not ->create() — no model events, no auto timestamps, so created_at/updated_at are stamped manually. New-card defaults are set explicitly: image_path null, is_active true, aced_count/missed_count 0, last_reviewed_at null.

ParseCsvCards's `language` CSV column is optional. Effective import language resolves in this order (decided with the user, not the original plan.md draft): 1) the destination deck's existing language via GetDeckLanguage if it already has cards, 2) the CSV's own detected language if uniform, 3) a language the user picks explicitly in the decks-import-csv page UI (only shown when neither of the above resolves it). A CSV whose language column has mixed values is rejected outright (languagesMixed), never silently picks one.
