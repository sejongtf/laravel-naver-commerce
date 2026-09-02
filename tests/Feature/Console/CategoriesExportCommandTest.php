<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Sejongtf\LaravelNaverCommerce\Tests\TestCase;

/** @var TestCase $this */

const CATEGORIES = [
    ['id' => '50000000', 'name' => '패션의류', 'last' => false],
    ['id' => '50000803', 'name' => '남성의류', 'last' => true],
];

it('prints categories to stdout when no path is given', function () {
    $this->fakeApi([$this->url('/v1/categories*') => Http::response(CATEGORIES)]);

    [$code, $output] = runArtisan('naver-commerce:categories:export');

    expect($code)->toBe(0)
        ->and(json_decode($output, true))->toBe(CATEGORIES)
        ->and($output)->toContain('패션의류'); // JSON_UNESCAPED_UNICODE

    $this->assertApiSent(fn (Request $request) => $request->url() === $this->url('/v1/categories'));
});

it('writes leaf categories to a file with --last, creating directories', function () {
    $dir = sys_get_temp_dir().'/nc-export-'.uniqid();
    $path = $dir.'/nested/categories.json';

    $this->fakeApi([$this->url('/v1/categories*') => Http::response([CATEGORIES[1]])]);

    [$code, $output] = runArtisan('naver-commerce:categories:export', ['path' => $path, '--last' => true]);

    expect($code)->toBe(0)
        ->and($output)->toContain('Exported 1 categories')
        ->and(json_decode(file_get_contents($path), true))->toBe([CATEGORIES[1]]);

    $this->assertApiSent(fn (Request $request) => $request->url() === $this->url('/v1/categories?last=true'));

    unlink($path);
    rmdir(dirname($path));
    rmdir($dir);
});

it('fails with the API error', function () {
    $this->fakeApi([$this->url('/v1/categories*') => Http::response(['code' => 'GW.AUTHZ', 'message' => 'forbidden'], 403)]);

    [$code, $output] = runArtisan('naver-commerce:categories:export');

    expect($code)->toBe(1)
        ->and($output)->toContain('GW.AUTHZ');
});
