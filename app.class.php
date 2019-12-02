<?php

class app
{

    private $dbdata;
    private $dbfile;

    private $wxapi;

    private function http($url, $post = false, $charset = 'utf-8')
    {
        static $http = false;
        if ($http === false) {
            $http = new http();
            $http->set(CURLOPT_REFERER, 'http://icard.jluzh.com/');
        } else {
            $http->reset();
        }
        if (!empty($this->dbdata['JSESSIONID'])) {
            $http->header('Cookie', 'JSESSIONID=' . $this->dbdata['JSESSIONID']);
        }

        if ($post) {
            $http->post($post);
        }

        $result = $http->connect($url, $charset);
        $head   = $http->getHeader();
        if (preg_match('@Set-Cookie: JSESSIONID=([a-zA-Z0-9\-\!]+);@', $head, $match)) {
            $this->dbdata['JSESSIONID'] = $match[1];
        }
        return $result;
    }

    public function __construct($openid)
    {
        $openid = str_replace(array('.', '/', '\\'), '', $openid);
        if (!$openid) {
            die;
        }
        $this->dbfile = WX_ROOT . '/dbfile/' . $openid . '.data';
        $this->dbdata = @unserialize(file_get_contents($this->dbfile));
        if (empty($this->dbdata)) {
            $this->dbdata = array();
        }

    }

    function call_action($action)
    {
        if ($action == 'captch') {

        }
    }

    function call_wxapi($wxapi)
    {
        $this->wxapi = $wxapi;
        $data        = &$this->wxapi->data;

        if ($data['MsgType'] == 'event' && $data['Event'] == 'CLICK') {
            $data['MsgType'] = 'text';
            $data['Content'] = $data['EventKey'];
        }

        if ($data['MsgType'] != 'text') {
            return;
        }
        if (strpos($data['Content'], '绑定') !== false) {
            $this->dbdata['callback_content'] = $data['Content'];
            $this->bind();
            die;
        }
        if (strpos($data['Content'], '成绩') !== false) {
            $this->dbdata['callback_content'] = $data['Content'];
            $this->checkScoreBind();
            $this->getScore();
            die;
        }
        if (strpos($data['Content'], '课表') !== false) {
            $this->dbdata['callback_content'] = $data['Content'];
            $this->checkScoreBind();
            $this->getTable();
            die;
        }
        if (strpos($data['Content'], '一卡通') !== false) {
            $this->dbdata['callback_content'] = $data['Content'];
            $this->checkBind();
            $this->getYkt();
            die;
        }
        if (strpos($data['Content'], '电费') !== false) {
            $this->dbdata['callback_content'] = $data['Content'];
            $this->checkBind();
            $this->getYktRecharge();
            die;
        }
        unset($this->dbdata['callback_content']);
    }


    function __destruct()
    {
        file_put_contents($this->dbfile, serialize($this->dbdata));
    }

    function checkBind()
    {
        if (empty($this->dbdata['username']) || empty($this->dbdata['jw_psw']) || empty($this->dbdata['ykt_psw'])) {
            echo $this->wxapi->response("您还没有绑定一卡通学号和密码 \n<a href='" . $this->getYktUrl('bind', urlencode(url('recharge'))) . "'>点击进入绑定</a>");
            die;
        }
    }

    function checkScoreBind()
    {
        if (empty($this->dbdata['username']) || empty($this->dbdata['jw_psw']) || empty($this->dbdata['ykt_psw'])) {
            echo $this->wxapi->response("您还没有绑定教务网学号和密码\n <a href='" . $this->getYktUrl('bind', urlencode(jwUrl('score'))) . "'>点击进入绑定</a>");
            die;
        }
    }

    function bind()
    {
        $data = [];
        $bind = $this->dbdata;
        if (isset($bind['username']) && !empty($bind['username'])) {
            $tips = '修改绑定信息';
        } else {
            $tips = '您还未绑定';
        }
        $data[] = [$tips, '', '', $this->getYktUrl('bind', urlencode(url('recharge')))];

        echo $this->wxapi->response($data, 'news');
        die;
    }

