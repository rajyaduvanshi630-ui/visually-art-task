<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RemovePlaceholderFromPathTest extends TestCase
{
    public static function redirectCases(): array
    {
        return [
            'email example' => ['/products/58-box-formats%7Bignore%7D?gclid=abc', '/products/58-box-formats?gclid=abc'],
            'literal braces' => ['/products/58{ignore}?x=1', '/products/58?x=1'],
            'lowercase encoding' => ['/p%7bignore%7d', '/p'],
            'multiple placeholders' => ['/p{ignore}%7Btracking_id%7D', '/p'],
            'mixed braces' => ['/p{ignore%7D', '/p'],
            'query bytes' => ['/p{ignore}?b=2&a=hello%20world&a=hello+world&x=%2f&empty=', '/p?b=2&a=hello%20world&a=hello+world&x=%2f&empty='],
            'query untouched' => ['/p{ignore}?token=%7Bignore%7D', '/p?token=%7Bignore%7D'],
            'empty query marker' => ['/p{ignore}?', '/p?'],
            'root fallback' => ['/{ignore}', '/'],
            'local redirect' => ['/{ignore}/example.com/path', '/example.com/path'],
            'other path encoding' => ['/p%20x%2fy{ignore}?q=%2B', '/p%20x%2fy?q=%2B'],
        ];
    }

   #[DataProvider('redirectCases')]
public function test_redirect_preserves_the_exact_target(
    string $uri,
    string $target
): void {
    $request = \Illuminate\Http\Request::create($uri, 'GET');

    // Set AFTER request creation to preserve the trailing question mark.
    $request->server->set('REQUEST_URI', $uri);

    $kernel = $this->app->make(
        \Illuminate\Contracts\Http\Kernel::class
    );

    $response = $kernel->handle($request);

    try {
        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame(
            $target,
            $response->headers->get('Location')
        );
    } finally {
        $kernel->terminate($request, $response);
    }
}

    public function test_query_only_placeholder_does_not_redirect(): void
    {
        Route::get('/plain', fn () => response('OK'));
        $uri = '/plain?q=%7Bignore%7D';
        $this->call('GET', $uri, [], [], [], ['REQUEST_URI' => $uri])
            ->assertOk()->assertContent('OK')->assertHeaderMissing('Location');
    }

    public function test_post_request_is_not_redirected(): void
    {
        Route::post('/submit{ignore}', fn () => response('created', 201));
        $uri = '/submit{ignore}';
        $this->call('POST', $uri, [], [], [], ['REQUEST_URI' => $uri])
            ->assertStatus(201)->assertHeaderMissing('Location');
    }
}
