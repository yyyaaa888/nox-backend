<?php

namespace App;

class Language
{
    public const EN = '1'; // English

    public const ZH_CN = '2'; // 中文简体

    public const ZH_TW = '3'; // 中文繁体

    public const JA = '4'; // 日语

    public const TH = '5'; // 泰语

    public const VI = '6'; // 越南语

    public const DEFAULT = self::EN; // 用户端默认语言

    public const ADMIN_DEFAULT = self::ZH_CN; // 管理端默认语言

    public const ALL = [
        self::EN,
        self::ZH_CN,
        self::ZH_TW,
        self::JA,
        self::TH,
        self::VI,
    ];

    public const NAME_MAP = [
        self::EN    => 'English',
        self::ZH_CN => '简体中文',
        self::ZH_TW => '繁體中文',
        self::JA    => '日本語',
        self::TH    => 'ไทย',
        self::VI    => 'Vietnamese',
    ];

    public const CN_NAME_MAP = [
        self::EN    => '英文',
        self::ZH_CN => '简体',
        self::ZH_TW => '繁体',
        self::JA    => '日语',
        self::TH    => '泰语',
        self::VI    => '越南',
    ];

    public const CODE_MAP = [
        self::EN    => 'en',
        self::ZH_CN => 'zh_CN',
        self::ZH_TW => 'zh_TW',
        self::JA    => 'ja',
        self::TH    => 'th',
        self::VI    => 'vi',
    ];

    public static function getLang()
    {
        $local = app()->getLocale();
        if (! $local) {
            return self::DEFAULT;
        }

        return array_search($local, self::CODE_MAP);
    }
}
