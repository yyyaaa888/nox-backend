<?php

namespace App\Admin\Middleware;

use Dcat\Admin\Admin;
use Dcat\Admin\OperationLog\Models\OperationLog as OperationLogModel;
use Illuminate\Http\Request;

class LogOperation extends \Dcat\Admin\OperationLog\Http\Middleware\LogOperation
{
    protected $secretFields = [
        'password',
        'password_confirmation',
    ];

    protected $except = [
        'dcat-admin.operation-log.*',
    ];

    protected $defaultAllowedMethods = ['HEAD', 'POST', 'PUT', 'DELETE', 'CONNECT', 'OPTIONS', 'TRACE', 'PATCH'];

    /**
     * Handle an incoming request.
     *
     *
     * @return mixed
     */
    public function handle(Request $request, \Closure $next)
    {
        if ($this->shouldLogOperation($request)) {
            $user = Admin::user();

            $log = [
                'user_id' => $user ? $user->id : 0,
                'path'    => substr($request->path(), 0, 255),
                'method'  => $request->method(),
                'ip'      => $request->getClientIp(),
                'input'   => $this->formatInput($request->input()),
            ];

            try {
                OperationLogModel::create($log);
            } catch (\Exception $exception) {
                // pass
            }
        }

        return $next($request);
    }
}
