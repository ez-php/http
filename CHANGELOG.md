# Changelog

All notable changes to `ez-php/http` are documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## [v1.0.1] — 2026-03-25

### Changed
- Tightened all `ez-php/*` dependency constraints from `"*"` to `"^1.0"` for predictable resolution

---

## [v1.0.0] — 2026-03-24

### Added
- `Request` — immutable HTTP request value object; header keys normalized to lowercase; exposes method, URI, headers, query params, body, and uploaded files
- `Response` — HTTP response value object with status code, headers, and body; all mutating operations return a new instance (clone-based withers)
- `RequestFactory` — builds a `Request` from PHP superglobals (`$_SERVER`, `$_GET`, `$_POST`, `$_FILES`, `php://input`)
- `ResponseEmitter` — sends headers and body to the client; guards against double-emit in the same process
- Zero runtime dependencies — suitable as a standalone HTTP message layer
