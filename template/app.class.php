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
            $http->set(CURLOPT_REFERER, 'http://218.61.108.163/');
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
            header('Content-Type: image/jpg');
            echo $this->http('http://218.61.108.163/ACTIONVALIDATERANDOMPICTURE.APPPROCESS');
            die;
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
        if (in_array($data['Content'], array('解绑', '解除绑定'))) {
            $this->dbdata = array();
            echo $this->wxapi->response("解除绑定成功！如需查询他人请点击【成绩查询】再次绑定。");
            die;

        }

        if (in_array($data['Content'], array('全部成绩', '所有成绩', '毕业成绩'))) {
            $this->dbdata['callback_content'] = $data['Content'];
            $this->checkBind();
            $this->getAllScore();
            die;
        }
        if ($data['Content'] == '绑定') {
            unset($this->dbdata['callback_content']);
            $this->dbdata['event'] = 'input_username';
            echo $this->wxapi->response("请回复您的学号：");
            die;
            die;
        }

        if (strpos($data['Content'], '成绩') !== false) {
            $this->dbdata['callback_content'] = $data['Content'];
            $this->checkBind();
            $this->getScore();
            die;
        }

        if (!empty($this->dbdata['event'])) {
            if ($data['Content'] == '返回' || $data['Content'] == '退出') {
                unset($this->dbdata['callback_content'], $this->dbdata['event']);
                die;
            }

            if ($this->dbdata['event'] == 'input_username') {
                if (empty($data['Content']) || preg_match('@^\d+$@', $data['Content']) == false) {
                    echo $this->wxapi->response("学号输入有误，请重新输入您的学号。");
                    die;
                }
                $this->dbdata['username'] = $data['Content'];
                $this->dbdata['event']    = 'input_password';
                echo $this->wxapi->response("请回复您的密码：");
                die;

            } else if ($this->dbdata['event'] == 'input_password') {
                if (empty($data['Content'])) {
                    echo $this->wxapi->response("密码输入有误，请重新输入您的密码。");
                    die;
                }
                $this->dbdata['password'] = $data['Content'];
                $this->_input_capth();

            } else if ($this->dbdata['event'] == 'input_captch') {

                if (!empty($data['Content'])) {
                    $post              = array();
                    $post['WebUserNO'] = $this->dbdata['username'];
                    $post['Password']  = $this->dbdata['password'];
                    $post['Agnomen']   = $data['Content'];
                    $html              = $this->http('http://218.61.108.163/ACTIONLOGON.APPPROCESS', $post, 'utf-8');

                    if (strpos($html, 'action="ACTIONLOGON.APPPROCESS"')) {
                        if (strpos($html, '请输入正确的附加码')) {
                            $this->_input_capth('验证码错误，请重新输入');
                            die;
                        } else {
                            $this->dbdata['event'] = 'input_username';
                            echo $this->wxapi->response("学号或密码错误。\n请回复您的学号：");
                            die;
                        }

                    } else {
                        // 登陆成功
                        unset($this->dbdata['event']);
                        if (empty($this->dbdata['callback_content'])) {
                            echo $this->wxapi->response("绑定学号成功！");
                            die;

                        } else {
                            $this->wxapi->data['Content'] = $this->dbdata['callback_content'];
                            $this->call_wxapi($this->wxapi);
                        }
                        die;
                    }

                }

                $this->_input_capth();

            }
        }

        unset($this->dbdata['callback_content']);
    }

    function _input_capth($tips = '请回复验证码')
    {
        $this->dbdata['event'] = 'input_captch';
        $img                   = $this->getUrl('captch');
        $data                  = array();
        $data[]                = array($tips, '', $img, '');

        echo $this->wxapi->response($data, 'news');
        die;
    }


    function __destruct()
    {
        file_put_contents($this->dbfile, serialize($this->dbdata));
    }

    function checkBind()
    {
        if (empty($this->dbdata['username']) || empty($this->dbdata['password'])) {
            $this->dbdata['event'] = 'input_username';
            echo $this->wxapi->response("您还没有绑定学号和密码。请回复您的学号：");
            die;
        }
    }

    function getAllScore()
    {
        $html = $this->http('http://218.61.108.163/ACTIONQUERYGRADUATESCHOOLREPORTBYSELF.APPPROCESS', false, 'utf-8');
        $html = str_replace(array('charset=GBK', 'charset=gb2312'), '', $html);

        if (strpos($html, '同学，您还未登录或者登录已过期') || strpos($html, 'ACTIONLOGON.APPPROCESS')) {
            $this->_input_capth();
        }

        $html = str_replace(array('&nbsp;', ' '), '', $html);
        $html = preg_replace('@<td.*?>@', '<td>', $html);

        preg_match_all('@<tr.*?>.*?</tr>@s', $html, $match);
        $match = $match[0];

        $data = array();

        $info   = $match[3] . $match[4];
        $info   = preg_replace('@<.*?>@', '', $info);
        $info   = preg_replace("@[\r\n]+@", "\n", $info);
        $info   = trim($info);
        $data[] = array('毕业成绩（所有成绩）', '', '', '');
        $data[] = array($info, '', '', '');

        unset($match[0], $match[1], $match[2], $match[3], $match[4], $match[5]);

        $list = array();
        foreach ($match as $val) {
            if (preg_match_all('@<td>(.*?)</td>@s', $val, $match)) {
                $match = $match[1];
                if (count($match) != 8 || empty($match[0])) {
                    continue;
                }
                $list[$match[0]][] = implode(' ', array($match[2], $match[6], $match[7]));
            }
        }
        if (empty($list)) {
            $list = array('暂无成绩');
        }
        foreach ($list as $key => $val) {
            $data[] = array($key . "\n" . implode("\n", $val), '', '', '');
        }

        echo $this->wxapi->response($data, 'news');
        die;
    }

    function getScore()
    {
        # 当前学期
        $YearTermNO = 14;

        /*
        $html = $this->http('http://218.61.108.163/ACTIONQUERYSTUDENTSCORE.APPPROCESS');
        if ( preg_match('@value="(\d+)"@',$html,$match) ) {
            $YearTermNO = $match[1];
        }
        */


        if (strpos($this->wxapi->data['Content'], '上上学期') !== false || strpos($this->wxapi->data['Content'], '大上学期') !== false) {
            $YearTermNO--;
            $YearTermNO--;

        } else if (strpos($this->wxapi->data['Content'], '上学期') !== false || strpos($this->wxapi->data['Content'], '上个学期') !== false) {
            $YearTermNO--;
        }

        $post               = array();
        $post['YearTermNO'] = $YearTermNO;

        $html = $this->http('http://218.61.108.163/ACTIONQUERYSTUDENTSCORE.APPPROCESS', $post, 'utf-8');
        $html = str_replace(array('charset=GBK', 'charset=gb2312'), '', $html);

        if (strpos($html, '同学，您还未登录或者登录已过期') || strpos($html, 'ACTIONLOGON.APPPROCESS')) {
            $this->_input_capth();
        }
        $html = str_replace(array('&nbsp;', ' '), '', $html);
        $html = preg_replace('@<td.*?>@', '<td>', $html);

        preg_match_all('@<tr.*?>.*?</tr>@s', $html, $match);
        $match = $match[0];

        $data  = array();
        $_info = $match[1];
        if (preg_match('@selected>(.*?)</option>@', $_info, $_match)) {
            $data[] = $_match[1];
        } else {
            $data[] = '成绩查询';
        }
        if (preg_match('@(学号:\d+)(姓名:.*?)<@', $_info, $_match)) {
            $data[] = $_match[1] . "\n" . $_match[2];
        }

        unset($match[0], $match[1], $match[2]);

        $list = array();
        foreach ($match as $val) {
            if (preg_match_all('@<td>(.*?)</td>@s', $val, $match)) {
                $match = $match[1];
                if (count($match) != 9 || empty($match[0])) {
                    continue;
                }
                $list[] = implode('-', array($match[2], $match[3], $match[8]));
            }
        }
        if (empty($list)) {
            $list = array("额，不好意思！暂未本学期成绩，请等待老师录入吧 \n如成绩不能正常显示，请回复“cj”进入网页版查询。");
        }
        $data[] = implode("\n", $list);

        foreach ($data as $i => $val) {
            $data[$i] = array($val, '', '', '');
        }

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
}


return; ?>

http://218.61.108.163
11207228
1111