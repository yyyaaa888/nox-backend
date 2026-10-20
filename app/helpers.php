<?php

use App\Models\Level;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

if (! function_exists('hashPassword')) {
    /**
     * 密码hash
     *
     * @param  string  $password
     * @param  string  $salt
     * @return string
     */
    function hashPassword($password, $salt)
    {
        return md5(md5($password).$salt);
    }
}
if (! function_exists('apiSuccess')) {
    /**
     * 成功返回
     *
     * @param  array  $data
     * @return JsonResponse
     */
    function apiSuccess($data = [], string $msg = 'success', int $code = \App\ErrorCode::CODE_SUCCESS)
    {
        if (is_string($data)) {
            $msg  = $data;
            $data = [];
        }
        $return_array = [
            'code' => $code,
            'data' => $data,
            'msg'  => $msg,
        ];

        return response()->json($return_array);
    }
}

if (! function_exists('apiPage')) {
    /**
     * 成功返回
     *
     * @param  array  $data
     * @return JsonResponse
     */
    function apiPage(\Illuminate\Pagination\LengthAwarePaginator $page, string $msg = 'success', int $code = \App\ErrorCode::CODE_SUCCESS)
    {
        $data = [
            'current_page' => $page->currentPage(),
            'last_page'    => $page->lastPage(),
            'per_page'     => (int) $page->perPage(),
            'total'        => $page->total(),
            'data'         => $page->items(),
        ];
        $return_array = [
            'code' => $code,
            'data' => $data,
            'msg'  => $msg,
        ];

        return response()->json($return_array);
    }
}

if (! function_exists('apiError')) {
    /**
     * 成功返回
     *
     * @param  array  $data
     * @param  string  $msg
     * @param  int  $code
     * @return JsonResponse
     */
    function apiError($msg = 'error', $code = \App\ErrorCode::CODE_ERROR)
    {
        if ($msg instanceof Exception) {
            $msg = $msg->getMessage();
        } elseif ($msg instanceof \App\Exceptions\LogicException) {
            $msg = $msg->getMessage();
        }
        $return_array = [
            'code' => $code,
            'data' => [],
            'msg'  => $msg,
        ];

        return response()->json($return_array);
    }
}

if (! function_exists('apiError2')) {
    /**
     * 成功返回
     *
     * @param  array  $data
     * @param  string  $msg
     * @param  int  $code
     * @return JsonResponse
     */
    function apiError2($msg = 'error', $code = \App\ErrorCode::CODE_ERROR, $status = 400)
    {
        if ($msg instanceof Exception) {
            $msg = $msg->getMessage();
        } elseif ($msg instanceof \App\Exceptions\LogicException) {
            $msg = $msg->getMessage();
        }
        $return_array = [
            'code' => $code,
            'data' => [],
            'msg'  => $msg,
        ];

        return response()->json($return_array, $status);
    }
}

if (! function_exists('siteConfig')) {

    function siteConfig(string $key)
    {
        $cache = getTableCache('configs');
        $res   = getTableValueByKey($cache, 'value', 'key', $key);

        return $res;
    }
}
// 拼接图片地址
if (! function_exists('getImgUrl')) {
    function getImgUrl($url)
    {
        return config('app.url').'/uploads/'.$url;
    }
}

if (! function_exists('getTableCache')) {

    function getTableCache(string $table, $forget = false)
    {
        $cacheName = 'Table_'.$table;
        if ($forget) {
            Cache::forget($cacheName);
        }
        $cache = Cache::has($cacheName);
        if (! $cache) {
            $result = DB::table($table)->get();
            Cache::put($cacheName, json_decode(json_encode($result), true));
        }

        return Cache::get($cacheName);
    }
}
if (! function_exists('getTablePluck')) {

    function getTablePluck($result, $id, $name)
    {
        $data = [];
        if (gettype($result) == 'object' || gettype($result) != 'array') {
            $result_json = json_encode($result);
            $result      = json_decode($result_json, true);
        }
        if (is_array($result) && count($result) > 0) {
            foreach ($result as $key => $value) {
                $data[$value[$id]] = $value[$name];
            }
        }

        return $data;
    }
}
if (! function_exists('getTableValueByKey')) {

    function getTableValueByKey($result, $field, $key, $keyValue)
    {
        $value = '';
        if (gettype($result) == 'object' || gettype($result) != 'array') {
            $result_json = json_encode($result);
            $result      = json_decode($result_json, true);
        }
        if (is_array($result) && count($result) > 0) {
            foreach ($result as $k => $v) {
                if (! isset($v[$key]) || ! isset($v[$field])) {
                    return '';
                }
                if ($v[$key] == $keyValue) {
                    return $v[$field];
                }
            }
        }

        return $value;
    }
}

if (! function_exists('obj2array')) {

    function obj2array(string $obj)
    {
        $obj = (array) $obj;
        foreach ($obj as $k => $v) {
            if (gettype($v) == 'resource') {
                return;
            }
            if (gettype($v) == 'object' || gettype($v) == 'array') {
                $obj[$k] = (array) obj2array($v);
            }
        }

        return $obj;
    }
}

