<?php

use Illuminate\Support\Facades\App;

// 从 app/ 提取全部 __('literal') 非命名空间 key
function apiTranslationKeys(): array
{
    $src = '';
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path()));
    foreach ($it as $f) {
        if ($f->isFile() && $f->getExtension() === 'php') {
            $src .= file_get_contents($f->getPathname())."\n";
        }
    }
    $src = preg_replace('#/\*.*?\*/#s', '', $src);   // block comments
    $src = preg_replace('#^\s*//.*$#m', '', $src);   // full-line comments
    preg_match_all('/__\(\s*[\'"](.+?)[\'"]\s*[,)]/', $src, $m);
    $codeKeys = array_filter(array_unique($m[1]), fn ($k) => ! str_contains($k, '.'));
    $zh = json_decode(file_get_contents(lang_path('zh_CN.json')), true);

    return array_values(array_unique(array_merge($codeKeys, array_keys($zh))));
}

afterEach(function () {
    App::setLocale('zh_CN');
});

it('所有支持的语种均包含每个 API key 的翻译', function () {
    $locales = ['zh_CN', 'zh_TW', 'vi', 'ja', 'th', 'en'];
    $canonical = apiTranslationKeys();

    foreach ($locales as $locale) {
        $file = lang_path("$locale.json");
        expect(file_exists($file))->toBeTrue("$file 不存在");
        $data = json_decode(file_get_contents($file), true);
        expect(json_last_error())->toBe(JSON_ERROR_NONE, "$file 不是合法 JSON");
        foreach ($canonical as $key) {
            expect(isset($data[$key]))->toBeTrue("[$locale] 缺失 key: $key");
        }
    }
});

it('各语种均保留全部占位符', function () {
    $zh = json_decode(file_get_contents(lang_path('zh_CN.json')), true);
    foreach (['zh_TW', 'vi', 'ja', 'th', 'en'] as $locale) {
        $data = json_decode(file_get_contents(lang_path("$locale.json")), true);
        foreach ($zh as $key => $zhValue) {
            preg_match_all('/:\w+/', (string) $zhValue, $m);
            foreach (array_unique($m[0]) as $placeholder) {
                expect(str_contains($data[$key] ?? '', $placeholder))->toBeTrue("[$locale] 占位符 $placeholder 丢失: $key");
            }
        }
    }
});

it('抽样 key 返回真实翻译', function () {
    foreach (['en', 'vi', 'ja', 'th'] as $locale) {
        App::setLocale($locale);
        expect(__('充值'))->not->toBe('充值');
    }
    App::setLocale('zh_TW');
    expect(__('提现'))->not->toBe('提现');
});
