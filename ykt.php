<?php
require_once './http.class.php';
require_once './function.php';
$http = new http();
$act  = $_GET['act'] ?? 'recharge';

//04171805
//135315
//zxb1655
//04171803
//658175
if (isset($_GET['sid']) && $_GET['sid']) {
    dsetcookie('tegic_sid', $_GET['sid'], 24 * 60 * 60 * 7);
    $msg = (isset($_GET['msg']) && $_GET['msg']) ? $_GET['msg'] : '';
    header('location: ' . url($act, urlencode($msg)));
    echo '正在进入……';
    die;
}
$openid = getcookie('tegic_sid');
if (!getcookie('tegic_sid')) {
    require_once './template/notfound.html.php';
    die;
}
$userInfo = get_cache($openid);
if (!$userInfo && !in_array($act, ['bind'])) {
    header('location: ' . url('bind', urlencode(get_present_url())));
    die;
}
if ($act == 'recharge') {//充值
    if ($_POST) {
        $input = $_POST;
        if (!$input['money'] || !$input['buildingId'] || !$input['floorId'] || !$input['roomNo'] || !$input['studentId'] || !$input['clientId']) {
            exit(json_encode(['status' => 0, 'msg' => '信息填写完整']));
        }
        $input['money'] = (int)$input['money'];
        $cookie         = $input['ck'];
        unset($input['ck']);
        if ($input <= 0) {
            exit(json_encode(['status' => 0, 'msg' => '充值金额不对']));
        }
        $input['payType']  = 1;
        $input['isUpdate'] = 1;
        $input['terminal'] = 'Ios';
        $http->header('Cookie', $cookie);
        $charge = $http->post(http_build_query($input))->connect("http://icard.jluzh.com:18001/excard/poscard/mobile/powerCharge/charge.ib");
        if (strpos($charge, '电费缴费成功') !== false) {
            exit(json_encode(['status' => 1, 'msg' => '电费缴费成功']));
        }
        preg_match('@M\.i\("(.*?)"\);@s', $charge, $matches);
        exit(json_encode(['status' => 0, 'msg' => $matches[1]]));
        die;
    }
    $id       = $userInfo['username'];
    $password = $userInfo['ykt_psw'];
    $html     = $http->connect('https://icard.jluzh.com/1001.json?clientid=a59f5cbdc8b0fc0b7370346ade0f8317&checksum=111111&stucode=' . $id . '&stupsw=' . $password);
    $html     = json_decode($html, true);
    if ($html['resultCode'] != 0000) {
        $msg = '用户名密码错误';
        require_once './template/error.html.php';
        die;
    }

    $cookie = $http->substr($http->getHeader(), 'Set-Cookie: ', '; Path=/');
    $http->header('Cookie', $cookie);
    $res      = $http->connect('https://icard.jluzh.com/0002.json?clientid=a59f5cbdc8b0fc0b7370346ade0f8317&checksum=111111&stucode=' . $id);
    $userData = json_decode($res, true);
    if (!isset($userData['value'][0])) {
        $msg = '无法获取您的信息';
        require_once './template/error.html.php';
        die;
    }
    $http->connect("http://icard.jluzh.com:18001/excard/poscard/mobile/powerCharge/index.ib?terminal=Android&clientid=a59f5cbdc8b0fc0b7370346ade0f8317&checknum=111111&studentId={$id}&school=6&qyid=poscard");
//进入充值页面
    $data = $http->reset()->post("terminal=IOS&studentId=" . $id . "&clientId=&_firstAccess=1&isUpdate=0&dormId=&buildingId=&floorId=&roomNo=")->connect("http://icard.jluzh.com:18001/excard/poscard/mobile/powerCharge/init.ib");
//    preg_match('@<span class="c-org">(.*?)</span>@s', $data, $matches);
    $balance = $userData['value'][0]['balance'];//余额
    //获取充值的cookie，方便下一次使用
    $cookie = $http->substr($http->getHeader(), 'Set-Cookie: ', '; Path=/');
    $http->header('Cookie', $cookie);
    $getBuilding = $http->post("queryType=BUILDING&dormId=1&studentId={$id}&clientId={$id}&checkSum=111111&terminal=Ios")->connect("http://icard.jluzh.com:18001/excard/poscard/mobile/powerCharge/getHosueInfo.ib");
    $getBuilding = json_decode($getBuilding, true);
    require_once './template/recharge.html.php';
    die;
} elseif ($act == 'bind') {//绑定
    if ($_POST) {
        if (!$_POST['username'] || !$_POST['jw_psw'] || !$_POST['ykt_psw']) {
            exit(json_encode(['status' => 0, 'msg' => '信息填写完整']));
        }
        //一卡通验证
        $html = $http->connect('https://icard.jluzh.com/1001.json?clientid=a59f5cbdc8b0fc0b7370346ade0f8317&checksum=111111&stucode=' . $_POST['username'] . '&stupsw=' . $_POST['ykt_psw']);
        $html = json_decode($html, true);
        if ($html['resultCode'] != 0000) {
            exit(json_encode(['status' => 0, 'msg' => '一卡通帐号或密码不正确']));
        }
        //教务网验证
        $http->reset();
        $http->header('Origin', "http://jw.jluzh.com");
        $http->header('Referer', "http://jw.jluzh.com/xtgl/login_slogin.html?language=zh_CN&_t=1565767414867");
        $http->header('User-Agent', "Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A372 Safari/604.1");
        $html = $http->connect("http://jw.jluzh.com/xtgl/login_slogin.html");
        preg_match_all('@Set-Cookie: (.*?) Path@s', $http->getHeader(), $matches);

        $cookie    = implode('', array_reverse($matches[1]));
        $csrfToken = $http->substr($html, '<input type="hidden" id="csrftoken" name="csrftoken" value="', '"/>');
        $http->header('Cookie', $cookie);
        $http->post(['csrftoken' => $csrfToken, 'yhm' => $_POST['username'], 'mm' => $_POST['jw_psw'], 'mm' => $_POST['jw_psw']]);
        $login = $http->connect("http://jw.jluzh.com/xtgl/login_slogin.html?time=1565769460381");
        if (strpos($login, '用户登录') !== false) {
            exit(json_encode(['status' => 0, 'msg' => '教务网用户名或密码错误']));
        }
        dsetcookie('score_ck', $cookie, 60 * 5);
        set_cache($openid, $_POST);
        exit(json_encode(['status' => 1, 'msg' => 'success']));
    }
    $redirectUrl = $_GET['msg'];
    require_once './template/bind.html.php';
    die;
} elseif ($act == 'getBuild') {//获取楼层
    $type   = $_POST['type'] ?? 'floor';
    $cookie = $_POST['ck'];
    switch ($type) {
        case 'build':
            $data = $http->post("queryType=FLOOR&dormId=1&buildingId=124&studentId={$userInfo['username']}&clientId={$userInfo['username']}&checkSum=111111&terminal=Ios")->connect("http://icard.jluzh.com:18001/excard/poscard/mobile/powerCharge/getHosueInfo.ib");
            break;
        case 'floor':
            $data = $http->post("queryType=FLOOR&dormId=1&buildingId={$_POST['buildId']}&studentId={$userInfo['username']}&clientId={$userInfo['username']}&checkSum=111111&terminal=Ios")->connect("http://icard.jluzh.com:18001/excard/poscard/mobile/powerCharge/getHosueInfo.ib");
            break;
        case 'room':
            $data = $http->post("queryType=ROOM&dormId=1&buildingId={$_POST['buildId']}&floorId={$_POST['floorId']}&studentId={$userInfo['username']}&clientId={$userInfo['username']}&checkSum=111111&terminal=Ios")->connect("http://icard.jluzh.com:18001/excard/poscard/mobile/powerCharge/getHosueInfo.ib");
            break;
    }
    exit($data);
} elseif ($act == 'recharge_history') {//充值历史
    if ($_POST) {
        $chargeListInputParams = [
            'terminal' => 'Ios',
            'studentId' => $userInfo['username'],
            'clientId' => $userInfo['username'],
            'pageNum' => $_POST['page'],
            'access' => 'ajax',
        ];
        $http->header('Cookie', $_POST['ck']);
        $chargeListData = $http->post(http_build_query($chargeListInputParams))->connect("http://icard.jluzh.com:18001/excard/poscard/mobile/powerCharge/list.ib");
        exit($chargeListData);
    }
    $cookie = $_GET['ck'];
    if (!$cookie) {
        header('location: ' . url('recharge'));
        die;
    }
    require_once './template/recharge.history.html.php';
    die;
} elseif ($act == 'balance') {//余额消息记录
    if ($_POST) {
        $http->header('Cookie', $_POST['ck']);
        $res = $http->connect("https://icard.jluzh.com/0005.json?stucode=" . $userInfo['username'] . "&startdate=" . trim($_POST['start']) . "&enddate=" . trim($_POST['end']));
        exit($res);
    }
    $id       = $userInfo['username'];
    $password = $userInfo['ykt_psw'];
    $html     = $http->connect('https://icard.jluzh.com/1001.json?clientid=a59f5cbdc8b0fc0b7370346ade0f8317&checksum=111111&stucode=' . $id . '&stupsw=' . $password);
    $html     = json_decode($html, true);
    if ($html['resultCode'] != 0000) {
        $msg = '用户名密码错误';
        require_once './template/error.html.php';
        die;
    }
    $cookie = $http->substr($http->getHeader(), 'Set-Cookie: ', '; Path=/');
    $http->header('Cookie', $cookie);
    $res      = $http->connect('https://icard.jluzh.com/0002.json?clientid=a59f5cbdc8b0fc0b7370346ade0f8317&checksum=111111&stucode=' . $id);
    $userData = json_decode($res, true);
    if (!isset($userData['value'][0])) {
        $msg = '无法获取您的信息';
        require_once './template/error.html.php';
        die;
    }
    $balance = $userData['value'][0]['balance'];//余额
    require_once './template/balance.html.php';
    die;
} else {
    die(1);
}