if (! function_exists('createCode')) {
    function createCode($str, $num)
    {
        $authnum = '';
        $char    = '0,1,2,3,4,5,6,7,8,9';
        $list    = explode(',', $char);
        for ($i = 0; $i < $num; $i++) {
            $rand_num = rand(0, 9);
            $authnum .= $list[$rand_num];
        }
        //        $arr     = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
        $arr     = date('ymdHis');
        $arr_num = rand(0, 7);
        if ($str != '') {
            $str = $str.$arr[$arr_num].$authnum;
        } else {
            $str = $arr[$arr_num].$authnum;
        }

        return $str;
    }
}

// if (!function_exists('now')) {
//     function now()
//     {
//         // return date("Y-m-d H:i:s");
//         return Carbon::now()->format('Y-m-d H:i:s');
//     }
// }
if (! function_exists('createOrderNo')) {
    function createOrderNo()
    {
        // return date("YmdHis") . random_int(100000, 999999);
        return Carbon::now()->format('YmdHis').random_int(100000, 999999);
    }
}

// 获取全局参数配置
if (! function_exists('getGlobalConfig')) {
    function getGlobalConfig(string $text, $default = null)
    {
        return DB::table('configs')->where('key', $text)->value('value') ?? $default;
    }
}
// 设置全局参数配置
if (! function_exists('setGlobalConfig')) {
    function setGlobalConfig(string $text, mixed $value)
    {
        if (empty($text)) {
            throw new LogicException('Error~');
        }

        $flag = DB::table('configs')->where('key', $text)->update(['value' => $value, 'updated_at' => now()]);
        if (! $flag) {
            throw new LogicException('Error~');
        }

        return $value;
    }
}
// 添加全局参数配置
if (! function_exists('addGlobalConfig')) {
    function addGlobalConfig(string $text, mixed $value, string $group = '')
    {
        if (empty($text)) {
            throw new LogicException('Error~');
        }

        $flag = DB::table('configs')->insert(['key' => $text, 'value' => $value, 'group' => $group, 'created_at' => now(), 'updated_at' => now()]);
        if (! $flag) {
            throw new LogicException('Error~');
        }

        return $value;
    }
}
if (! function_exists('getMillisecond')) {
    function getMillisecond()
    {
        [$microsecond, $time] = explode(' ', microtime()); // ' '中间是一个空格

        return (float) sprintf('%.0f', (floatval($microsecond) + floatval($time)) * 1000);
    }
}

// if (!function_exists('customRequestCaptcha')) {
//     function customRequestCaptcha()
//     {
//         return new \ReCaptcha\RequestMethod\Post();
//     }
// }
if (! function_exists('array_only')) {
    function array_only(array $array, array $keys)
    {
        return array_intersect_key($array, array_flip((array) $keys));
    }
}
if (! function_exists('randOrderNo')) {
    function randOrderNo()
    {
        // return date('YmdHis') . rand(10000, 99999);
        return Carbon::now()->format('YmdHis').rand(10000, 99999);
    }
}

if (! function_exists('bchexdec')) {
    function bchexdec(string $hex): string
    {
        // 移除空白并转小写
        $hex = strtolower(trim($hex));

        // 检查并移除 0x 前缀
        if (substr($hex, 0, 2) === '0x') {
            $hex = substr($hex, 2);
        }

        // 验证是否为合法的十六进制字符
        if (! preg_match('/^[0-9a-f]+$/', $hex)) {
            throw new InvalidArgumentException("Invalid hexadecimal input: {$hex}");
        }

        // 初始化结果为 "0"
        $decimal = '0';

        // 十六进制每一位对应一个乘法+加法操作
        $length = strlen($hex);
        for ($i = 0; $i < $length; $i++) {
            $char  = $hex[$i];
            $digit = ord($char) < 58 ? (int) $char : (ord($char) - 87); // 'a'=97 -> 10

            $decimal = bcmul($decimal, '16');
            $decimal = bcadd($decimal, (string) $digit);
        }

        return $decimal;
    }
}

if (! function_exists('bcround2')) {
    /**
     * 使用 BC Math 对高精度数字进行四舍五入
     *
     * @param  string  $number  要四舍五入的数字（字符串形式）
     * @param  int  $scale  保留的小数位数
     * @return string 四舍五入后的结果（字符串）
     */
    function bcround2(string $number, int $scale = 0)
    {
        // 如果 scale 为负数，可以处理整数部分的舍入（这里暂不支持）
        if ($scale < 0) {
            // 可选：抛出异常或扩展逻辑
            throw new InvalidArgumentException('Scale must be >= 0');
        }

        // 获取当前 scale + 1 位的值，用于判断是否 >=5
        $temp_scale = $scale + 1;
        $number     = bcadd($number, '0', $temp_scale); // 标准化精度

        // 提取第 (scale+1) 位数字
        $pos = strpos($number, '.');
        if ($pos === false) {
            // 没有小数点，直接补零
            $number .= '.';
        }
        $decimal_part = substr($number, $pos + 1);
        if (strlen($decimal_part) <= $scale) {
            // 不需要舍入
            return bcadd($number, '0', $scale);
        }

        $next_digit = (int) ($decimal_part[$scale] ?? 0);

        if ($next_digit >= 5) {
            // 需要进位：加 1 * 10^(-scale)
            $increment = bcpow('10', (string) (-$scale), $temp_scale);
            $number    = bcadd($number, $increment, $temp_scale);
        }

        // 截断到目标精度
        return bcadd($number, '0', $scale);
    }
}

