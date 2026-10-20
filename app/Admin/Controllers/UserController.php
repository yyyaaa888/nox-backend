<?php

namespace App\Admin\Controllers;

use App\Admin\Forms\ClearPayPasswordForm;
use App\Admin\Forms\SetVirtualLevelForm;
use App\Models\Level;
use App\Models\User;
use App\Models\UserRelationClosure;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Show;
use Dcat\Admin\Widgets\Card;

/**
 * @property int $id
 * @property int $level_id
 * @property int $base_level
 * @property mixed $userSpx
 */
class UserController extends AdminController
{
    protected $title = '用户列表';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new User(['level']), function (Grid $grid) {

            $grid->model()->orderBy('id', 'desc');

            $grid->tools([
                '<a class="btn btn-primary disable-outline" href="'.admin_url('users/setVirtualLevel').'">设置虚拟等级</a>',
                // '<a class="btn btn-primary disable-outline" href="'.admin_url('users/clearPayPassword').'">清除支付密码</a>',
            ]);

            $grid->column('id')->sortable();
            $grid->column('address', '用户钱包')->address();
            $grid->column('name', '用户名称');
            $grid->column('invited_uid', '邀请人ID');
            $grid->column('invite_code', '邀请码');
            $grid->column('level_id', '等级')->using(Level::FULL_LEVEL_MAP);
            $grid->column('virtual_level', '虚拟等级')->using(Level::LEVEL_MAP);
            $grid->column('invite_team_num', '推荐关系')->display(function () {
                $team_num   = UserRelationClosure::getTeamCount($this->id); // 团队人数
                $direct_num = UserRelationClosure::getDirectCount($this->id); // 直推人数

                return "团队{$team_num}人，直推{$direct_num}人";
            })->expand(function () {

                // 个人业绩
                $performance = User::getPersonalPerformance($this->id);
                $team_data   = UserRelationClosure::getTeamPerformances($this->id);

                $performance      = removeTrailingZeros($performance);
                $team_performance = removeTrailingZeros($team_data['total']);      // 团队业绩
                // $big_performance   = removeTrailingZeros($team_data['big_total']);  // 大区业绩
                $small_performance = removeTrailingZeros($team_data['small_total']); // 小区业绩

                $content = "有效直推：{$team_data['eff']}人<br/>";
                $content .= "个人业绩：{$performance}<br/>";
                $content .= "团队业绩：{$team_performance}<br/>";
                // $content .= "大区业绩：{$big_performance}<br/>";
                $content .= "小区业绩：{$small_performance}<br/>";
                $title = '团队信息';
                $card  = new Card($title, $content);

                return "<div style='padding:10px 10px 0'>$card</div>";
            });

            // $grid->column('remark', '后台备注')->limit(10);
            $grid->column('allow_w_usdt', 'USDT提现')->switch()->help('关闭后，该用户将无法提现USDT');
            $grid->column('allow_w_nox', 'NOXO提现')->switch()->help('关闭后，该用户将无法提现NOXO');
            $grid->column('status', '状态')->switch()->help('关闭后，该用户将无法登录');
            $grid->column('created_at')->sortable();

            $grid->disableViewButton();
            $grid->disableCreateButton();
            $grid->disableDeleteButton();
            $grid->withBorder(); // 显示边框
            $grid->disableRowSelector(); // 禁用复选框
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id')->width(2);
                $filter->like('address', '用户钱包')->width(3);
                $filter->like('name', '用户名称')->width(3);
                // $filter->like('remark', '后台备注')->width(3);
                $filter->equal('invited_uid', '邀请人ID')->width(2);
                $filter->like('invite_code', '邀请码')->width(2);
                $filter->equal('level_id', '等级')->select(Level::FULL_LEVEL_MAP)->width(2);
                $filter->equal('status', '状态')->select([1 => '是', 0 => '否'])->width(2);
                $filter->between('created_at')->datetime()->width(4);
            });
            // $grid->export();

        });
    }

    /**
     * Make a show builder.
     *
     * @param  mixed  $id
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, new User(), function (Show $show) {
            $show->field('id');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new User(), function (Form $form) {
            if ($form->isCreating()) {
                $form->saving(function ($form) {
                    return $form->response()->error('禁止操作');
                });
                $form->disableViewButton();
                $form->disableViewCheck();
                $form->disableDeleteButton();
            } else {
                $form->hidden('id');
                $form->display('address', '用户钱包')->disable();
                $form->display('name', '用户名称')->disable();
                $form->switch('allow_w_usdt', 'USDT提现')->default(0)->help('关闭后，该用户将无法提现USDT');
                $form->switch('allow_w_nox', 'NOXO提现')->default(0)->help('关闭后，该用户将无法提现NOXO');
                $form->switch('status', '状态')->default(1)->help('关闭后，该用户将无法登录');
                // $form->text('remark', '后台备注')->help('备注信息，50个字符以内')->rules('nullable|max:50')->saving(function ($value) {
                //     return $value ?: '';
                // });
                $form->disableViewButton();
                $form->disableViewCheck();
                $form->disableDeleteButton();
            }
        });
    }

    public function setVirtualLevel(Content $content)
    {
        return $content
            ->title('设置虚拟等级')
            ->body(new Card(new SetVirtualLevelForm()));
    }

    // public function clearPayPassword(Content $content)
    // {
    //     return $content
    //         ->title('清除支付密码')
    //         ->body(new Card(new ClearPayPasswordForm()));
    // }
}
