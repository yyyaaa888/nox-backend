<?php

namespace App\Admin\Controllers;

use App\Admin\Metrics\Examples;
use App\Http\Controllers\Controller;
use Dcat\Admin\Layout\Column;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Layout\Row;

class HomeController extends Controller
{
    public function index(Content $content)
    {
        return $content
            ->header('仪表盘')
            ->description('')
            ->body(function (Row $row) {
                // $row->column(6, function (Column $column) {
                //     $column->row(Dashboard::title());
                //     $column->row(new Examples\Tickets());
                // });

                // $row->column(6, function (Column $column) {
                //     $column->row(function (Row $row) {
                //         $row->column(6, new Examples\NewUsers());
                //         $row->column(6, new Examples\NewDevices());
                //     });

                //     $column->row(new Examples\Sessions());
                //     $column->row(new Examples\ProductOrders());
                // });

                $row->column(6, function (Column $column) {
                    $column->row(function (Row $row) {
                        $row->column(6, Dashboard::title());
                        $row->column(6, new Examples\NewUsers());
                        $row->column(6, new Examples\PaymentStat());
                        $row->column(6, new Examples\WithdrawStat());
                        // $row->column(6, new Examples\GlobalPerfStat());
                        $row->column(6, new Examples\UserPerfStat());
                    });
                });
                $row->column(6, function (Column $column) {
                    $column->row(function (Row $row) {
                        // $row->column(6, new Examples\NftRewardStat());
                        $row->column(6, new Examples\TeamRewardStat());
                    });
                });
            });
    }
}
