<?php

namespace App\Admin\Forms;

use App\Jobs\UserUpgradeJob;
use App\Models\Level;
use App\Models\User;
use Dcat\Admin\Widgets\Form;
use Illuminate\Support\Facades\DB;

class SetVirtualLevelForm extends Form
{
    /**
     * Handle the form request.
     *
     * @param array $input
     *
     * @return mixed
     */
    public function handle(array $input)
    {
        $uid = $input['user_id'] ?? null;
        $level = $input['level'] ?? 0;
        if (!$uid) {
            return $this->response()->error('用户ID不能为空');
        }
        if (!is_numeric($uid)) {
            return $this->response()->error('用户ID必须是数字');
        }
        $level = intval($level);
        if (!in_array($level, array_keys(Level::LEVEL_MAP))) {
            return $this->response()->error('等级不存在');
        }

        $user = User::getInfo($uid);
        if (!$user) {
            return $this->response()->error('用户不存在');
        }

        try {
            DB::beginTransaction();
            // $admin_id = auth('admin')->user()->id;

            // $user->level_id = $level;
            $user->virtual_level = $level;
            $user->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->response()->error($e->getMessage());
        }

        UserUpgradeJob::dispatch($user->id);

        return $this
            ->response()
            ->success('设置成功')
            ->refresh();
    }

    /**
     * Build a form here.
     */
    public function form()
    {
        $this->select('user_id', '用户')->ajax('/api/users')->required();
        $this->select('level', '虚拟等级')->options(Level::LEVEL_MAP)->required();
    }

    /**
     * The data of the form.
     *
     * @return array
     */
    public function default()
    {
        return [];
    }
}
