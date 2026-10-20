<?php

namespace App\Admin\Middleware;

use Closure;
use Dcat\Admin\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class CheckUserSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (Admin::guard()->check()) {
            $user             = Admin::user();
            $cachedTimestamp  = Cache::get('admin_user_last_login_'.$user->id);
            $sessionTimestamp = session('admin_login_timestamp');

            if (! $cachedTimestamp || ! $sessionTimestamp) { // 账号登录时间过期
                Admin::guard()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                admin_warning('登录提示', '您的账号登录时间已过期，请重新登录');

                return admin_redirect(admin_url('auth/login'));
            }

            if ($cachedTimestamp != $sessionTimestamp) { // 账号在其他地方登录
                Admin::guard()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                // return redirect(admin_base_path('auth/login'))->with('error', '您的账号已在其他地方登录');
                admin_warning('登录提示', '您的账号已在其他地方登录！');

                return admin_redirect(admin_url('auth/login'));
            }

            // 延长会话过期时间
            session([
                'admin_last_activity' => Carbon::now()->toDateTimeString(),
            ]);
        }

        return $next($request);
    }
}
