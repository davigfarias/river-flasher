<?php

/**
 * Logs into the app through the real OTP flow, for browser tests. Each
 * digit is pressed into its own box by aria-label — the OTP widget
 * auto-advances focus via JS, so `type()`/`fill()` on the group doesn't
 * work, but a real per-box keypress does.
 */
function loginWithCode($page, string $code)
{
    foreach (str_split($code) as $position => $digit) {
        $page->keys('input[aria-label="Character '.($position + 1).' of '.mb_strlen($code).'"]', $digit);
    }

    return $page;
}
