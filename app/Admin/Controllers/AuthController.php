<?php

namespace App\Admin\Controllers;

use App\Admin\Models\AdminLoginLog;
use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Http\Controllers\AuthController as BaseAuthController;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Models\Administrator;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;

class AuthController extends BaseAuthController
{
    protected $view = 'pages.login-google2fa';

    /**
     * Show the login page.
     *
     * @return Content|\Illuminate\Http\RedirectResponse
     */
    public function getLogin(Content $content)
    {
        if ($this->guard()->check()) {
            return redirect($this->getRedirectPath());
        }

        // $messages = getGlobalConfig('login-scroll');
        // $messages = explode(',', $messages);

        // $canvas = getGlobalConfig('login-canvas') ?? 'space';
        // $spotlight = getGlobalConfig('spotlight');
        // $loginScroll = getGlobalConfig('login-scroll-switch');

        // return $content->full()->body(view($this->view, compact(['messages', 'canvas', 'spotlight', 'loginScroll'])));
        return $content->full()->body(view($this->view));
    }

    /**
     * User logout.
     *
     * @return \Illuminate\Http\RedirectResponse|string
     */
    public function getLogout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();

        $path = admin_url('auth/login');
        if ($request->pjax()) {
            return "<script>location.href = '$path';</script>";
        }

        return redirect($path);
    }

    public function postLogin(Request $request)
    {
        $credentials = $request->only([$this->username(), 'password']);
        $remember    = (bool) $request->input('remember', false);
        $code        = $request->input('code');

        /** @var \Illuminate\Validation\Validator $validator */
        $validator = Validator::make($credentials + ['code' => $code], [
            $this->username() => 'required|string|max:255',
            'password'        => 'required|string|max:255',
            // 'g-recaptcha-response' => 'required|captcha',
            'code' => 'nullable|digits:6',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorsResponse($validator);
        }

        $ip       = $request->ip();
        $username = $credentials[$this->username()];

        $user = \Dcat\Admin\Models\Administrator::where($this->username(), $username)->first();
        if (! $user) {
            AdminLoginLog::addNoAccountLog($username, $ip, '账号不存在');

            return $this->validationErrorsResponse([
                $this->username() => $this->getFailedLoginMessage(),
            ]);
        }

        if ($this->guard()->attempt($credentials, $remember)) {
            $user      = Admin::user();
            $google2fa = new \PragmaRX\Google2FA\Google2FA();
            if ($user->google2fa_secret) {
                if (! $code) {
                    AdminLoginLog::addFailLog($user, $ip, '未输入Google验证码');

                    return $this->validationErrorsResponse([
                        'code' => '已开启Google验证，请输入验证码',
                    ]);
                }
                try {
                    $secret_key = Crypt::decryptString($user->google2fa_secret);
                } catch (DecryptException $e) {
                    AdminLoginLog::addFailLog($user, $ip, 'Google验证配置错误');

                    return $this->validationErrorsResponse([
                        'code' => 'Google验证配置错误，请联系管理员',
                    ]);
                }
                $timestamp = $google2fa->verifyKeyNewer($secret_key, $code, $user->google2fa_ts);
                if ($timestamp !== false) {
                    $user->google2fa_ts = $timestamp;
                    $user->save();
                } else {
                    AdminLoginLog::addFailLog($user, $ip, 'Google验证码错误');

                    return $this->validationErrorsResponse([
                        'code' => '验证码错误',
                    ]);
                }
            }

            return $this->sendLoginResponse($request);
        }

        AdminLoginLog::addFailLog($user, $ip, $this->getFailedLoginMessage());

        return $this->validationErrorsResponse([
            $this->username() => $this->getFailedLoginMessage(),
        ]);
    }

    // 忽略修改密码提示
    public function ignoreChangePasswordPrompt()
    {
        $user                      = Admin::user();
        $user->password_changed_at = Carbon::now();
        $user->save();
        $previousUrl = \Dcat\Admin\Support\Helper::getPreviousUrl() ?: admin_url('/');

        // admin_warning('安全警告', '您已忽略修改密码提示，请注意安全');
        return redirect($previousUrl);
    }

    /**
     * Model-form for user setting.
     *
     * @return Form
     */
    protected function settingForm()
    {
        return new Form(new Administrator(), function (Form $form) {
            $form->action(admin_url('auth/setting'));

            $form->disableCreatingCheck();
            $form->disableEditingCheck();
            $form->disableViewCheck();

            $form->tools(function (Form\Tools $tools) {
                $tools->disableView();
                $tools->disableDelete();
            });

            $form->display('username', trans('admin.username'));
            $form->text('name', trans('admin.name'))->required();
            // $form->image('avatar', trans('admin.avatar'))->autoUpload();

            $form->password('old_password', trans('admin.old_password'));

            $form->password('password', trans('admin.password'))
                ->help('密码长度为8-20个字符，包含字母、数字和特殊字符')
                ->minLength(8)
                ->maxLength(20)
                ->customFormat(function ($v) {
                    if ($v == $this->password) {
                        return;
                    }

                    return $v;
                });
            $form->password('password_confirmation', trans('admin.password_confirmation'))->same('password');

            $form->ignore(['password_confirmation', 'old_password']);

            $form->saving(function (Form $form) {
                if ($form->password && $form->model()->password != $form->password) {
                    $form->password                     = bcrypt($form->password);
                    $form->model()->password_changed_at = Carbon::now();
                }

                if (! $form->password) {
                    $form->deleteInput('password');
                }
            });

            $form->saved(function (Form $form) {
                return $form
                    ->response()
                    ->success(trans('admin.update_succeeded'))
                    ->redirect('auth/setting');
            });
        });
    }

    /**
     * Send the response after the user was authenticated.
     *
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function sendLoginResponse(Request $request)
    {
        // 获取当前用户
        $user = Admin::user();

        AdminLoginLog::addPassLog($user, $request->ip(), '登录成功');

        // 更新用户登录IP和登录时间
        $user->last_login_ip = $request->ip();
        $user->last_login_at = Carbon::now();
        $user->save();

        $request->session()->regenerate();

        // 存储新的登录时间戳
        $loginTimestamp = Carbon::now()->timestamp;
        Cache::put('admin_user_last_login_'.$user->id, $loginTimestamp, 3600 * 24 * 30); // 30天过期

        // 在会话中存储登录时间戳
        session(['admin_login_timestamp' => $loginTimestamp]);

        $path = $this->getRedirectPath();

        return $this->response()
            ->success(trans('admin.login_successful'))
            ->locationToIntended($path)
            ->locationIf(Admin::app()->getEnabledApps(), $path)
            ->send();
    }
}
