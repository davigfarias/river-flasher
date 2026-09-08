---
paths:
  - 'resources/views/{pages/⚡flashcards-create/*,components/⚡edit-card-modal*}'
---

# Flashcards Create

## Card image upload: guard temporaryUrl() and disable submit while uploading
The image preview must be `@if ($form->image->isPreviewable())` before `temporaryUrl()` — an avif/heic upload otherwise throws FileNotPreviewableException and 500s the render (preview_mimes has no avif and config/livewire.php isn't published). Non-previewable => show a "será convertida ao salvar" notice; StoreCardImage re-encodes to webp anyway.
Submit button carries `wire:target="form.image, <saveMethod>" wire:loading.attr="disabled"` so it's dead while the file uploads (Livewire uploads a file input immediately, regardless of wire:model modifier) and during the save request — stops a submit landing before the upload finishes.
