# ez-php/http

HTTP message objects for PHP — immutable `Request`, `Response`, `RequestFactory`, `ResponseFactory`, `StreamedResponse`, `ResponseEmitter`, `Cookie`, and `UploadedFile`. Zero dependencies.

[![CI](https://github.com/ez-php/http/actions/workflows/ci.yml/badge.svg)](https://github.com/ez-php/http/actions/workflows/ci.yml)

## Requirements

- PHP 8.5+

## Installation

```bash
composer require ez-php/http
```

## Usage

### Request

```php
use EzPhp\Http\Request;
use EzPhp\Http\RequestFactory;

// Create from PHP globals (web context)
$request = RequestFactory::createFromGlobals();

echo $request->method();           // GET, POST, ...
echo $request->uri();              // /users/42
echo $request->query('page');      // query string value
echo $request->input('name');      // POST body value
echo $request->header('accept');   // request header (case-insensitive)
echo $request->cookie('session');  // cookie value
echo $request->param('id');        // route parameter (set by router)
echo $request->rawBody();          // raw request body string
echo $request->ip();               // client IP (X-Forwarded-For aware)

// Uploaded files
$file = $request->file('avatar');  // returns UploadedFile|null

// Immutable — returns a new instance
$request = $request->withMethod('PATCH');
$request = $request->withParams(['id' => '42']);
```

### Response

```php
use EzPhp\Http\Response;
use EzPhp\Http\ResponseEmitter;
use EzPhp\Http\ResponseFactory;

$response = new Response(body: 'Hello', status: 200);

// Fluent header chaining — each call returns a new instance
$response = $response
    ->withHeader('Content-Type', 'application/json')
    ->withHeader('X-Request-Id', 'abc123');

// Factory helpers
$json     = ResponseFactory::json(['id' => 1]);
$redirect = ResponseFactory::redirect('/dashboard');
$html     = ResponseFactory::html('<h1>Hello</h1>');

// Send to the browser
(new ResponseEmitter())->emit($response);
```

`emit()` accepts any `ResponseInterface`, including `StreamedResponse`. Inside an ez-php
application use `$app->send($request, $response)` instead: it reports stream failures and
runs terminable middleware after the body has been sent.

### Streamed responses

```php
use EzPhp\Http\Sse\SseEvent;
use EzPhp\Http\StreamedResponse;

// File download — the resource is read in chunks and closed afterwards
$download = StreamedResponse::download($storage->getStream('reports/q3.csv'), 'q3.csv', $size, 'text/csv');

// Server-Sent Events
$events = StreamedResponse::sse(function (): \Generator {
    yield new SseEvent(json_encode(['progress' => 50], JSON_THROW_ON_ERROR), 'progress');
});
```

Headers are sent before the first chunk, so an exception inside the generator can no longer become
an error page. Check authorisation and inputs **before** returning a `StreamedResponse`.

### Cookie

```php
use EzPhp\Http\Cookie;

$cookie = new Cookie(
    name:     'session',
    value:    'abc123',
    expires:  time() + 3600,
    path:     '/',
    secure:   true,
    httpOnly: true,
    sameSite: 'Lax',
);

$response = $response->withHeader('Set-Cookie', $cookie->toHeaderValue());
```

### UploadedFile

```php
use EzPhp\Http\UploadedFile;

$file = $request->file('avatar'); // returns UploadedFile|null

if ($file !== null && $file->isValid()) {
    $file->moveTo('/var/www/uploads/' . $file->clientFilename());
    echo $file->clientMimeType(); // 'image/jpeg'
    echo $file->size();           // bytes
}
```

## Classes

| Class | Description |
|---|---|
| `RequestInterface` | Contract for the `Request` value object |
| `Request` | Immutable HTTP request value object (`final readonly class`) |
| `RequestFactory` | Builds a `Request` from PHP superglobals |
| `Response` | HTTP response value object; clone-based `withHeader()` |
| `ResponseFactory` | Static helpers: `json()`, `redirect()`, `html()`, `text()`, `noContent()` |
| `ResponseEmitter` | Sends any `ResponseInterface` — headers via `HeaderSenderInterface`, body via `OutputInterface`, chunk by chunk for streams |
| `HeaderSenderInterface` | Abstraction over `header()` calls (injectable for testing) |
| `NativeHeaderSender` | Default `HeaderSenderInterface` implementation using PHP's `header()` |
| `Cookie` | Immutable value object for `Set-Cookie` attributes; `toHeaderValue()` produces the header string |
| `UploadedFile` | Wraps a `$_FILES` entry; `isValid()`, `moveTo()`, `clientFilename()`, `clientMimeType()`, `size()` |

## License

MIT — [Andreas Uretschnig](mailto:andreas.uretschnig@gmail.com)
