# Coding Guidelines

Applies to the entire ez-php project — framework core, all modules, and the application template.

---

## Environment

- PHP **8.5**, Composer for dependency management
- All commands run **inside Docker** — never directly on the host

```
docker compose exec app <command>
```

Container name: `ez-php-app`, service name: `app`.

---

## Quality Suite

Run after every change:

```
docker compose exec app composer full
```

Executes in order:
1. `phpstan analyse` — static analysis, level 9, config: `phpstan.neon`
2. `php-cs-fixer fix` — auto-fixes style (`@PSR12` + `@PHP83Migration` + strict rules)
3. `phpunit` — all tests with coverage

Individual commands when needed:
```
composer analyse   # PHPStan only
composer cs        # CS Fixer only
composer test      # PHPUnit only
```

**PHPStan:** never suppress with `@phpstan-ignore-line` — always fix the root cause.

---

## Coding Standards

- `declare(strict_types=1)` at the top of every PHP file
- Typed properties, parameters, and return values — avoid `mixed`
- PHPDoc on every class and public method
- One responsibility per class — keep classes small and focused
- Constructor injection — no service locator pattern
- No global state unless intentional and documented

**Naming:**

| Thing | Convention |
|---|---|
| Classes / Interfaces | `PascalCase` |
| Methods / variables | `camelCase` |
| Constants | `UPPER_CASE` |
| Files | Match class name exactly |

**Principles:** SOLID · KISS · DRY · YAGNI

---

## Workflow & Behavior

- Write tests **before or alongside** production code (test-first)
- Read and understand the relevant code before making any changes
- Modify the minimal number of files necessary
- Keep implementations small — if it feels big, it likely belongs in a separate module
- No hidden magic — everything must be explicit and traceable
- No large abstractions without clear necessity
- No heavy dependencies — check if PHP stdlib suffices first
- Respect module boundaries — don't reach across packages
- Keep the framework core small — what belongs in a module stays there
- Document architectural reasoning for non-obvious design decisions
- Do not change public APIs unless necessary
- Prefer composition over inheritance — no premature abstractions

---

## New Modules & CLAUDE.md Files

When creating a new module or `CLAUDE.md` anywhere in this repository:

**CLAUDE.md structure:**
- Start with the full content of `CODING_GUIDELINES.md`, verbatim
- Then add `---` followed by `# Package: ezphp/<name>` (or `# Directory: <name>`)
- Module-specific section must cover:
  - Source structure (file tree with one-line descriptions per file)
  - Key classes and their responsibilities
  - Design decisions and constraints
  - Testing approach and any infrastructure requirements (e.g. needs MySQL, Redis)
  - What does **not** belong in this module

**Each module needs its own:**
`composer.json` · `phpstan.neon` · `phpunit.xml` · `.php-cs-fixer.php` · `.gitignore` · `.github/workflows/ci.yml` · `README.md` · `tests/TestCase.php`

**Docker setup:** copy `docker-compose.yml`, `docker/`, `.env.example` and `start.sh` from the repository root and adapt them for the module (service names, ports, required services). Use a unique `DB_PORT` in `.env.example` that is not used by any other package — increment by one per package starting with `3306` (root).
---

# Package: ezphp/http

HTTP value objects and I/O helpers — `Request`, `Response`, `RequestFactory`, `ResponseEmitter`.

This package is a **zero-framework-dependency** standalone library. It has no knowledge of the Application, Container, Router, or any other ez-php package. The framework uses these types directly — they are the shared HTTP vocabulary for the entire monorepo.

---

## Source Structure

```
src/
├── Request.php          — Immutable HTTP request value object (final readonly class)
├── Response.php         — Mutable-via-clone HTTP response value object
├── RequestFactory.php   — Builds a Request from PHP superglobals ($_SERVER, $_GET, $_POST, etc.)
└── ResponseEmitter.php  — Sends a Response to the client via http_response_code() and header()

tests/
├── TestCase.php                    — Base PHPUnit test case
├── Http/RequestTest.php            — Covers Request: all accessors, withParams, withMethod
├── Http/RequestFactoryTest.php     — Covers RequestFactory: superglobal parsing, header extraction
└── Http/ResponseTest.php           — Covers Response: status, body, withHeader, headers
```

---

## Key Classes and Responsibilities

### Request (`src/Request.php`)

**`final readonly class`** — fully immutable value object. All properties are set in the constructor and cannot change.

| Method | Returns | Source |
|---|---|---|
| `method()` | `string` | HTTP verb (uppercase), e.g. `'GET'`, `'POST'` |
| `uri()` | `string` | Full request URI including query string |
| `query(key, default)` | `mixed` | `$_GET` equivalent |
| `input(key, default)` | `mixed` | `$_POST` / parsed body equivalent |
| `header(key, default)` | `mixed` | Headers (keys always lowercase) |
| `cookie(key, default)` | `mixed` | `$_COOKIE` equivalent |
| `server(key, default)` | `mixed` | `$_SERVER` equivalent |
| `rawBody()` | `string` | Raw `php://input` content |
| `param(key, default)` | `mixed` | URL route parameters (e.g. `{id}`) set by the router |

