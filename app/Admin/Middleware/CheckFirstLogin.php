<?php

namespace App\Admin\Middleware;

use Closure;
use Dcat\Admin\Admin;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckFirstLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 忽略Pjax请求
        $ignoreChangePasswordPromptPath = ltrim(admin_base_path('auth/ignoreChangePasswordPrompt'), '/');
        if (\Dcat\Admin\Support\Helper::isAjaxRequest($request) || $request->is($ignoreChangePasswordPromptPath)) {
            return $next($request);
        }
        $auth_setting_url              = admin_url('auth/setting');
        $ignoreChangePasswordPromptUrl = admin_url('auth/ignoreChangePasswordPrompt');

        if (Admin::user() && ! Admin::user()->password_changed_at) {
            admin_warning('安全警告', "您还未修改过密码，存在安全隐患，请立即 <a style=\"color: #f44336;font-weight: bold;\" href=\"{$auth_setting_url}\">修改密码</a> 以确保账户安全！<a style=\"color: #f44336;font-weight: bold;\" href=\"{$ignoreChangePasswordPromptUrl}\">点击此处忽略</a>");
        }

        return $next($request);
    }
}
