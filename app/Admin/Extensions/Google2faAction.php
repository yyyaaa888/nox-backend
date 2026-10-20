<?php

namespace App\Admin\Extensions;

use Dcat\Admin\Admin;
use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Models\Administrator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class Google2faAction extends RowAction
{
    /**
     * 标题
     *
     * @return string
     */
    public function title()
    {
        $admin = $this->getRow();
        $user  = Admin::user();
        if ($admin->id == $user->id || $user->id == Administrator::DEFAULT_ID) {
            if (! $admin->google2fa_secret) {
                return '<span class="text-info">去开启</span>';
            } else {
                return '<span class="text-danger">去关闭</span>';
            }
        } else {
            return '';
        }
    }

    /**
     * 设置确认弹窗信息，如果返回空值，则不会弹出弹窗
     *
     * 允许返回字符串或数组类型
     *
     * @return array|string|void
     */
    public function confirm()
    {
        $admin = $this->getRow();
        $user  = Admin::user();
        if ($admin->id == $user->id || $user->id == Administrator::DEFAULT_ID) {
            if (! $admin->google2fa_secret) {
                $message = '确认开启谷歌验证？';
            } else {
                $message = '确认关闭谷歌验证？';
            }
        } else {
            $message = '无权限';
        }

        return [
            // 确认弹窗 title
            $message,
            // 确认弹窗 content
            $this->getKey(),
        ];
    }

    public function handle(Request $request)
    {
        $id    = $this->getKey();
        $admin = Administrator::query()->where('id', $id)->first();
        if (empty($admin)) {
            return $this->response()->error('管理员不存在')->refresh();
        }

        $user = Admin::user();
        if ($admin->id == $user->id || $user->id == Administrator::DEFAULT_ID) {
            try {
                if (! $admin->google2fa_secret) {
                    $google2fa = new \PragmaRX\Google2FA\Google2FA();
                    $google2fa->setAlgorithm(\PragmaRX\Google2FA\Support\Constants::SHA512);
                    $key                     = $google2fa->generateSecretKey();
                    $key                     = Crypt::encryptString($key);
                    $admin->google2fa_secret = $key;
                    $admin->google2fa_ts     = 0;
                    $admin->save();

                    return $this->response()->success('已开启')->refresh();
                } else {
                    $admin->google2fa_secret = null;
                    $admin->google2fa_ts     = 0;
                    $admin->save();

                    return $this->response()->success('已关闭')->refresh();
                }
            } catch (\Exception $e) {
                return $this->response()->error($e->getMessage());
            }
        }

        return $this->response()->error('无权限');
    }

    /**
     * 设置要POST到接口的数据
     *
     * @return array
     */
    public function parameters()
    {
        return [
            // 发送当前行 id 字段数据到接口
            'id' => $this->getKey(),
        ];
    }
}
