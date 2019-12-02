<?php
require_once './http.class.php';
require_once './function.php';
require_once './FileCache.php';
$http = new http();
$act  = $_GET['act'] ?? 'score';
$url  = "http://jw.jluzh.com";


//04171805
//135315
//zxb1655
//04171803
//658175
if (isset($_GET['sid']) && $_GET['sid']) {
    dsetcookie('tegic_sid', $_GET['sid'], 24 * 60 * 60 * 7);
    if (isset($_GET['ck']) && !empty($_GET['ck'])) {
        dsetcookie('score_ck', $_GET['ck'], 60 * 5);
    }
    $msg = (isset($_GET['msg']) && $_GET['msg']) ? $_GET['msg'] : '';
    header('location: ' . jwUrl($act, urlencode($msg)));
    echo '正在进入……';
    die;
}
$openid = getcookie('tegic_sid');
if (!getcookie('tegic_sid')) {
    require_once './template/notfound.html.php';
    die;
}

$userInfo = get_cache($openid);
$cookie   = getcookie('score_ck');
if (!$userInfo && !in_array($act, ['bind'])) {
    header('location: ' . url('bind', urlencode(get_present_url())));
//    require_once './template/bind.html.php';
    die;
}
$username = $userInfo['username'];
$psw      = $userInfo['jw_psw'];
if (!$cookie) {
    $http->header('Origin', "http://jw.jluzh.com");
    $http->header('Referer', "http://jw.jluzh.com/xtgl/login_slogin.html?language=zh_CN&_t=1565767414867");
    $http->header('User-Agent', "Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A372 Safari/604.1");
    $html = $http->connect("http://jw.jluzh.com/xtgl/login_slogin.html");
    preg_match_all('@Set-Cookie: (.*?) Path@s', $http->getHeader(), $matches);

    $cookie = implode('', array_reverse($matches[1]));
    dsetcookie('score_ck', $_GET['ck'], 24 * 60 * 60 * 7);
    $csrfToken = $http->substr($html, '<input type="hidden" id="csrftoken" name="csrftoken" value="', '"/>');
    $http->header('Cookie', $cookie);
    $http->post(['csrftoken' => $csrfToken, 'yhm' => $username, 'mm' => $psw, 'mm' => $psw]);
    $login = $http->connect("http://jw.jluzh.com/xtgl/login_slogin.html?time=1565769460381");

    if (strpos($login, '用户登录') !== false) {
        $msg = '用户名或密码错误';
        require_once './template/error.html.php';
        die;
    }
}

if ($act == 'score') {
    if ($_POST) {
        $data = [
            'xnm' => '',
            'xqm' => '',
            '_search' => 'false',
            'nd' => time(),
            'queryModel.showCount' => '300',
            'queryModel.currentPage' => '1',
            'queryModel.sortName' => 'xnmmc',
            'queryModel.sortOrder' => 'desc',
            'time' => '0',
        ];
        $url  = "http://jw.jluzh.com/cjcx/cjcx_cxDgXscj.html?doType=query&gnmkdmKey=N305005&sessionUserKey=" . $username;
        $http->header('Cookie', $cookie);
        $score   = $http->post($data)->connect($url);
        $score   = json_decode($score, true);
        $newData = [];
        if ($score['items']) {
            foreach ($score['items'] as $val) {
                $newData[] = [
                    'name' => $val['kcmc'],
                    'score' => $val['cj'],
                    'gpa' => isset($val['jd']) ? $val['jd'] : 0,
                    'xf' => isset($val['xf']) ? $val['xf'] : 0,
                    'xnm' => $val['xnm'],
                    'xnmmc' => $val['xnmmc'],
                    'xqm' => $val['xqm'],
                    'xqmmc' => $val['xqmmc'],
                    'xh_id' => $val['xh_id'],
                ];
            }
        }

        $result = ['data' => $newData, 'total' => $score['totalCount']];
        exit(json_encode(['status' => 1, 'msg' => 'success', 'data' => $result]));
    }
    require_once './template/score.html.php';
    die;

} else {
    $year = YEAR;
    $term = TERM;
    if ($_POST) {
        $cacheFile = new FileCache(array('cacheTime' => 60 * 60 * 24 * 7, 'suffix' => '.php'));
        $data      = $cacheFile->get('table-' . $username . '-' . $year . '-' . $term);
        if ($data) {
            $data  = json_decode($data, true);
            $table = json_decode($cacheFile->get('table-origin-' . $username . '-' . $year . '-' . $term), true);
        } else {
            $data = [
                'xnm' => $year,
                'xqm' => $term,
            ];
            $url  = "http://jw.jluzh.com//kbcx/xskbcx_cxXsKb.html?gnmkdmKey=N2151";
            $http->header('Cookie', $cookie);
            $data  = $http->post($data)->connect($url);
            $table = json_decode($data, true);

            $data = [];
            if ($table && $table['kbList']) {
                foreach ($table['kbList'] as $k => $v) {
                    $data[$v['xqjmc']][] = [
                        'name' => $v['kcmc'],
                        'week' => $v['zcd'],
                        'number' => $v['jcor'],
                        'classroom' => $v['cdmc'],
                    ];
                }
            }
            $cacheFile->set('table-' . $username . '-' . $year . '-' . $term, json_encode($data));
            $cacheFile->set('table-origin-' . $username . '-' . $year . '-' . $term, json_encode($table));
        }
        $weekArray = ["星期日", "星期一", "星期二", "星期三", "星期四", "星期五", "星期六"];
        $week      = $weekArray[date("w", time())];
        $result    = ['info' => $table['xsxx'], 'data' => $data];
        list($week, $data) = tableFormat($result);
        exit(json_encode(['status' => 1, 'msg' => 'success', 'data' => compact('week', 'data')]));
    }
    require_once './template/table.html.php';
    die;
}


function tableFormat($result)
{
    $week = array_keys($result['data']);
    $data = [];
    foreach ($result['data'] as $k => $v) {
        $result = [
            '', '', '', '', '', '', '', '', '', '', '', ''
        ];
        foreach ($v as $key => $val) {
            $arr = explode('-', $val['number']);
            for ($i = $arr[0]; $i <= $arr[1]; $i++) {
                $result[$i - 1] = sprintf("%s[%s][%s]", $val['name'], $val['number'], $val['classroom']);
            }
        }
        $data[] = $result;
    }
    return [$week, $data];
}