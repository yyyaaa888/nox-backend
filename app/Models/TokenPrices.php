<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// CREATE TABLE `token_prices` (
//     `id`          int(10) unsigned NOT NULL AUTO_INCREMENT
//     `price`       decimal(20,6) unsigned NOT NULL COMMENT '收益金额',
//     `date`        date     NOT NULL     COMMENT '结算日期',
//     `created_at`  datetime NOT NULL     COMMENT '添加时间',
//     `updated_at`  datetime DEFAULT NULL COMMENT '更新时间',
//     PRIMARY KEY (`id`),
//     UNIQUE `date` (`date`)
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='代币价格';

/**
 * @property int $id
 * @property string $amount
 * @property string $date
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class TokenPrices extends Model
{
    protected $table = 'token_prices';

    protected $guarded = [];
}
