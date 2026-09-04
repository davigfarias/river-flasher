---
paths:
  - resources/views/components/create-deck-menu-modal.blade.php
---

# Components

## Chain Flux modals with a nested modal.trigger + modal.close
To have a button close the currently-open Flux modal and open a different one in the same click, wrap it in both: `<flux:modal.trigger name="target"><flux:modal.close><flux:button>...</flux:button></flux:modal.close></flux:modal.trigger>`. flux:modal.close's `<ui-close>` closes its nearest ancestor `<ui-modal>` regardless of name; flux:modal.trigger dispatches the `modal-show` event for the target name. Click bubbles inner→outer, so close fires before the new modal opens.

create-deck-menu-modal.blade.php is the single entry point (nav + decks.blade.php both trigger `create-deck-menu`) that fans out to `new-deck` and `deck-from-tag-mode` (both closed/reopened this way) plus a plain wire:navigate link to the decks.import-csv page for CSV import (too complex for a modal — same reasoning as why "baralho por tema" is a page, not a modal).
