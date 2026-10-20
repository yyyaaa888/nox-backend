<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// CREATE TABLE `fees` (
//     `id`           int(10)       unsigned NOT NULL AUTO_INCREMENT,
//     `amount`       decimal(20,6) unsigned NOT NULL COMMENT '金额',
//     `date`         date         NOT NULL COMMENT '日期',
//     `created_at`   datetime     NOT NULL COMMENT '添加时间',
//     `updated_at`   datetime DEFAULT NULL COMMENT '更新时间',
//     PRIMARY KEY (`id`),
//     UNIQUE KEY (`date`)
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='手续费';

/**
 * @property int $id
 * @property string $amount
 * @property string $date
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Fee extends Model
{
    protected $table = 'fees';

    protected $guarded = [];
}
