<?php

class FileCache{

    private $cacheTime = 3600;        //默认缓存时间
    private $cacheDir = './temp';    //缓存绝对路径
    private $md5 = true;              //是否对键进行加密
    private $suffix = ".php";         //设置文件后缀

    public function __construct($config){
        if( is_array( $config ) ){
            foreach( $config as $key=>$val ){
                $this->$key = $val;
            }
        }
    }

    //设置缓存
    public function set($key,$val,$leftTime=null){
        $key = $this->md5 ? md5($key) : $key;
        $leftTime = $leftTime ? $leftTime : $this->cacheTime;
        !file_exists($this->cacheDir) && mkdir($this->cacheDir,0777);
        $file = $this->cacheDir.'/'.$key.$this->suffix;
        $val = serialize($val);
        @file_put_contents($file,$val) or $this->error(__line__,"文件写入失败");
        @chmod($file,0777)  or $this->error(__line__,"设定文件权限失败");
        @touch($file,time()+$leftTime) or $this->error(__line__,"更改文件时间失败");
    }

    //得到缓存
    public function get($key){
        $this->clear();
        if( $this->_isset($key) ){
            $key_md5 = $this->md5 ? md5($key) : $key;
            $file = $this->cacheDir.'/'.$key_md5.$this->suffix;
            $val = file_get_contents($file);
            return unserialize($val);
        }
        return null;
    }

    //判断文件是否有效
    public function _isset($key){
        $key = $this->md5 ? md5($key) : $key;
        $file = $this->cacheDir.'/'.$key.$this->suffix;
        if( file_exists($file) ){
            if( @filemtime($file) >= time() ){
                return true;
            }else{
                @unlink($file);
                return false;
            }
        }
        return false;
    }

    //删除文件
    public function _unset($key){
        if( $this->_isset($key) ){
            $key_md5 = $this->md5 ? md5($key) : $key;
            $file = $this->cacheDir.'/'.$key_md5.$this->suffix;
            return @unlink($file);
        }
        return false;
    }

    //清除过期缓存文件
    public function clear(){
        $files = scandir($this->cacheDir);
        foreach ($files as $val){
            if (@filemtime($this->cacheDir."/".$val) < time()){
                @unlink($this->cacheDir."/".$val);
            }
        }
    }

    //清除所有缓存文件
    public function clear_all(){
        $files = scandir($this->cacheDir);
        foreach ($files as $val){
            @unlink($this->cacheDir."/".$val);
        }
    }

    private function error($line,$msg){
        throw new Exception("出错文件：".__file__."/n出错行：$line/n错误信息：$msg");
    }
}

?>