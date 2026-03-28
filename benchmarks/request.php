<?php

declare(strict_types=1);

/**
 * Performance benchmark for EzPhp\Http\Request.
 *
 * Measures the overhead of constructing a Request value object and
 * accessing its most common accessor methods.
 *
 * Exits with code 1 if the per-request time exceeds the defined threshold,
 * allowing CI to detect performance regressions automatically.
 *
 * Usage:
 *   php benchmarks/request.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use EzPhp\Http\Request;

const ITERATIONS = 5000;
const THRESHOLD_MS = 1.0; // per-request upper bound in milliseconds

// ── Benchmark ─────────────────────────────────────────────────────────────────

// Warm-up
$r = new Request(
    method: 'POST',
    uri: '/api/users?page=2&limit=25',
    query: ['page' => '2', 'limit' => '25'],
    body: ['name' => 'Alice', 'email' => 'alice@example.com'],
    cookies: [],
    server: ['REMOTE_ADDR' => '127.0.0.1'],
    headers: ['content-type' => 'application/json', 'accept' => 'application/json'],
    rawBody: '{"name":"Alice","email":"alice@example.com"}',
    params: [],
);
$r->method();
$r->uri();
$r->query('page');
$r->input('name');
$r->header('content-type');

$start = hrtime(true);

for ($i = 0; $i < ITERATIONS; $i++) {
    $req = new Request(
        method: 'POST',
        uri: '/api/users?page=2&limit=25',
        query: ['page' => '2', 'limit' => '25'],
        body: ['name' => 'Alice', 'email' => 'alice@example.com'],
        cookies: [],
        server: ['REMOTE_ADDR' => '127.0.0.1'],
        headers: ['content-type' => 'application/json', 'accept' => 'application/json'],
        rawBody: '{"name":"Alice","email":"alice@example.com"}',
        params: [],
    );
    $req->method();
    $req->uri();
    $req->query('page');
    $req->input('name');
    $req->header('content-type');
}

$end = hrtime(true);

$totalMs = ($end - $start) / 1_000_000;
$perRequest = $totalMs / ITERATIONS;

echo sprintf(
    "HTTP Request Benchmark\n" .
    "  Operations per iter  : construct + 5 accessors\n" .
    "  Iterations           : %d\n" .
    "  Total time           : %.2f ms\n" .
    "  Per iteration        : %.3f ms\n" .
    "  Threshold            : %.1f ms\n",
    ITERATIONS,
    $totalMs,
    $perRequest,
    THRESHOLD_MS,
);

if ($perRequest > THRESHOLD_MS) {
    echo sprintf(
        "FAIL: %.3f ms exceeds threshold of %.1f ms\n",
        $perRequest,
        THRESHOLD_MS,
    );
    exit(1);
}

echo "PASS\n";
exit(0);
