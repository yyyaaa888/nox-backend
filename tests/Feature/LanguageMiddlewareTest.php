<?php

use App\Http\Middleware\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

function invokeLanguageMiddleware(?string $acceptLanguage): string
{
    $request = Request::create('/');
    if ($acceptLanguage !== null) {
        $request->headers->set('accept-language', $acceptLanguage);
    }
    (new Language)->handle($request, function () {});

    return App::getLocale();
}

afterEach(function () {
    App::setLocale('zh_CN');
});

it('将支持的 accept-language 头映射到对应语言', function () {
    expect(invokeLanguageMiddleware('zh-CN'))->toBe('zh_CN');
    expect(invokeLanguageMiddleware('zh-TW'))->toBe('zh_TW');
    expect(invokeLanguageMiddleware('en'))->toBe('en');
    expect(invokeLanguageMiddleware('ja'))->toBe('ja');
    expect(invokeLanguageMiddleware('th'))->toBe('th');
    expect(invokeLanguageMiddleware('vi'))->toBe('vi');
});

it('不支持的或缺省语言头回退到默认语言', function () {
    expect(invokeLanguageMiddleware('unknown-lang'))->toBe('zh_CN');
    expect(invokeLanguageMiddleware(null))->toBe('zh_CN');
});
