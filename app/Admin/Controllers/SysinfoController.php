<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Dcat\Admin\Admin;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Layout\Row;
use Dcat\Admin\Widgets\Card;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Web3\Validators\AddressValidator;

class SysinfoController extends Controller
{
    public function index(Content $content)
    {
        return $content
            ->header('系统信息')
            ->description('')
            ->body(function (Row $row) {
                $row->column(6, Card::make(
                    '资产信息',
                    admin_view('admin.sysinfo.index', [
                        'data' => $this->getAssetInfo(),
                    ]),
                ));
                $row->column(6, Card::make(
                    '系统信息',
                    admin_view('admin.sysinfo.index', [
                        'data' => $this->getSysinfo(),
                    ]),
                ));
            });
    }

    private function getSysinfo()
    {
        $lastLogin = Cache::get('admin_user_last_login_'.Admin::user()->id);

        return [
            'APP_NAME'     => env('APP_NAME'),
            'APP_ENV'      => env('APP_ENV'),
            'APP_DEBUG'    => env('APP_DEBUG') ? '是' : '否',
            'APP_TIMEZONE' => config('app.timezone'),
            'LAST_LOGIN'   => $lastLogin ? Carbon::createFromTimestamp($lastLogin, config('app.timezone'))->format('Y-m-d H:i:s') : null,
        ];
    }

    private function getAssetInfo()
    {
        return [
            '区块链ID' => env('ASSET_CHAIN_ID'),
            '支付合约' => $this->addressHide(env('PAY_CONTRACT')),
            // '提现合约' => $this->addressHide(env('WITHDRAW_CONTRACT')),
            '收款地址' => $this->addressHide(env('ASSET_PAY_ADDRESS')),
        ];
    }

    private function addressHide(?string $value)
    {
        if ($value) {
            if (AddressValidator::validate($value)) {
                return substr($value, 0, 4).'...'.substr($value, -4);
            } else {
                return '配置不正确';
            }
        }

        return '未配置';
    }
}
