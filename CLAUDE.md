# Coding Guidelines

Applies to the entire ez-php project — framework core, all modules, and the application template.

---

## Environment

- PHP **8.5**, Composer for dependency management
- All project based commands run **inside Docker** — never directly on the host

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
1. `sync_guidelines.php --check` — fails if any `CLAUDE.md` has drifted from this file
2. `check_test_classes.php` — fails on a duplicate test class name (all packages share the `Tests\` namespace, so a collision is a fatal error in the aggregated run, not a test failure)
3. `phpstan analyse` — static analysis, level 9, config: `phpstan.neon`
4. `php-cs-fixer fix` — auto-fixes style (`@PSR12` + `@PHP83Migration` + strict rules)
   *(Note: `@PHP85Migration` does not exist yet in php-cs-fixer; `@PHP83Migration` is the highest available and is used intentionally even though the project targets PHP 8.5)*
5. `phpunit` — all tests with coverage

Individual commands when needed:
```
composer analyse             # PHPStan only
composer cs                  # CS Fixer only
composer test                # PHPUnit only
composer guidelines:check    # CLAUDE.md drift only
composer test-classes:check  # duplicate test class names only
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
- Concrete classes are `final` — extend behavior through composition, not inheritance. Exception-hierarchy base classes (e.g. `EzPhpException`, `HttpException`, `CacheException`) are one carve-out, since they exist specifically to be extended. A documented template-method-style base class (e.g. `Mailable`, meant to be configured via constructor-time subclassing) is the other — the owning module's `CLAUDE.md` must record it under Design Decisions.

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

### 1 — Required files

Every module under `modules/<name>/` must have:

| File | Purpose |
|---|---|
| `composer.json` | package definition, deps, autoload |
| `phpstan.neon` | static analysis config, level 9 |
| `phpunit.xml` | test suite config |
| `.php-cs-fixer.php` | code style config |
| `.gitignore` | ignore `vendor/`, `.env`, cache |
| `.env.example` | environment variable defaults (copy to `.env` on first run) |
| `docker-compose.yml` | Docker Compose service definition (always `container_name: ez-php-<name>-app`) |
| `docker/app/Dockerfile` | module Docker image (`FROM au9500/php:8.5`) |
| `docker/app/container-start.sh` | container entrypoint: `composer install` → `sleep infinity` |
| `docker/app/php.ini` | PHP ini overrides (`memory_limit`, `display_errors`, `xdebug.mode`) |
| `.github/workflows/ci.yml` | standalone CI pipeline |
| `README.md` | public documentation |
| `tests/TestCase.php` | base test case for the module |
| `start.sh` | convenience script: copy `.env`, bring up Docker, wait for services, exec shell |
| `CLAUDE.md` | see section 2 below |

### 2 — CLAUDE.md structure

Every module `CLAUDE.md` must follow this exact structure:

1. **Full content of `CODING_GUIDELINES.md`, verbatim** — copy it as-is, do not summarize or shorten
2. A `---` separator
3. `# Package: ez-php/<name>` (or `# Directory: <name>` for non-package directories)
4. Module-specific section covering:
   - Source structure — file tree with one-line description per file
   - Key classes and their responsibilities
   - Design decisions and constraints
   - Testing approach and infrastructure requirements (MySQL, Redis, etc.)
   - What does **not** belong in this module

**Do not edit part 1 by hand.** It is generated from `CODING_GUIDELINES.md` by
`sync_guidelines.php` at the project root:

```
php sync_guidelines.php            # rewrite every out-of-sync CLAUDE.md
php sync_guidelines.php --check    # report drift, exit 1 if any (CI / pre-commit)
```

Edit `CODING_GUIDELINES.md`, then run the script — it replaces everything before the
`# Package:` / `# Directory:` / `# Project:` heading and preserves the hand-written
section below it byte-for-byte. Editing a single copy only creates drift; before this
script existed, all 40 copies had diverged.

### 3 — Scaffolding a new module

`make_module.php` at the project root writes the required-file set and the monorepo
wiring in one step, wrapping `docker-init` for the Docker subset:

```
composer module:make <name> -- --description="..."
php make_module.php <name> --description="..." --services=mysql,redis
```

`<name>` is the kebab-case package name; the namespace is derived as
`EzPhp\<PascalCase>` (each `-`-separated word upper-cased) unless `--namespace=`
overrides it. Existing exceptions the guess gets wrong: `bignum` → `BigNum`,
`dataloader` → `DataLoader`, `dotenv` → `Env`, `graphql` → `GraphQL`, `oauth` → `OAuth`,
`opcache` → `OPCache`, `swagger-ui` → `SwaggerUI`, `webauthn` → `WebAuthn` and
`websocket` → `WebSocket`; `websocket-client` → `WebsocketClient`, `websocket-tls` → `WebsocketTls`,
`webauthn-metadata` → `WebauthnMetadata` and `metrics-statsd` → `MetricsStatsd` are
intentional lower-case-word namespaces, and `testing-application` shares `EzPhp\Testing\`
with `testing`).

To bring in a module whose code already lives in its own repository instead of
generating a fresh skeleton, pass `--repo=` with a git URL:

```
php make_module.php <name> --repo=<git-url> [--namespace=Foo]
```

This runs `git submodule add <url> modules/<name>` instead of writing package
files, then applies the same monorepo wiring below. It is mutually exclusive
with `--services` and `--description` — a submodule brings its own Docker
scaffold (if any) and its own `composer.json` description. A minimal `CLAUDE.md`
stub is written only if the submodule doesn't already ship one, so
`composer guidelines:sync` has a `# Package:` heading to anchor part 1 against.

It writes `modules/<name>/` and registers the module in the four places the monorepo
needs it — root `composer.json` (`autoload.psr-4` **and** the shared
`autoload-dev` `Tests\` directory list), `phpstan.neon`, `phpunit.xml` (test suite
**and** coverage source), and `packages.sh` (alphabetical position) — in both
generated and `--repo` mode.

Two things stay manual on purpose:

- **`CLAUDE.md` part 1** — only the `# Package:` section is generated. Run
  `composer guidelines:sync` afterwards; baking a guidelines copy into the generator
  would recreate the drift the sync script exists to prevent.
- **The host-port table below** (`--services` only) — claim the "next free" row by
  editing the table in `CODING_GUIDELINES.md` (never in a `CLAUDE.md` copy) and run
  `composer guidelines:sync` in the same change. Editing it drifts every `CLAUDE.md`
  until the sync runs, which is why the generator only reminds you instead of doing
  it. Skipping the edit leaves "next free" stale, so the next module collides.

### 4 — Docker scaffold

Run from the new module root (requires `"ez-php/docker": "^2.0"` in `require-dev`):

```
vendor/bin/docker-init
```

This copies `Dockerfile`, `docker-compose.yml`, `.env.example`, `start.sh`, and `docker/` into the module, replacing `{{MODULE_NAME}}` placeholders. Existing files are never overwritten.

Pass `--services` to merge MySQL/Redis/Meilisearch service definitions directly into `docker-compose.yml` and uncomment the matching sections in `.env.example`, instead of adapting them by hand afterward:

```
vendor/bin/docker-init --services=mysql
vendor/bin/docker-init --services=redis
vendor/bin/docker-init --services=meilisearch
vendor/bin/docker-init --services=mysql,redis
```

Pass `--extensions` to merge PHP extension install blocks (apt packages plus `docker-php-ext-install`/`pecl` lines) directly into `docker/app/Dockerfile`, instead of hand-editing it afterward — supported extensions: `bcmath`, `gmp`, `gd`, `imagick`:

```
vendor/bin/docker-init --extensions=gmp,bcmath
vendor/bin/docker-init --extensions=gd,imagick
```

When run from a module directory inside this monorepo, any requested extension not already present is also merged into the shared root `docker/app/Dockerfile` — the container `composer full` at the root actually runs against, distinct from the module's own standalone image.

After scaffolding:

1. Adapt `docker-compose.yml` — add or remove services (MySQL, Redis, Meilisearch) as needed
2. Adapt `.env.example` — fill in connection defaults matching the services above
3. Assign a unique host port for each exposed service (see table below)

**Allocated host ports:**

| Package | `DB_HOST_PORT` (MySQL) | Redis host port | `MEILISEARCH_PORT` |
|---|---|---|---|
| root (`ez-php-project`) | 3306 | 6379 (`REDIS_PORT`) | 7700 |
| `ez-php/framework` | 3307 | — | — |
| `ez-php/` (application template) | 3308 | 6383 (`REDIS_PORT`) | — |
| `ez-php/orm` | 3309 | — | — |
| `ez-php/cache` | — | 6380 (`REDIS_HOST_PORT`) | — |
| `ez-php/queue` | 3310 | 6381 (`REDIS_HOST_PORT`) | — |
| `ez-php/rate-limiter` | — | 6382 (`REDIS_HOST_PORT`) | — |
| `ez-php/search` | — | — | 7701 |
| `ez-php/event-store` | 3311 | — | — |
| **next free** | **3312** | **6384** | **7702** |

Only set a port for services the module actually uses. Modules without external services need no port config.

> The `MEILISEARCH_PORT` column is the **host** port. Inside a Compose network the service is always reachable at `http://meilisearch:7700` regardless of the host mapping — only publish-side ports need to be unique.

> The "Redis host port" column is likewise the **host**-published port. `ez-php/cache`, `ez-php/queue`, and `ez-php/rate-limiter` map it through a separate `REDIS_HOST_PORT` env var in `docker-compose.yml`, keeping `REDIS_PORT` fixed at `6379` for in-container connections (the app container always reaches Redis at `redis:6379` over the Compose network, regardless of the host mapping) — the root project and the `ez-php/` application template are the two exceptions, since both have no host/container split and use `REDIS_PORT` for both (the template's other in-container Redis settings — `CACHE_REDIS_PORT`, `QUEUE_REDIS_PORT`, `RATE_LIMITER_REDIS_PORT` — stay fixed at `6379` regardless, same as every other module).

> This table tracks only MySQL, Redis, and Meilisearch ports — the three services shared across multiple modules where a collision is otherwise easy to introduce. Mailpit is the one other service with published host ports: SMTP `1025` and web UI `8025`. `ez-php/mail` maps them through `MAILPIT_SMTP_HOST_PORT`/`MAILPIT_API_HOST_PORT` in `modules/mail/docker-compose.yml` (mirroring the `*_HOST_PORT` pattern above, documented in `modules/mail/.env.example`); the root project and the `ez-php/` template each run their own Mailpit on the same defaults (`MAIL_PORT`/`MAIL_WEB_PORT`), so **these three stacks cannot run at the same time** without overriding those variables. It isn't a table column because no module beyond those three runs Mailpit — but a new module adding its own single-use service's ports should likewise parameterize them and document the defaults in its own `.env.example` rather than adding a column here.

### 5 — Monorepo scripts

`packages.sh` at the project root is the **central package registry**. Both `push_all.sh` and `update_all.sh` source it — the package list lives in exactly one place.

When adding a new module, add `"$ROOT/modules/<name>"` to the `PACKAGES` array in `packages.sh` in **alphabetical order** among the other `modules/*` entries (before `framework`, `ez-php`, and the root entry at the end).

---

# Package: ez-php/http

HTTP value objects and I/O helpers — `Request`, `Response`, `RequestFactory`, `ResponseEmitter`.

This package is a **zero-framework-dependency** standalone library. It has no knowledge of the Application, Container, Router, or any other ez-php package. The framework uses these types directly — they are the shared HTTP vocabulary for the entire monorepo.

---

## Source Structure

```
src/
├── RequestInterface.php     — Contract for the Request value object
├── Request.php              — Immutable HTTP request value object (final readonly class)
├── RequestFactory.php       — Builds a Request from PHP superglobals ($_SERVER, $_GET, $_POST, $_FILES, etc.)
├── ResponseInterface.php    — Body-neutral pipeline contract: status, headers, withHeader, cookies, withCookie, writeBody
├── Response.php             — Clone-based HTTP response value object with a string body
├── StreamedResponse.php     — Chunk-factory response; download() for resources, sse() for Server-Sent Events
├── HeaderValidator.php      — Rejects CR/LF/control characters in header names and values (shared by both responses)
├── ResponseFactory.php      — Static helpers: json(), redirect(), html(), text(), noContent()
├── ResponseEmitter.php      — Sends any ResponseInterface: headers via HeaderSenderInterface, body via OutputInterface
├── HeaderSenderInterface.php — Abstraction over header() calls; injectable for testing
├── NativeHeaderSender.php   — HeaderSenderInterface implementation using PHP's header() and http_response_code()
├── OutputInterface.php      — Abstraction over body output: write + flush, client-connected check, ignore_user_abort
├── NativeOutput.php         — OutputInterface implementation using echo, ob_flush()/flush(), connection_aborted()
├── ClientDisconnectedException.php — Thrown by the emitter when the client is gone; never reported
├── Sse/
│   └── SseEvent.php         — Single Server-Sent Events frame: data, event, id, retry + toString()
├── Cookie.php               — Immutable value object for Set-Cookie attributes; toHeaderValue()
└── UploadedFile.php         — Wraps a $_FILES entry: isValid(), moveTo(), clientFilename(), clientMimeType(), size()

tests/
├── TestCase.php                    — Base PHPUnit test case
├── Http/RequestTest.php            — Covers Request: all accessors, withParams, withMethod, file()
├── Http/RequestFactoryTest.php     — Covers RequestFactory: superglobal parsing, header extraction, files
├── Http/ResponseTest.php           — Covers Response: status, body, withHeader, headers, writeBody
├── Http/StreamedResponseTest.php   — Covers StreamedResponse: chunk order, factory re-read, onError frames, disconnect, download()
├── Http/StreamedResponseSseTest.php — Covers StreamedResponse::sse(): headers, frames, generic error frame
├── Http/ResponseEmitterTest.php    — Covers ResponseEmitter via SpyHeaderSender + RecordingOutput: headers, cookies, chunks, disconnect, stream errors
├── Http/HeaderValidatorTest.php    — Covers HeaderValidator: control characters in names and values
├── Http/NativeOutputTest.php       — Covers NativeOutput: CLI connection state
├── Http/Sse/SseEventTest.php       — Covers SseEvent: getters, toString formatting, multi-line data
├── Http/ResponseFactoryTest.php    — Covers ResponseFactory: json, redirect, html, text, noContent
├── Http/CookieTest.php             — Covers Cookie: all attributes, toHeaderValue() format
└── Http/UploadedFileTest.php       — Covers UploadedFile: isValid(), moveTo(), accessors
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
| `all()` | `array<string, mixed>` | Query + body merged; body wins on collision |
| `has(key)` | `bool` | Key present in query **or** body (`array_key_exists`; null counts as present) |
| `hasQuery(key)` | `bool` | Key present in query string only |
| `hasInput(key)` | `bool` | Key present in request body only |
| `header(key, default)` | `mixed` | Headers (keys always lowercase) |
| `cookie(key, default)` | `mixed` | `$_COOKIE` equivalent |
| `server(key, default)` | `mixed` | `$_SERVER` equivalent |
| `rawBody()` | `string` | Raw `php://input` content |
| `param(key, default)` | `mixed` | URL route parameters (e.g. `{id}`) set by the router |
| `ip(trustedProxies[])` | `string` | Client IP; reads `X-Forwarded-For` only when `REMOTE_ADDR` is a trusted proxy, walking the chain from the right and returning the first untrusted hop (the leftmost entry is client-controlled) |

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

Sends any `ResponseInterface` to the client: status, headers and cookies through `HeaderSenderInterface`, then `ignore_user_abort(true)` and `$response->writeBody($write)` through `OutputInterface`. `$write` flushes each chunk and throws `ClientDisconnectedException` once the client is gone, which ends the body quietly.

`emit(ResponseInterface $response, ?Closure $onStreamError = null)` — an exception raised while the body is written happens after headers were sent, so it cannot become an error page; it is passed to `$onStreamError` (the framework reports it) or rethrown when no callback is given.

---

### StreamedResponse (`src/StreamedResponse.php`)

Implements `ResponseInterface` with a `Closure(): iterable<string>` chunk factory and an optional `onError` hook producing error frames. `download()` streams a resource with RFC 6266 `Content-Disposition`; `sse()` streams `SseEvent`s with the event-stream headers and a generic `event: error` frame on failure.

---

## Design Decisions and Constraints

- **`Request` is `final readonly`** — Immutability is enforced by the language. Route parameters and method overrides are applied by returning new instances via `withParams()` / `withMethod()`, preserving the original object throughout the middleware chain.
- **`Response` uses clone-based withers** — PHP's `readonly` class feature prevents post-construction mutation, but `header()` addition is a natural part of building a response in middleware. Clone-based withers keep the API clean without requiring a builder pattern.
- **No PSR-7** — PSR-7 `MessageInterface` brings significant complexity (streams, URI objects, multiple `withXxx` methods). This package intentionally stays simple. If PSR-7 compatibility is required, adapt at the application boundary.
- **`Request` header keys normalized to lowercase** — HTTP headers are case-insensitive (RFC 7230). `Request::header()` lowercases on read, eliminating case bugs for inbound headers without requiring normalization at write time (the raw superglobal key casing is never under application control anyway). This normalization is intentionally scoped to `Request` only: `Response::withHeader()`/`headers()` preserve the exact key casing the caller supplies (e.g. `'Content-Type'`), since callers choose that casing deliberately for outbound headers and `ResponseEmitter` sends it as-is — case-insensitive per RFC 7230, so this is not a correctness issue, just an intentional asymmetry between the two classes.
- **`RequestFactory` is a static class** — There is no reason to inject it; it reads from PHP globals which are process-global anyway. Static methods make the intent clear and avoid pointless instantiation.
- **Responses write their own body (emit strategy)** — `ResponseInterface::writeBody(Closure $write)` instead of `body(): string` on the interface. The emitter never branches on the response type, so a new response type needs no emitter change, and a string body and a stream are emitted the same way. Only code that genuinely needs the string (e.g. a toolbar injector) checks `instanceof Response`.
- **`StreamedResponse` takes a chunk *factory*, not an iterable** — a generator can be iterated once; a factory yields a fresh iterator per `writeBody()`, so tests can read the body and clones made by withers never share a half-consumed generator. `download()` is the documented exception: a resource is single-use.
- **Headers go out before the first chunk** — every exception inside a chunk generator is therefore a mid-stream failure, even one before the first chunk. Checks belong in the controller before it returns the `StreamedResponse`.
- **Body output behind `OutputInterface`** — mirrors `HeaderSenderInterface`, so flushing and client disconnects are testable without an HTTP context.
- **`ResponseEmitter` is a regular class** — Unlike `RequestFactory`, it may need to be replaced in tests or extended (e.g. streaming emitter). Keeping it instantiable allows binding a custom emitter in the container.
- **No JSON/redirect helpers** — `Response::json()`, `Response::redirect()`, etc. are application-layer conveniences. They do not belong in the value object itself.
- **Zero framework dependencies** — This package must remain usable standalone. Do not import Application, Container, Router, or any other framework class.

---

## Testing Approach

- **No external infrastructure required** — All tests are purely in-process.
- **`RequestFactory` tests** — Populate `$_SERVER`, `$_GET`, `$_POST`, `$_COOKIE` directly in the test, then restore in `tearDown`. Do not rely on `php://input` in unit tests — test its absence (empty string fallback).
- **`ResponseEmitter` is unit-tested through fakes** — `SpyHeaderSender` and `RecordingOutput` (both in `ResponseEmitterTest.php`) replace the native SAPI calls. `NativeHeaderSender` and `NativeOutput::write()` stay untested: `header()` cannot be asserted in CLI, and `ob_flush()` pushes output past PHPUnit's capture buffer.
- **`#[UsesClass]` required** — PHPUnit is configured with `beStrictAboutCoverageMetadata=true`. Declare indirectly used classes with `#[UsesClass]`.

---

## What Does NOT Belong Here

| Concern | Where it belongs |
|---|---|
| Request validation | `ez-php/validation` |
| Session handling | Application session middleware |
| CORS headers | `ez-php/framework` (`CorsMiddleware`) |
| HTTP client (outgoing requests) | `ez-php/http-client` |
| PSR-7 / PSR-15 compatibility | Application-level adapter if needed |
| Multipart / file upload handling | Application layer |
