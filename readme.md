# AS QS Relay

Author: AlphaSys  
Version: 0.1.3  
Status: MVP

## Purpose

AS QS Relay maintains a first-party JSON cookie containing a visitor's tracked query-string touchpoints over time. It can progressively capture configured query-string keys from visited URLs and expose the parsed relay payload to front-end JavaScript.

## Key Features

- Captures configurable query-string keys from page URLs.
- Defaults to `utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content`, and `utm_id`.
- Includes a Settings > QS Relay options page with a repeater for tracked query-string keys.
- Stores a compact, readable timestamp-indexed text map in the `as_qs_relay` cookie.
- Exposes the parsed payload as `window.ASQR_QS_RELAY`.
- Supports query-string commands for resetting or hydrating from Independent Analytics.
- Includes a GitHub release updater with a plugin-row "Check for updates" link.

## Query String Commands

- Reset relay cookie: `?asqr_reset=1`
- Hydrate from current Independent Analytics session: `?asqr_from_ia=1`
- Alternate command form: `?as_qs_relay=reset` or `?as_qs_relay=ia`

## Folder Structure

```text
as-qs-relay/
├── as-qs-relay.php
├── functions/
│   ├── admin.php
│   ├── assets.php
│   ├── helpers.php
│   ├── rest.php
│   ├── setup.php
│   └── updater.php
├── scripts/
│   └── as-qs-relay.js
├── styles/
│   └── as-qs-relay.css
└── templates/
    └── .gitkeep
```

## Important Notes

- The cookie is intentionally readable by JavaScript.
- Cookie storage is readable text, for example `2026-07-20T03:24:03+00:00{utm_source:bbb}|2026-07-20T03:24:07+00:00{utm_source:ccc}`.
- Internal plugin access converts the cookie text back into a structured timestamp-indexed payload before processing.
- The relay stores up to 20 timestamped captures and trims older entries if the cookie approaches browser size limits.
- Independent Analytics hydration requires Independent Analytics to be active and able to resolve the current visitor/session. Direct IA hydration can populate UTM fields when those keys are tracked; URL fallback can populate any tracked keys present on the landing URL.
- No custom database tables are created.

## Future Considerations

- Optional admin diagnostics for the current cookie payload.
- Optional shortcode or REST endpoint for controlled server-side rendering.
