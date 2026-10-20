<?php

namespace App;

class ErrorCode
{
    const CODE_SUCCESS = 200;

    const CODE_ERROR = 400;

    const CODE_DEFAULT = 10000; // 默认错误代码

    const ACCOUNT_LOGOUT = 401; // 未登录

    const UNBIND_INVITE_CODE = 405; // 未登录
}