// 使用 BC Math 对高精度数字进行向上舍入
if (! function_exists('bcceil2')) {
    function bcceil2(string $number, int $scale = 0)
    {
        if ($scale < 0) {
            throw new InvalidArgumentException('Scale must be >= 0');
        }

        $number = trim((string) $number);

        // 判断是否为负数
        $isNegative = strpos($number, '-') === 0;
        if ($isNegative) {
            $absNumber = substr($number, 1);
        } else {
            $absNumber = $number;
        }

        // 在绝对值上做“向下取整”（因为 ceil(-x) = -floor(x)）
        if ($isNegative) {
            $floored = bcfloor2($absNumber, $scale);

            return '-'.$floored;
        }

        // 改用字符串方式判断
        if (strpos($absNumber, '.') === false) {
            $absNumber .= '.0';
        }
        [$intPart, $decPart] = explode('.', $absNumber);
        $decPart             = rtrim($decPart, '0');

        if (strlen($decPart) <= $scale) {
            // 无需进位
            return $intPart.($scale > 0 ? '.'.str_pad($decPart, $scale, '0', STR_PAD_RIGHT) : '');
        }

        // 检查第 scale 位之后是否有非零
        $extra = substr($decPart, $scale);
        if (preg_match('/[1-9]/', $extra)) {
            // 需要进位
            $base      = $intPart.'.'.str_pad(substr($decPart, 0, $scale), $scale, '0', STR_PAD_RIGHT);
            $increment = '0.'.str_repeat('0', $scale - 1).'1';
            if ($scale === 0) {
                $increment = '1';
                $base      = $intPart;
            }

            return bcadd($base, $increment, $scale);
        } else {
            return $intPart.'.'.str_pad(substr($decPart, 0, $scale), $scale, '0', STR_PAD_RIGHT);
        }
    }
}

// 使用 BC Math 对高精度数字进行向下舍入
if (! function_exists('bcfloor2')) {
    function bcfloor2(string $number, int $scale = 0)
    {
        $number     = trim((string) $number);
        $isNegative = strpos($number, '-') === 0;
        if ($isNegative) {
            $absNumber = substr($number, 1);
            // floor(-x) = -ceil(x)
            $ceiled = bcceil($absNumber, $scale);

            return '-'.$ceiled;
        }

        if (strpos($number, '.') === false) {
            return $scale > 0 ? $number.'.'.str_repeat('0', $scale) : $number;
        }

        [$intPart, $decPart] = explode('.', $number);
        $decPart             = rtrim($decPart, '0');
        if (strlen($decPart) <= $scale) {
            return $intPart.($scale > 0 ? '.'.str_pad($decPart, $scale, '0', STR_PAD_RIGHT) : '');
        }

        $truncated = substr($decPart, 0, $scale);

        return $intPart.'.'.str_pad($truncated, $scale, '0', STR_PAD_RIGHT);
    }
}

if (! function_exists('all_in_array')) {
    // 检查sub中的元素是否是否全部都存在于array中
    function all_in_array(array $sub, array $array)
    {
        return collect($sub)->diff($array)->isEmpty();
    }
}

if (! function_exists('user_level')) {
    function user_level(?int $id = null): string
    {
        if (is_null($id)) {
            return 'S0';
        }
        if ($id > Level::MAX_LEVEL || $id < 0) {
            throw new LogicException('等级不存在');
        }

        return 'S'.$id;
    }
}

if (! function_exists('removeTrailingZeros')) {
    function removeTrailingZeros($numberStr)
    {
        if (is_null($numberStr)) {
            return '0';
        }
        if (strpos($numberStr, '.') !== false) {
            $numberStr = rtrim(rtrim($numberStr, '0'), '.');
        }

        return $numberStr;
    }
}

if (! function_exists('generateEthereumAddress')) {
    function generateEthereumAddress()
    {
        // 生成 20 字节（160 位）的随机数据
        $bytes = random_bytes(20);

        // 将二进制数据转换为小写的十六进制表示
        $address = '0x'.bin2hex($bytes);

        return $address;
    }
}

if (! function_exists('checkDecimalPlaces')) {
    // 判断小数是否是指定的位数
    function checkDecimalPlaces(string $value, int $min = 2, ?int $max = null)
    {

        if (! is_numeric($value)) {
            return false;
        }

        $matches = [];

        if (preg_match('/^[+-]?\d*\.?(\d*)$/', $value, $matches) !== 1) {
            return false;
        }

        $decimals = strlen(end($matches));

        if (is_null($max)) {
            return $decimals == $min;
        }

        return $decimals >= $min &&
            $decimals <= $max;
    }
}
