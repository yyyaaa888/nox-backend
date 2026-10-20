<?php

namespace App\Admin\Forms;

use App\Models\User;
use Dcat\Admin\Widgets\Form;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ClearPayPasswordForm extends Form
{
    /**
     * Handle the form request.
     *
     *
     * @return mixed
     */
    public function handle(array $input)
    {
        $validator = Validator::make($input, [
            'user_id' => 'required|integer',
        ], [
            'user_id.required' => __('用户ID不能为空'),
            'user_id.integer'  => __('用户ID必须是数字'),
        ]);
        if ($validator->fails()) {
            return $this->response()->error($validator->errors()->first());
        }
        $input = $validator->validated();

        $uid = $input['user_id'];

        /** @var User $user */
        $user = User::find($uid);
        if (! $user) {
            return $this->response()->error('用户不存在');
        }

        try {
            DB::beginTransaction();
            $admin_id           = auth('admin')->user()->id;
            $user->pay_password = null;
            $user->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->response()->error($e->getMessage());
        }

        return $this
            ->response()
            ->success('清除支付密码成功')
            ->refresh();
    }

    /**
     * Build a form here.
     */
    public function form()
    {
        $this->select('user_id', '用户')->options(function ($id) {
            $user = \App\Models\User::find($id);
            if ($user) {
                return [$user->id => $user->name.'('.$user->remark.')'];
            }

            return [];
        })->ajax('/api/users')->required();
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

    /**
     * 是否有权限判断.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Illuminate\Contracts\Auth\Authenticatable|\Dcat\Admin\Traits\HasPermissions|null  $user
     */
    protected function authorize($user): bool
    {
        return $user->isRole('clear_pay_password') || $user->isAdministrator();
    }
}
