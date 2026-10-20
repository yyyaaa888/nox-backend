<?php

namespace App\Admin\Controllers;

use App\Admin\Forms\Form;
use App\Admin\Repositories\Config;
use Dcat\Admin\Show;
use Dcat\Admin\Widgets\Tab;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ConfigController extends BaseController
{
    protected $hidden = [
        'key',
        'group',
        'tab',
    ];

    protected $title = '系统配置';

    protected string $defaultForm = 'bus';

    protected function grid()
    {
        $tab   = new Tab();
        $forms = $this->forms();
        foreach ($forms as $id => $form) {
            $active = $id == $this->defaultForm;
            $tab->add($form['title'], $form['form'], $active, $id);
        }

        return $tab;
    }

    protected function forms(?string $tab = null)
    {
        $forms    = [];
        $baseForm = Form::make(new Config(), function (Form $form) {
            $this->before($form);
            $form->hidden('group')->value('base');
            $form->hidden('tab')->value('base');
            $form->switch('not_allow_to_user_login', '禁止用户登录')->rules('required|integer|in:0,1')->help('禁止后，所有用户无法登录用户端');
        });
        $forms['base'] = [
            'title' => '基本配置',
            'form'  => $baseForm,
        ];

        $homeForm = Form::make(new Config(), function (Form $form) {
            $this->before($form);
            $form->hidden('group')->value('home');
            $form->hidden('tab')->value('home');
            $form->divider('数据修正');
            $form->number('site_home_nox_pool', '全网NOXO池')->rules('required|integer|min:0')->help('全网NOXO池');
            $form->number('site_home_usdt_pool', '全网USDT池')->rules('required|integer|min:0')->help('全网USDT池');
            $form->number('site_home_power', '全网算力')->rules('required|integer|min:0')->help('全网算力');
            $form->number('site_home_power_big', '全网大区算力')->rules('required|integer|min:0')->help('全网大区算力，不能大于全网算力，全网小区算力 = 全网算力 - 全网大区算力');
            $form->number('site_home_user_cc', '全网人数')->rules('required|integer|min:0')->help('全网人数');
        });
        $forms['home'] = [
            'title' => '首页配置',
            'form'  => $homeForm,
        ];

        $nodeForm = Form::make(new Config(), function (Form $form) {
            $this->before($form);
            $form->hidden('group')->value('node');
            $form->hidden('tab')->value('node');
            $form->switch('site_allow_node_buy', '是否开启节点购买')->rules('required|integer|in:0,1')->help('关闭后，用户无法进行节点购买');
        });
        $forms['node'] = [
            'title' => '节点配置',
            'form'  => $nodeForm,
        ];

        $powerForm = Form::make(new Config(), function (Form $form) {
            $this->before($form);
            $form->hidden('group')->value('power');
            $form->hidden('tab')->value('power');
            $form->switch('site_allow_power_buy', '是否开启算力购买')->rules('required|integer|in:0,1')->help('关闭后，用户无法进行算力购买');
        });
        $forms['power'] = [
            'title' => '算力配置',
            'form'  => $powerForm,
        ];

        // $rechargeForm = Form::make(new Config(), function (Form $form) {
        //     $this->before($form);
        //     $form->hidden('group')->value('recharge');
        //     $form->hidden('tab')->value('recharge');

        //     $form->divider('USDT充值');
        //     $form->switch('site_allow_recharge_usdt', '是否开启USDT充值')->rules('required|integer|in:0,1')->help('关闭后，用户无法进行USDT充值');
        //     $form->number('site_recharge_min_usdt', 'USDT最低充值金额')->rules('required|integer|min:0')->help('USDT最低充值金额，只支持整数');

        //     $form->divider('NOXO充值');
        //     $form->switch('site_allow_recharge_nox', '是否开启NOXO充值')->rules('required|integer|in:0,1')->help('关闭后，用户无法进行NOXO充值');
        //     $form->number('site_recharge_min_nox', 'NOXO最低充值金额')->rules('required|integer|min:0')->help('NOXO最低充值金额，只支持整数');
        // });
        // $forms['recharge'] = [
        //     'title' => '充值配置',
        //     'form'  => $rechargeForm,
        // ];

        $withdrawForm = Form::make(new Config(), function (Form $form) {
            $this->before($form);
            $form->hidden('group')->value('withdraw');
            $form->hidden('tab')->value('withdraw');

            $form->divider('USDT提现');
            $form->switch('site_allow_w_usdt', '是否开启USDT提现')->rules('required|integer|in:0,1')->help('关闭后，用户无法进行USDT提现');
            $form->number('site_w_min_usdt', 'USDT最低提现金额')->rules('required|integer|min:0')->help('USDT最低提现金额，只支持整数');
            $form->decimal('site_w_fee_rate_usdt', 'USDT提现手续费率')->rules('required|decimal:0,2|min:0')->help('USDT提现金额手续费率，最多只支持2位小数');

            $form->divider('NOXO提现');
            $form->switch('site_allow_w_nox', '是否开启NOXO提现')->rules('required|integer|in:0,1')->help('关闭后，用户无法进行NOXO提现。');
            $form->number('site_w_min_nox', 'NOXO最低提现金额')->rules('required|integer|min:0')->help('NOXO最低提现金额，只支持整数');
            $form->decimal('site_w_fee_rate_nox', 'NOXO提现手续费率')->rules('required|decimal:0,2|min:0')->help('NOXO提现手续费率，最多只支持2位小数');

            $form->divider('提现额度');
            $form->number('site_w_usdt_global_limit', '全站每日USDT提现额度')->rules('required|integer|min:0')->help('全站每日USDT提现额度，只支持整数');
            $form->number('site_w_nox_global_limit', '全站每日NOXO提现额度')->rules('required|integer|min:0')->help('全站每日NOXO提现额度，只支持整数');
            $form->number('site_w_usdt_user_limit', '用户每日USDT提现额度')->rules('required|integer|min:0')->help('用户每日USDT提现额度，只支持整数');
            $form->number('site_w_nox_user_limit', '用户每日NOXO提现额度')->rules('required|integer|min:0')->help('用户每日NOXO提现额度，只支持整数');
        });
        $forms['withdraw'] = [
            'title' => '提现配置',
            'form'  => $withdrawForm,
        ];

        $contactForm = Form::make(new Config(), function (Form $form) {
            $form->html('<h5>联系方式</h5>');

            $this->before($form);
            $form->hidden('group')->value('contact');
            $form->hidden('tab')->value('contact');
            $form->switch('site_show_contact', '是否显示联系方式')->rules('required|integer|in:0,1')->help('关闭后，用户端将不显示联系方式');
            $form->text('site_contact_tg', 'Telegram')->rules('required|string|max:30')->help('填写 Telegram 账号');
            $form->text('site_contact_x', 'Twitter')->rules('required|string|max:30')->help('填写 Twitter 账号');
        });
        $forms['contact'] = [
            'title' => '联系方式',
            'form'  => $contactForm,
        ];

        return $tab ? $forms[$tab] : $forms;
    }

    public function before(Form $form)
    {
        $form->edit(1);
        $form->action('config/change');
        $form->disableListButton();
        $form->disableCreatingCheck();
        $form->disableViewButton();
        $form->disableDeleteButton();
        $form->disableViewCheck();
        $form->disableEditingCheck();
    }

    public function change(Request $request)
    {
        $data  = $request->all();
        $group = $data['group'];
        $tab   = $data['tab'];

        if (empty($group)) {
            return $this->errorReturn('参数错误');
        }
        if (empty($tab)) {
            return $this->errorReturn('参数错误');
        }

        $input = [];
        foreach ($data as $k2 => $v2) {
            // if (is_array($v2)) {
            //     $v2 = json_encode($v2);
            // }
            if (substr($k2, 0, 1) == '_' || in_array($k2, $this->hidden)) {
                continue;
            }
            $input[$k2] = $v2;
        }
        if (empty($input)) {
            return $this->errorReturn('参数错误');
        }

        /** @var Form $form */
        $form = $this->forms($tab)['form'];
        $form->build();

        /** @var \Dcat\Admin\Form\Field $field */
        foreach ($form->builder()->fields() as $field) {
            if (! $validator = $field->getValidator($input)) {
                continue;
            }

            if (($validator instanceof \Illuminate\Validation\Validator) && $validator->fails()) {
                $failedValidators[] = [$field, $validator];
            }
        }
        if ($validationMessages = $form->validationMessages($input)) {
            return $this->validationErrorsResponse($validationMessages);
        }

        if (($response = $form->prepare($input))) {
            return $response;
        }

        $updates = $form->updates();

        // save
        foreach ($updates as $k => $v) {
            if (is_array($v)) {
                $v = json_encode($v);
            }
            \App\Models\Config::updateOrCreate([
                'key' => $k,
            ], [
                'group' => $group,
                'value' => $v,
            ]);
        }

        Cache::forget('configs'); // 删除缓存
        Cache::forget('common.config'); // 删除config缓存

        return $this->successReturn('更新成功');
    }

    /**
     * Make a show builder.
     *
     * @param  mixed  $id
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, new Config(), function (Show $show) {
            $show->field('id');
            $show->field('group');
            $show->field('key');
            $show->field('value');
            $show->field('created_at');
            $show->field('updated_at');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new Config(), function (Form $form) {
            $form->display('id');
            $form->text('group');
            $form->text('key');
            $form->text('value');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
