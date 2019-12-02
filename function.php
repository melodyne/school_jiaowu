<?php
define('ROOT_PATH', dirname(__FILE__));
define('YEAR', '2019');
define('TERM', '9');
function url($action = '', $msg = '')
{
    return get_url() . 'ykt.php?_=' . time() . '&act=' . $action . '&msg=' . $msg;
}

function jwUrl($action = '', $msg = '')
{
    return get_url() . 'jw.php?_=' . time() . '&act=' . $action . '&msg=' . $msg;
}

/**
 * 获取完整网址
 */
function get_url()
{
    $name = '';
    if (isset($_SERVER['HTTP_HOST'])) {
        $name = $_SERVER['HTTP_HOST'];
    } else {
        $name = $_SERVER['SERVER_NAME'];
    }
    $path = $_SERVER['PHP_SELF'];
    $path = substr($path, 0, strrpos($path, '/') + 1);

    return 'http://' . $name . $path;
}

function get_present_url()
{
    $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
    $php_self     = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
    $path_info    = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
    $relate_url   = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self . (isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : $path_info);
    return $sys_protocal . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . $relate_url;
}

function dsetcookie($var, $value = '', $life = 0, $prefix = 1)
{
    $_COOKIE[$var] = $value;
    if ($value == '' || $life < 0) {
        $value = '';
        $life  = -1;
    }
    $life = $life > 0 ? time() + $life : ($life < 0 ? time() - 31536000 : 0);
    $path = '/';

    if (PHP_VERSION < '5.2.0') {
        setcookie($var, $value, $life, $path, $_SERVER['HTTP_HOST'], 0);
    } else {
        setcookie($var, $value, $life, $path, $_SERVER['HTTP_HOST'], 0, false);
    }
}

function getcookie($key)
{
    return isset($_COOKIE[$key]) ? $_COOKIE[$key] : '';
}

function get_cache($openid)
{
    $openid = str_replace(array('.', '/', '\\'), '', $openid);
    if (!$openid) {
        return false;
    }
    $dbfile = ROOT_PATH . '/dbfile/' . $openid . '.data';
    $dbdata = @unserialize(file_get_contents($dbfile));
    if (empty($dbdata)) {
        $dbdata = array();
    }
    return $dbdata;
}

function set_cache($openid, $data)
{
    $openid = str_replace(array('.', '/', '\\'), '', $openid);
    if (!$openid) {
        return false;
    }
    $dbfile = ROOT_PATH . '/dbfile/' . $openid . '.data';
    file_put_contents($dbfile, serialize($data));
    return true;
}