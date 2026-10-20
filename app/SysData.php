<?php

namespace App;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SysData
{
    private static $table = 'sys_data';

    private static $cacheKey = 'sys_data_';

    public const KEY_POWER = 'power'; // 全网算力值

    public const KEY_REAL_POWER = 'real_power'; // 全网真实算力值

    public const KEY_POWER_REWARD = 'power_reward'; // 每日产量

    public const KEYS = [
        self::KEY_POWER,
        self::KEY_REAL_POWER,
        self::KEY_POWER_REWARD,
    ];

    private static $key_decimals = [
        self::KEY_POWER        => 0,
        self::KEY_REAL_POWER   => 0,
        self::KEY_POWER_REWARD => 0,
    ];

    public static function inc(string $key, string $val = '1.0'): bool
    {
        if (! isset(self::$key_decimals[$key])) {
            return false;
        }
        $decimal = self::$key_decimals[$key];
        $val     = bcadd($val, 0, $decimal);
        Cache::forget(self::$cacheKey.$key);

        return DB::table(self::$table)
            ->where('key', $key)
            ->update([
                'val' => DB::raw("val + $val"),
            ]);
    }

    public static function dec(string $key, string $val = '1.0'): bool
    {
        if (! isset(self::$key_decimals[$key])) {
            return false;
        }
        $val = bcadd($val, 0, self::$key_decimals[$key]);
        Cache::forget(self::$cacheKey.$key);

        return DB::table(self::$table)
            ->where('key', $key)
            ->update([
                'val' => DB::raw("val - $val"),
            ]);
    }

    public static function reset(string $key, string $val): bool
    {
        if (! isset(self::$key_decimals[$key])) {
            return false;
        }
        $val = bcadd($val, 0, self::$key_decimals[$key]);
        Cache::forget(self::$cacheKey.$key);

        return DB::table(self::$table)
            ->where('key', $key)
            ->update([
                'val' => $val,
            ]);
    }

    public static function get(string $key): ?string
    {
        if (! isset(self::$key_decimals[$key])) {
            return null;
        }
        $cacheKey = self::$cacheKey.$key;
        $val      = Cache::rememberForever($cacheKey, function () use ($key) {
            $val = DB::table(self::$table)
                ->where('key', $key)
                ->value('val');

            return $val === null ? null : bcadd($val, 0, self::$key_decimals[$key]);
        });
        if ($val === null) {
            return null;
        }

        return $val;
    }

    public static function getKeys(): array
    {
        return self::KEYS;
    }

    public static function list(): array
    {
        $list = [];
        foreach (self::KEYS as $key) {
            $val        = self::get($key);
            $decimal    = self::$key_decimals[$key] ?? 6;
            $list[$key] = $val === null ? null : bcadd($val, 0, $decimal);
        }

        return $list;
    }

    public static function clearCache(): bool
    {
        foreach (self::KEYS as $key) {
            Cache::forget(self::$cacheKey.$key);
        }

        return true;
    }
}
