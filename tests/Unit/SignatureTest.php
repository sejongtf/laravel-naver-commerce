<?php

use Sejongtf\LaravelNaverCommerce\Auth\Signature;
use Sejongtf\LaravelNaverCommerce\Exceptions\AuthenticationException;

it('generates a base64-encoded bcrypt signature matching the documented algorithm', function () {
    $clientId = 'aaaabbbbcccc';
    $secret = '$2a$10$abcdefghijklmnopqrstuv';
    $timestamp = 1643961623299;

    $signature = Signature::generate($clientId, $secret, $timestamp);

    expect($signature)->toBe(base64_encode(crypt("{$clientId}_{$timestamp}", $secret)));
    // bcrypt 는 salt 의 마지막 문자를 정규화하므로(v → u) 앞 21자만 비교한다.
    expect(base64_decode($signature))->toStartWith('$2a$10$abcdefghijklmnopqrstu');
});

it('is deterministic for the same input', function () {
    $a = Signature::generate('client', '$2a$10$abcdefghijklmnopqrstuv', 1700000000000);
    $b = Signature::generate('client', '$2a$10$abcdefghijklmnopqrstuv', 1700000000000);

    expect($a)->toBe($b);
});

it('throws when the client secret is not a bcrypt salt', function () {
    Signature::generate('client', 'not-a-bcrypt-salt', 1700000000000);
})->throws(AuthenticationException::class);

it('throws when credentials are empty', function () {
    Signature::generate('', '', 1700000000000);
})->throws(AuthenticationException::class);

it('produces a millisecond timestamp', function () {
    $ts = Signature::timestamp();

    expect($ts)->toBeGreaterThan(1_600_000_000_000)->toBeLessThan(4_000_000_000_000);
});
