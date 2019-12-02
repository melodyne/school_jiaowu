<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>绑定</title>
    <meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=0">
    <link rel="icon" href="data:image/ico;base64,aWNv">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/teg1c/weuiplus@v1.0/css/weui.css"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/teg1c/weuiplus@v1.0/css/weuix.css"/>
    <script src="https://cdn.jsdelivr.net/gh/teg1c/weuiplus@v1.0/js/zepto.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/teg1c/weuiplus@v1.0/js/zepto.weui.js"></script>
    <style>
        body {
            max-width: 600px;
            margin: 0 auto;
        }

        .title {
            text-align: center;
            font-size: 24px;
            color: #3cc51f;
            font-weight: 400;
            margin: 0 15%;
        }

        .sub-title {
            text-align: center;
            color: #888;
            font-size: 14px;
        }

        header {
            padding: 25px 0;
        }

        .hide {
            display: none;
        }

        .weui_msg_img {
            background: rgba(0, 0, 0, 0.7) none repeat scroll 0 0;
            height: 100%;
            left: 0;
            display: none;
            position: fixed;
            text-align: center;
            top: 0;
            width: 100%;
            z-index: 999;
        }

        .weui_msg_com {
            border-radius: 10px;
            margin: 70px auto 0;
            position: relative;
            width: 80%;
        }

        .weui_msg_close {
            background: rgba(55, 55, 55, 0.9);
            border-radius: 15px;
            color: #fff;
            cursor: pointer;
            font-weight: bold;
            height: 30px;
            line-height: 30px;
            position: absolute;
            right: -10px;
            top: -10px;
            width: 30px;
            border: 1px solid #FFF;
        }

        .weui_msg_src {
            border-radius: 5px;
            font-size: 0;
            overflow: hidden;
        }

        .weui_msg_src img {
            width: 100%;
        }

        .weui_msg_src p {
            font-size: 18px;
            line-height: 25px;
            margin: 20px auto 0;
            width: 80%;
        }

        .weui_msg_src a {
            background: #f84c2f none repeat scroll 0 0;
            border-radius: 5px;
            color: #fff;
            display: block;
            font-size: 14px;
            height: 35px;
            line-height: 35px;
            margin: 20px auto;
            text-align: center;
            width: 100px;
        }

        .weui_msg_comment {
            background: #fff none repeat scroll 0 0;
            border-radius: 5px;
            color: #ff0000;
            padding: 0 0 10px;
        }
    </style>
</head>

<body>
<header class='header'>
    <h1 class="title">绑定</h1>
</header>

<div class="weui-cells__title">完善下方信息</div>
<div class="weui-cells weui-cells_form">
    <div class="weui-cell">
        <div class="weui-cell__hd"><label class="weui-label">学号</label></div>
        <div class="weui-cell__bd">
            <input class="weui-input" id="username" value="<?php echo @$userInfo['username']?>" pattern="[0-9]*" placeholder="请输入学号" type="number">
        </div>
    </div>
    <div class="weui-cell">
        <div class="weui-cell__hd"><label class="weui-label">教务密码</label></div>
        <div class="weui-cell__bd">
            <input class="weui-input" id="jw_psw" value="<?php echo @$userInfo['jw_psw']?>" placeholder="请输入教务密码" type="text">
        </div>
    </div>
    <div class="weui-cell">
        <div class="weui-cell__hd"><label class="weui-label">一卡通密码</label></div>
        <div class="weui-cell__bd">
            <input class="weui-input" id="ykt_psw" value="<?php echo @$userInfo['ykt_psw']?>" placeholder="请输入教务一卡通密码" type="text">
        </div>
    </div>
</div>

<div class="weui-btn-area">
    <a class="weui-btn weui-btn_primary" href="javascript:" id="btn">绑定</a>
</div>


<div class="loading2 hide" data-text="登录中..."></div>
<script src="https://cdn.jsdelivr.net/gh/teg1c/weuiplus@v1.0/js/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/gh/teg1c/weuiplus@v1.0/js/php.js"></script>
<script>
    var url = "<?php echo $redirectUrl;?>"
    $(function () {
        $('#btn').click(function () {
            var username = $('#username').val();
            var jw_psw = $('#jw_psw').val();
            var ykt_psw = $('#ykt_psw').val();
            if (!username || !jw_psw ||!ykt_psw){
                $.toptip('信息填写完整');
                return false;
            }
            $.showLoading();
            $.post("<?php echo url('bind')?>", {
                username: username,
                jw_psw: jw_psw,
                ykt_psw: ykt_psw
            }, function (data, textStatus, xhr) {
                $.hideLoading();
                if (data.status == 0) {
                    $.toptip(data.msg);
                    return false;
                }
                $.toast('绑定成功');
                if (url){
                    setTimeout("window.location.href = url", 200);
                }else{
                    setTimeout("window.location.href = \"<?php echo url('recharge')?>\"", 200);
                }

            }, 'json');
        })
    })
</script>
</body>
</html>
