# ez-php/http

HTTP message objects for PHP — immutable `Request`, `Response`, `RequestFactory`, and `ResponseEmitter`. Zero dependencies.

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

// Immutable — returns a new instance
$request = $request->withMethod('PATCH');
$request = $request->withParams(['id' => '42']);
```

### Response

```php
use EzPhp\Http\Response;
use EzPhp\Http\ResponseEmitter;

$response = new Response(body: 'Hello', status: 200);

// Fluent header chaining — each call returns a new instance
$response = $response
    ->withHeader('Content-Type', 'application/json')
    ->withHeader('X-Request-Id', 'abc123');

// Send to the browser
(new ResponseEmitter())->emit($response);
```

## License

MIT — [Andreas Uretschnig](mailto:andreas.uretschnig@gmail.com)
