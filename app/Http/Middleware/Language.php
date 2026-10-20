<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class Language
{
    public const DEFAULT_LANG = 'zh_CN';

    public const LANGS = [
        'en'    => 'en', // 英
        'zh-TW' => 'zh_TW', // 繁体
        'zh-CN' => 'zh_CN', // 简体
        'ja'    => 'ja', // 日
        'th'    => 'th', // 泰
        'vi'    => 'vi', // 越南
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $lang = $request->header('accept-language');
        if ($lang) {
            $lang = str_replace('_', '-', $lang);
            if (isset(self::LANGS[$lang])) {
                $lang = self::LANGS[$lang];
            } else {
                $lang = self::DEFAULT_LANG;
            }
        } else {
            $lang = self::DEFAULT_LANG;
        }
        App::setLocale($lang);

        return $next($request);
    }
}
