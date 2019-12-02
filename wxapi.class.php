<?php


class wxapi
{
    # 编码
    private $charset;

    # 收到的内容
    public $data;

    # 获取到的原生数据
    private $_input;

    # 编码转换，类似于discuz里的diconv
    private function iconv($txt, $in_charset, $out_charset)
    {
        if ($in_charset != $out_charset) {
            if (function_exists('diconv')) {
                $txt = diconv($txt, $in_charset, $out_charset);

            } else if (function_exists('iconv')) {
                $txt = iconv($in_charset, $out_charset . '//IGNORE', $txt);

            } else if (function_exists('mb_convert_encoding')) {
                $txt = mb_convert_encoding($txt, $out_charset, $in_charset);

            }
        }
        return $txt;
    }

    public function __construct($charset = 'utf-8')
    {
        $this->charset = strtolower($charset);

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->data   = array();
            $this->_input = file_get_contents('php://input');
            $xml          = new SimpleXMLElement($this->_input);
            $xml || exit;
            foreach ($xml as $key => $value) {
                $this->data[$key] = strval($value);
            }
            if (isset($this->data['Content'])) {
                $this->data['Content'] = $this->iconv($this->data['Content'], 'utf-8', $this->charset);
            }
        }
    }

    # 验证Token
    public function valid($token)
    {

        $data = array($_GET['timestamp'], $_GET['nonce'], $token);
        sort($data, SORT_STRING);
        $signature = sha1(implode($data));
        if ($signature === $_GET['signature']) {
            if (isset($_GET['echostr']) && strtolower($_SERVER['REQUEST_METHOD']) == 'get') {
                ob_start();
                ob_clean();
                echo $_GET['echostr'];
                die;
            }
        } else {
            die('Access Denied');
        }
    }

    # 回复内容
    public function response($content, $type = 'text', $flag = 0)
    {
        $this->data = array(
            'ToUserName' => $this->data['FromUserName'],
            'FromUserName' => $this->data['ToUserName'],
            'CreateTime' => time(),
            'MsgType' => $type,
        );
        $this->$type($content);
        //$this -> data['FuncFlag'] = $flag;
        $xml = new SimpleXMLElement('<xml></xml>');
        $this->data2xml($xml, $this->data);
        $ret = $xml->asXML();
        $ret = $this->iconv($ret, $this->charset, 'utf-8');
        return $ret;
    }

    private function text($content)
    {
        $this->data['Content'] = $content;
    }

    private function music($music)
    {
        list($music['Title'], $music['Description'], $music['MusicUrl'], $music['HQMusicUrl']) = $music;
        $this->data['Music'] = $music;
    }

    private function news($news)
    {
        $articles = array();
        foreach ($news as $key => $value) {
            list($articles[$key]['Title'], $articles[$key]['Description'], $articles[$key]['PicUrl'], $articles[$key]['Url']) = $value;
            if ($key >= 9) {
                break;
            }
        }
        $this->data['ArticleCount'] = count($articles);
        $this->data['Articles']     = $articles;
    }

    private function data2xml($xml, $data, $item = 'item')
    {
        foreach ($data as $key => $value) {
            is_numeric($key) && $key = $item;
            if (is_array($value) || is_object($value)) {
                $child = $xml->addChild($key);
                $this->data2xml($child, $value, $item);
            } else {
                if (is_numeric($value)) {
                    $child = $xml->addChild($key, $value);
                } else {
                    $child = $xml->addChild($key);
                    $node  = dom_import_simplexml($child);
                    $node->appendChild($node->ownerDocument->createCDATASection($value));
                }
            }
        }
    }

    # 转发到另外的接口去
    public function call_third_api($url, $token)
    {
        if (!$url) {
            return '';
        }
        if (strpos($url, '?')) {
            $url .= '&';
        } else {
            $url .= '?';
        }

        $timestamp = $nonce = time();

        $signkey = array($token, $timestamp, $nonce);
        sort($signkey, SORT_STRING);
        $signString = implode($signkey);
        $signString = sha1($signString);

        $url .= 'timestamp=' . $timestamp . '&nonce=' . $timestamp . '&signature=' . $signString;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: text/xml'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_input);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            print curl_error($ch);
        }
        curl_close($ch);
        return $response;
    }
}