    /**
     * 一卡通余额
     */
    function getYkt()
    {
        $id       = $this->dbdata['username'];
        $password = $this->dbdata['ykt_psw'];
        $http     = new http();
        $html     = $http->connect('https://icard.jluzh.com/1001.json?clientid=a59f5cbdc8b0fc0b7370346ade0f8317&checksum=111111&stucode=' . $id . '&stupsw=' . $password);
        $html     = json_decode($html, true);
        if ($html['resultCode'] != 0000) {
            echo $this->wxapi->response("用户名密码错误 \r\n <a href='" . $this->getYktUrl('bind', urlencode(url('recharge'))) . "'>点击进入重新绑定</a>");
            die;
        }
        $cookie = $http->substr($http->getHeader(), 'Set-Cookie: ', '; Path=/');
        $http->header('Cookie', $cookie);
        $res      = $http->connect('https://icard.jluzh.com/0002.json?clientid=a59f5cbdc8b0fc0b7370346ade0f8317&checksum=111111&stucode=' . $id);
        $userData = json_decode($res, true);
        if (!isset($userData['value'][0])) {
            echo $this->wxapi->response("无法获取您的信息 \r\n <a href='" . $this->getYktUrl('bind', urlencode(url('recharge'))) . "'>点击进入重新绑定</a>");
            die;
        }
        $balance = $userData['value'][0]['balance'];//余额
        echo $this->wxapi->response("{$userData['value'][0]['name']} 您好:\n校园卡余额: {$balance} 元\n----- \n<a href='" . $this->getYktUrl('balance') . "'>点击查看一卡通消费记录</a>\n\n<a href='" . $this->getYktUrl('recharge') . "'>点击充值电费</a>");
        die;
    }

    /**
     * 一卡通电费
     */
    function getYktRecharge()
    {
        $id       = $this->dbdata['username'];
        $password = $this->dbdata['ykt_psw'];
        $http     = new http();
        $html     = $http->connect('https://icard.jluzh.com/1001.json?clientid=a59f5cbdc8b0fc0b7370346ade0f8317&checksum=111111&stucode=' . $id . '&stupsw=' . $password);
        $html     = json_decode($html, true);
        if ($html['resultCode'] != 0000) {
            echo $this->wxapi->response("用户名密码错误 \r\n <a href='" . $this->getYktUrl('bind', urlencode(url('recharge'))) . "'>点击进入重新绑定</a>");
            die;
            die;
        }

        $cookie = $http->substr($http->getHeader(), 'Set-Cookie: ', '; Path=/');
        $http->header('Cookie', $cookie);
        $res      = $http->connect('https://icard.jluzh.com/0002.json?clientid=a59f5cbdc8b0fc0b7370346ade0f8317&checksum=111111&stucode=' . $id);
        $userData = json_decode($res, true);
        if (!isset($userData['value'][0])) {
            echo $this->wxapi->response("无法获取您的信息 \r\n <a href='" . $this->getYktUrl('bind', urlencode(url('recharge'))) . "'>点击进入重新绑定</a>");
            die;
        }
        $http->connect("http://icard.jluzh.com:18001/excard/poscard/mobile/powerCharge/index.ib?terminal=Android&clientid=a59f5cbdc8b0fc0b7370346ade0f8317&checknum=111111&studentId={$id}&school=6&qyid=poscard");
        $http->reset()->post("terminal=IOS&studentId=" . $id . "&clientId=&_firstAccess=1&isUpdate=0&dormId=&buildingId=&floorId=&roomNo=")->connect("http://icard.jluzh.com:18001/excard/poscard/mobile/powerCharge/init.ib");
        $cookie                = $http->substr($http->getHeader(), 'Set-Cookie: ', '; Path=/');
        $chargeListInputParams = [
            'terminal' => 'Ios',
            'studentId' => $id,
            'clientId' => $id,
            'pageNum' => 1,
            'access' => 'ajax',
        ];
        $http->header('Cookie', $cookie);
        $chargeListData = $http->post(http_build_query($chargeListInputParams))->connect("http://icard.jluzh.com:18001/excard/poscard/mobile/powerCharge/list.ib");
        $chargeListData = json_decode($chargeListData, true);
        $chargeListData = $chargeListData['list'];
        $data           = "充值记录:\n\n";
        foreach ($chargeListData as $k => $val) {
            if ($k > 4) {
                break;
            }
            $data .= "[" . $this->msecdate('Y-m-d H:i:s', $val['pmlEventdate']) . "]" . $val['pmlThirdName'] . "-购电:" . $val['pmlAmount'] . "元\n";
        }
        $data .= "-----\n<a href='" . $this->getYktUrl('recharge') . "'>点击进入充值电费</a>";
        echo $this->wxapi->response($data);
        die;
    }

