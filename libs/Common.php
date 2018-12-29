<?php
namespace ank\utils;

/**
 * 常用工具类集合
 */
class Common
{
    /**
     * opt请求数组数据
     * @param  [type] $url       url地址
     * @param  [type] $postData post的数据数组key=>value对,如果此项有数据自动转换为post请求
     * @param  [type] $headers  请求头信息为一维数组
     * @return [type]           返回网页内容
     */
    public static function sendRequest($opt)
    {
        $conf = [
            'url'      => '',
            'postdata' => [], //有数据时自动转为post
            'headers'  => [],
            'post'     => false, //默认为false，true时当前请求强制转为post
        ];
        $conf = array_merge($conf, $opt);
        if (is_string($conf['headers'])) {
            $conf['headers'] = trim($conf['headers']);
            $conf['headers'] = preg_split('/\r?\n/', $conf['headers']);
        }
        if (!$conf['url']) {
            return '';
        }
        $ch = curl_init();
        //跳过ssl检查项。
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, $conf['url']);
        if ($conf['postdata'] || $conf['post'] === true) {
            curl_setopt($ch, CURLOPT_POST, 1);
            $postdata = http_build_query($conf['postdata']);
            $conf['postdata'] && curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata); //设置post数据
        } else {
            curl_setopt($ch, CURLOPT_POST, 0);
        }
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60); //超时
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); //自动递归的抓取重定向后的页面,0时禁止重定向
        $conf['headers'] && curl_setopt($ch, CURLOPT_HTTPHEADER, $conf['headers']); //设置请求头
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //返回内容不直接输出到浏览器,保存到变量中
        //curl_setopt($ch, CURLOPT_HEADER, 1); //将响应头信息作为数据流输出
        // curl_setopt($ch, CURLOPT_REFERER, $conf['url']); //设置来源地址
        $result = curl_exec($ch);
        //如果上面设置啦响应头信息输出到数据流可以用下面的方法取响应头中的信息
        //取响应头信息
        //$weizhi = strpos($result, "\r\n\r\n");
        //$re_header = substr($result, 0, $weizhi);
        //取返回的内容
        //$result = substr($result, $weizhi + 4);
        //preg_match_all('/Set-Cookie:stest=(.*)/i', $result, $cookie);
        //请求出错退出
        if ($error = curl_error($ch)) {
            die($error);
        }
        curl_close($ch);
        return $result;
    }
    public function success($msg, $data = [], $url = '', $wait = 3)
    {
        header('Content-type: application/json; charset=utf-8'); //json
        $redata = [
            'wait'   => $wait,
            'code'   => 1,
            'msg'    => $msg,
            'data'   => $data,
            'url'    => $url,
            'errmsg' => '',
        ];
        echo json_encode($redata);
        die();
    }
    public function error($msg, $data = [], $url = '', $wait = 3)
    {
        header('Content-type: application/json; charset=utf-8'); //json
        $redata = [
            'wait'   => $wait,
            'code'   => 0,
            'msg'    => $msg,
            'data'   => $data,
            'url'    => $url,
            'errmsg' => '',
        ];
        echo json_encode($redata);
        die();
    }

    /**
     * 取参数
     * @param  string $key     [description]
     * @param  string $default [description]
     * @param  string $filter  string|array  $filter 过滤函数可以用正则,不符合的用默认值
     * @return [type]          [description]
     */
    public function input($key = '', $default = '')
    {
        $data = '';
        if ($key == '') {
            $data = '';
        } else if ($key == 'get.') {
            $data = $_GET;
        } else if ($key == 'post.') {
            $data = $_POST;
        } else if ($key == 'param.') {
            $data = array_merge($_GET, $_POST);
        } else if (isset($_GET[$key])) {
            $data = $_GET[$key];
        } else if (isset($_POST[$key])) {
            $data = $_POST[$key];
        } else {
            $data = '';
        }

        // if (is_string($filter)) {
        //     $filter = explode(',', $filter);
        // } else {
        //     $filter = (array) $filter;
        // }

        // $filter[] = $default;
        // if (is_array($data)) {
        //     array_walk_recursive($data, [$this, 'filterValue'], $filter);
        //     reset($data);
        // } else {
        //     $this->filterValue($data, $key, $filter);
        // }

        if (!$data) {
            $data = $default;
        }
        //默认去掉字符串两边的空格,应该没有其它特殊情况要保留空格的吧。有的话再说
        return is_string($data) ? trim($data) : $data;

    }

    /**
     * 获取客户端IP地址
     * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
     * @param boolean $adv 是否进行高级模式获取（有可能被伪装）
     * @return mixed
     */
    public function get_client_ip($type = false, $adv = true)
    {
        $type      = $type ? 1 : 0;
        $type      = 0;
        static $ip = null;
        if ($ip !== null) {
            return $ip[$type];
        }

        if ($adv) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $pos = array_search('unknown', $arr);
                if (false !== $pos) {
                    unset($arr[$pos]);
                }

                $ip = trim($arr[0]);
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u", ip2long($ip));
        $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
        return $ip[$type];
    }
    /**
     * 时间戳格式化
     * @param int $time
     * @return string 完整的时间显示
     * @author huajie <banhuajie@163.com>
     */
    public function time_format($time = null, $format = 'Y-m-d H:i:s')
    {
        if (preg_match('/\d{1,4}[^\d]+\d{1,2}[^\d]+\d{1,2}(\s+\d{1,2}[^\d]+\d{1,2}[^\d]+\d{1,2})?/', $time)) {
            return $time;
        } else {
            if (strlen($time) >= 12) {
                $time /= 1000;
            }
            return $time === null ? date($format, time()) : ($time ? date($format, $time) : '--');
        }
    }
}

// //使用方法
// $header = <<<eot
// Host:www.yidianzixun.com
// User-Agent:Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36
// eot;
// echo send_request([
//     'url'     => $url,
//     'headers' => $header,
// ]);
