# Changelog

All notable changes to AS QS Relay are recorded here.

## 0.1.2 - 2026-07-20

- Fixed second-load failures by using WordPress/PHP's normal cookie encoding instead of raw JSON cookie headers.
- Kept the simple timestamp-indexed JSON payload while avoiding the previous double-encoding path.

## 0.1.1 - 2026-07-20

- Changed the relay cookie to a simple timestamp-indexed JSON key/value map.
- Switched cookie writing to avoid double URL encoding.
- Added tolerant reading for old encoded relay cookie values.

## 0.1.0 - 2026-07-20

- Added progressive query-string capture into a first-party JSON cookie.
- Added an options-page repeater for tracked query-string keys.
- Added query-string commands to reset the relay cookie or hydrate it from the current Independent Analytics session.
- Added front-end JavaScript exposure through `window.ASQR_QS_RELAY`.
- Added GitHub release updater support.
