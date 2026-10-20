<?php

namespace App\Admin\Models;

use Dcat\Admin\Models\Administrator;
use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

// CREATE TABLE `admin_login_logs` (
//     `id`         int(10)      unsigned NOT NULL AUTO_INCREMENT,
//     `admin_id`   int(10)      unsigned NOT NULL DEFAULT 0    COMMENT '管理员id',
//     `admin_name` varchar(255)              NULL DEFAULT NULL COMMENT '登录账号',
//     `login_ip`   varchar(255)              NULL DEFAULT NULL COMMENT '登录IP',
//     `status`     tinyint(3)   unsigned NOT NULL              COMMENT '状态 1=成功 2=失败',
//     `remark`     text                                        COMMENT '备注',
//     `login_num`  int(10)      unsigned NOT NULL DEFAULT 0    COMMENT '今日登录次数',
//     `fail_num`   int(10)      unsigned NOT NULL DEFAULT 0    COMMENT '今日失败次数',
//     `created_at` datetime NOT NULL     COMMENT '添加时间',
//     `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
//     PRIMARY KEY (`id`),
//     KEY (`admin_id`),
//     KEY (`admin_name`),
//     KEY (`login_ip`)
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员登录日志';

/**
 * @property int $id
 * @property int $admin_id
 * @property string|null $admin_name
 * @property string|null $login_ip
 * @property int $status
 * @property string|null $remark
 * @property int $login_num
 * @property int $fail_num
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class AdminLoginLog extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'admin_login_logs';

    protected $guarded = [];

    public const STATUS_PASS = 1; // 成功

    public const STATUS_FAIL = 2; // 失败

    public const STATUS_MAP = [
        self::STATUS_PASS => '成功',
        self::STATUS_FAIL => '失败',
    ];

    public const STATUS_COLOR = [
        self::STATUS_PASS => 'success',
        self::STATUS_FAIL => 'danger',
    ];

    public static function addFailLog(?Administrator $user, ?string $ip, ?string $remark = null)
    {
        $_24Hours = [Carbon::now()->subDay(), Carbon::now()];
        // 统计24小时内失败次数
        $failNum = self::query()
            ->where('admin_id', $user->id)
            ->whereBetween('created_at', $_24Hours)
            ->where('status', self::STATUS_FAIL)
            ->count();
        // 统计24小时内登录次数
        $loginNum = self::query()
            ->where('admin_id', $user->id)
            ->whereBetween('created_at', $_24Hours)
            ->count();
        $adminLoginLog             = new self();
        $adminLoginLog->admin_id   = $user->id;
        $adminLoginLog->admin_name = $user->name;
        $adminLoginLog->login_ip   = $ip;
        $adminLoginLog->status     = self::STATUS_FAIL;
        $adminLoginLog->remark     = $remark;
        $adminLoginLog->fail_num   = $failNum + 1;
        $adminLoginLog->login_num  = $loginNum;
        $adminLoginLog->save();
    }

    public static function addNoAccountLog(string $username, ?string $ip, ?string $remark = null)
    {
        $_24Hours = [Carbon::now()->subDay(), Carbon::now()];
        // 统计24小时内失败次数
        $failNum = self::query()
            ->where('admin_name', $username)
            ->whereBetween('created_at', $_24Hours)
            ->where('status', self::STATUS_FAIL)
            ->count();
        // 统计24小时内登录次数
        $loginNum = self::query()
            ->where('admin_name', $username)
            ->whereBetween('created_at', $_24Hours)
            ->count();
        $adminLoginLog             = new self();
        $adminLoginLog->admin_name = $username;
        $adminLoginLog->login_ip   = $ip;
        $adminLoginLog->status     = self::STATUS_FAIL;
        $adminLoginLog->remark     = $remark;
        $adminLoginLog->fail_num   = $failNum + 1;
        $adminLoginLog->login_num  = $loginNum;
        $adminLoginLog->save();
    }

    public static function addPassLog(Administrator $user, ?string $ip, ?string $remark = null)
    {
        $_24Hours = [Carbon::now()->subDay(), Carbon::now()];
        // 统计24小时内失败次数
        $failNum = self::query()
            ->where('admin_id', $user->id)
            ->whereBetween('created_at', $_24Hours)
            ->where('status', self::STATUS_FAIL)
            ->count();
        // 统计24小时内登录次数
        $loginNum = self::query()
            ->where('admin_id', $user->id)
            ->whereBetween('created_at', $_24Hours)
            ->count();
        $adminLoginLog             = new self();
        $adminLoginLog->admin_id   = $user->id;
        $adminLoginLog->admin_name = $user->name;
        $adminLoginLog->login_ip   = $ip;
        $adminLoginLog->status     = self::STATUS_PASS;
        $adminLoginLog->remark     = $remark;
        $adminLoginLog->fail_num   = $failNum;
        $adminLoginLog->login_num  = $loginNum + 1;
        $adminLoginLog->save();
    }
}
