---
paths:
  - .env
---

# General

## APP_URL mismatches the Herd domain — Storage::url() breaks in the browser
.env has APP_URL=http://localhost:8000 but this project is served locally via Herd at http://river-flasher.test. Storage::url() (used by Card::imageUrl()) builds absolute URLs from APP_URL, so card images 404/fail to load silently in the browser even though the file exists and the correct host serves it fine (verified: same path under river-flasher.test returns 200). Not a bug in the image feature itself — fix by setting APP_URL to the Herd domain, or via `herd link`/APP_URL override, if browser-visible images matter for local QA.