    /**
     * score
     */
    function getScore()
    {
        $matches = [];
        list($username, $http, $matches, $cookie) = $this->jwLogin($matches);
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
        $data  = $http->post($data)->connect($url);
        $data  = json_decode($data, true);
        $score = "成绩:\n\n";
        if ($data['items']) {
            foreach ($data['items'] as $k => $val) {
                if ($k > 10) {
                    break;
                }
                $item  = [
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
                $score .= "[{$item['xnmmc']}·{$item['xqmmc']}]" . $item['name'] . "[{$item['score']}]\n";
            }
        }
        $url   = $this->getJwUrl('score') . '&score_ck=' . $cookie;
        $score .= "\n<a href='" . $url . "'>查看更多成绩(五分钟内有效)</a>";

        echo $this->wxapi->response($score);
        die;
    }

    function getTable()
    {
        $cacheTag = false;
        $year     = YEAR;
        $term     = TERM;
        try {
            $cacheFile = new FileCache(array('cacheTime' => 60 * 60 * 24 * 7, 'suffix' => '.php'));
            $data      = $cacheFile->get('table-' . $this->dbdata['username'] . '-' . $year . '-' . $term);
            if ($data) {
                $cacheTag = true;
                $data     = json_decode($data, true);
            } else {
                $matches = [];

                list($username, $http, $matches, $cookie) = $this->jwLogin($matches);
                $data = [
                    'xnm' => $year ? $year : '',
                    'xqm' => $term ? $term : '',
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
                $cacheFile->set('table-' . $this->dbdata['username']. '-' . $year . '-' . $term, json_encode($data));
            }

            $weekArray = ["星期日", "星期一", "星期二", "星期三", "星期四", "星期五", "星期六"];
            $week      = $weekArray[date("w", time())];
            $push      = "今日{$week}课表:\n\n";
            if (isset($data[$week]) && !empty($data[$week])) {
                foreach ($data[$week] as $val) {
                    $push .= "第{$val['number']}节-{$val['name']}-{$val['week']}-{$val['classroom']}\n";
                }
            } else {
                $push .= "无课";
            }
            $url = $this->getJwUrl('table') . '&score_ck=' . $cookie;
            if ($cacheTag) {
                $url = $this->getJwUrl('table');
            }

            $push .= "\n<a href='" . $url . "'>查看更多课表(五分钟内有效)</a>";
        } catch (Exception $e) {
            $push = "出错了:" . $e->getMessage();
        } catch (Throwable $e) {
            $push = "出错了:" . $e->getMessage();
        }

        echo $this->wxapi->response($push);
        die;
    }

    function getAllScore()
    {
        $data   = array();
        $data[] = array('所有成绩', '', '', '');
        echo $this->wxapi->response($data, 'news');
        die;
    }


    // 组装URL
    function getUrl($action, $openid = false)
    {
        if ($openid === false) {
            $openid = $this->wxapi->data['FromUserName'];
        }

        $param = 'openid=' . urlencode($openid) . '&action=' . $action . '&_=' . time() . rand(10000, 99999);
        $url   = get_url() . 'api.php?param=' . urlencode($param);

        return $url;
    }

    function getYktUrl($action, $url = '', $openid = false)
    {
        if ($openid === false) {
            $openid = $this->wxapi->data['FromUserName'];
        }
        $url = get_url() . 'ykt.php?sid=' . $openid . '&act=' . $action . '&msg=' . $url . '&_=' . time() . rand(10000, 99999);
        return $url;
    }

    function getJwUrl($action, $url = '', $openid = false)
    {
        if ($openid === false) {
            $openid = $this->wxapi->data['FromUserName'];
        }
        $url = get_url() . 'jw.php?sid=' . $openid . '&act=' . $action . '&msg=' . $url . '&_=' . time() . rand(10000, 99999);
        return $url;
    }

    function msecdate($tag, $time)
    {
        $a    = substr($time, 0, 10);
        $b    = substr($time, 10);
        $date = date($tag, $a);
        return $date;
    }

    /**
     * @param $matches
     *
     * @return array
     */
    private function jwLogin($matches)
    {
        $username = $this->dbdata['username'];
        $psw      = $this->dbdata['jw_psw'];
        $http     = new http();
        $http->header('Origin', "http://jw.jluzh.com");
        $http->header('Referer', "http://jw.jluzh.com/xtgl/login_slogin.html?language=zh_CN&_t=1565767414867");
        $http->header('User-Agent', "Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A372 Safari/604.1");
        $html = $http->connect("http://jw.jluzh.com/xtgl/login_slogin.html");
        preg_match_all('@Set-Cookie: (.*?) Path@s', $http->getHeader(), $matches);

        $cookie    = implode('', array_reverse($matches[1]));
        $csrfToken = $http->substr($html, '<input type="hidden" id="csrftoken" name="csrftoken" value="', '"/>');
        $http->header('Cookie', $cookie);
        $http->post(['csrftoken' => $csrfToken, 'yhm' => $username, 'mm' => $psw, 'mm' => $psw]);
        $login = $http->connect("http://jw.jluzh.com/xtgl/login_slogin.html?time=1565769460381");
        if (strpos($login, '用户登录') !== false) {
            echo $this->wxapi->response("用户名密码错误 \r\n <a href='" . $this->getYktUrl('bind', urlencode(jwUrl('score'))) . "'>点击进入重新绑定</a>");
        }
        return array($username, $http, $matches, $cookie);
    }

    private function tableFormat($result): array
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
}


return; ?>

http://218.61.108.163
11207228
1111