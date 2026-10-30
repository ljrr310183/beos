<?php

it('answers the preflight of the configured frontend origin', function () {
    $response = $this->call('OPTIONS', '/api/products', [], [], [], [
        'HTTP_ORIGIN' => 'http://localhost:3000',
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
    ]);

    $response->assertNoContent();
    expect($response->headers->get('Access-Control-Allow-Origin'))->toBe('http://localhost:3000');
});

it('never echoes an origin that is not the configured one', function () {
    $response = $this->call('OPTIONS', '/api/products', [], [], [], [
        'HTTP_ORIGIN' => 'http://evil.test',
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
    ]);

    expect($response->headers->get('Access-Control-Allow-Origin'))->not->toBe('http://evil.test');
});

it('does not expose CORS headers on non api paths', function () {
    $response = $this->call('OPTIONS', '/up', [], [], [], [
        'HTTP_ORIGIN' => 'http://localhost:3000',
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
    ]);

    expect($response->headers->has('Access-Control-Allow-Origin'))->toBeFalse();
});
