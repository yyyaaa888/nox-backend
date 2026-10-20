<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\SoftDeletes;

// CREATE TABLE `chains` (
//     `id`       int(10) unsigned NOT NULL AUTO_INCREMENT,
//     `name`     varchar(50)  DEFAULT NULL COMMENT '链名称',
//     `alias`    varchar(50)  DEFAULT NULL COMMENT '别名',
//     `chain_id` int(10)      DEFAULT NULL COMMENT '链id',
//     `symbol`   varchar(10)  DEFAULT NULL COMMENT '符号',
//     `decimals` tinyint(11)  DEFAULT '0'  COMMENT '精度',
//     `rpc_url`  varchar(191) DEFAULT NULL COMMENT 'rpc地址',
//     `scan_url` varchar(255) DEFAULT NULL COMMENT '区块浏览器地址',
//     `status`   tinyint(4)   DEFAULT '1'  COMMENT '是否开启',
//     `created_at` datetime DEFAULT NULL,
//     `updated_at` datetime DEFAULT NULL,
//     `deleted_at` datetime DEFAULT NULL,
//     PRIMARY KEY (`id`)
//   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='区块链列表';

/**
 * @property int $id
 * @property string $name
 * @property string $alias
 * @property string $chain_id
 * @property string $symbol
 * @property int $decimals
 * @property string $rpc_url
 * @property string $scan_url
 * @property string $status
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Chain extends BaseModel
{
    use HasDateTimeFormatter;
    use SoftDeletes;

    protected $table = 'chains';
    protected $guarded = [];

    public static function info()
    {
        return self::where('status', 1)->first();
    }

    public static function getInfoByChainId(string $chain_id): ?self
    {
        return self::where('chain_id', $chain_id)->first();
    }
}