**Immutable wither methods** — return a new `Request` instance:
- `withParams(array<string, string>)` — used by the router to attach extracted URL parameters
- `withMethod(string)` — used for HTTP method override

**Header keys are always lowercased.** `header('Authorization')` and `header('authorization')` are equivalent.

---

### Response (`src/Response.php`)

Value object representing an outgoing HTTP response. Headers are applied via `withHeader()` which returns a clone.

| Method | Behaviour |
|---|---|
| `status(): int` | HTTP status code (default `200`) |
| `body(): string` | Response body (default `''`) |
| `withHeader(name, value): self` | Returns a clone with the header added/replaced |
| `headers(): array<string, string>` | All set headers |

**`Response` is not `readonly`** — `withHeader()` uses `clone` internally, which requires mutable properties. This is the only exception to the immutability preference in this package.

---

### RequestFactory (`src/RequestFactory.php`)

Static factory. Single responsibility: build a `Request` from PHP superglobals.

**`createFromGlobals(): Request`**

- `REQUEST_METHOD` → `method` (defaults to `'GET'` if absent)
- `REQUEST_URI` → `uri` (defaults to `'/'` if absent)
- `$_GET` → `query`
- `$_POST` → `body`
- `$_COOKIE` → `cookies`
- `$_SERVER` → `server`
- `php://input` → `rawBody` (empty string on read failure)
- Headers extracted from `$_SERVER`:
  - `HTTP_*` keys → lowercased, `HTTP_` prefix stripped, `_` → `-`
  - `CONTENT_TYPE` → `content-type`
  - `CONTENT_LENGTH` → `content-length`

`params` is always `[]` — route parameters are attached later by the router via `withParams()`.

---

### ResponseEmitter (`src/ResponseEmitter.php`)

Sends a `Response` to the client. Must be called only once per request, after the response is fully built.

```php
$emitter->emit($response);
// equivalent to:
http_response_code($response->status());
foreach ($response->headers() as $name => $value) {
    header("$name: $value");
}
echo $response->body();
```

**Cannot be tested with headers in a CLI/PHPUnit context** — `http_response_code()` and `header()` throw warnings or silently fail when no HTTP context exists. Test the `Response` value object directly; test the emitter via integration/acceptance tests only.

---

## Design Decisions and Constraints

- **`Request` is `final readonly`** — Immutability is enforced by the language. Route parameters and method overrides are applied by returning new instances via `withParams()` / `withMethod()`, preserving the original object throughout the middleware chain.
- **`Response` uses clone-based withers** — PHP's `readonly` class feature prevents post-construction mutation, but `header()` addition is a natural part of building a response in middleware. Clone-based withers keep the API clean without requiring a builder pattern.
- **No PSR-7** — PSR-7 `MessageInterface` brings significant complexity (streams, URI objects, multiple `withXxx` methods). This package intentionally stays simple. If PSR-7 compatibility is required, adapt at the application boundary.
- **Header keys normalized to lowercase** — HTTP headers are case-insensitive (RFC 7230). Lowercasing on read eliminates case bugs without requiring normalization at write time.
- **`RequestFactory` is a static class** — There is no reason to inject it; it reads from PHP globals which are process-global anyway. Static methods make the intent clear and avoid pointless instantiation.
- **`ResponseEmitter` is a regular class** — Unlike `RequestFactory`, it may need to be replaced in tests or extended (e.g. streaming emitter). Keeping it instantiable allows binding a custom emitter in the container.
- **No JSON/redirect helpers** — `Response::json()`, `Response::redirect()`, etc. are application-layer conveniences. They do not belong in the value object itself.
- **Zero framework dependencies** — This package must remain usable standalone. Do not import Application, Container, Router, or any other framework class.

---

## Testing Approach

- **No external infrastructure required** — All tests are purely in-process.
- **`RequestFactory` tests** — Populate `$_SERVER`, `$_GET`, `$_POST`, `$_COOKIE` directly in the test, then restore in `tearDown`. Do not rely on `php://input` in unit tests — test its absence (empty string fallback).
- **`ResponseEmitter` is not unit-tested here** — `header()` and `http_response_code()` cannot be asserted in a CLI test context. Cover emitter behaviour via integration/acceptance tests in the application.
- **`#[UsesClass]` required** — PHPUnit is configured with `beStrictAboutCoverageMetadata=true`. Declare indirectly used classes with `#[UsesClass]`.

---

## What Does NOT Belong Here

| Concern | Where it belongs |
|---|---|
| JSON response helper (`Response::json()`) | Application layer or a response helper trait |
| Redirect response helper | Application layer |
| File/stream response | Application layer or a future `StreamResponse` |
| Request validation | `ezphp/validation` |
| Session handling | Application session middleware |
| CORS headers | `ezphp/framework` (`CorsMiddleware`) |
| HTTP client (outgoing requests) | `ezphp/http-client` |
| PSR-7 / PSR-15 compatibility | Application-level adapter if needed |
| Multipart / file upload handling | Application layer |
