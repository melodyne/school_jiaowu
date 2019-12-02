<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>一卡通交易记录</title>
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
    <h1 class="title">一卡通交易记录</h1>
</header>
<div class="weui-cells__title">一卡通余额:<? echo $balance; ?> <a href="<?php echo url('recharge')?>"
                                                                                  style="float: right;">充值电费</a>
</div>


<div class="weui-cells weui-cells_form">
    <div class="weui-cell">
        <div class="weui-cell__hd"><label for="time-format" class="weui-label">开始</label></div>
        <div class="weui-cell__bd">
            <input class="weui-input" id="start" type="text" value="<?php echo date('Y-m-d', strtotime("-7 day")); ?>">
        </div>
    </div>
    <div class="weui-cell">
        <div class="weui-cell__hd"><label for="time-format" class="weui-label">结束</label></div>
        <div class="weui-cell__bd">
            <input class="weui-input" id="end" type="text" value="<?php echo date('Y-m-d'); ?>">
        </div>
    </div>

</div>
<div class="page-bd-15">
    <div class="button-sp-area" style="padding-top: 20px">
        <a href="javascript:;" id="sub" class="weui-btn weui-btn_block weui-btn_primary">查询</a>
    </div>
</div>
<div class="page-bd">
    <div class="weui-cells__title" id="count"></div>
    <div class="weui-cells" id="content" style="font-size: 9px">
    </div>
</div>

<script src="https://cdn.jsdelivr.net/gh/teg1c/weuiplus@v1.0/js/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/gh/teg1c/weuiplus@v1.0/js/php.js"></script>
<script>
    $("#start").datetimePicker({
        title: 'start',
        years: range(1940, 2030),
        times: function () {
            return [];
        },
        parse: function (str) {
            return str.split("-");
        },
        onChange: function (picker, values, displayValues) {
            console.log(values);
        }
    });
    $("#end").datetimePicker({
        title: 'start',
        years: range(1940, 2030),
        times: function () {
            return [];
        },
        parse: function (str) {
            return str.split("-");
        },
        onChange: function (picker, values, displayValues) {
            console.log(values);
        }
    });

    window.onload =function(){
        $('#sub').trigger("click");
    }
    $(function () {
        var ck = "<?php echo $cookie;?>";
        $('#sub').click(function () {
            $.showLoading();
            var data = {
                start: $('#start').val(),
                end: $('#end').val(),
                ck: ck,
            };
            $('#content').html('');
            $('#count').text('');
            $.post("<?php echo url('balance');?>", data, function (data, textStatus, xhr) {
                $.hideLoading();
                if (data.resultCode != '0000') {
                    $.toptip('错误');
                    return false;
                }
                if (data.total == 0) {
                    $.toptip('一共 0 条数据');
                    return false;
                }
                $('#count').text("一共 " + data.total + " 条数据")
                console.log(data)

                $.each(data.value, function (i, item) {
                    addEle($('#content'), item)
                })
            }, 'json');
        });
    });

    function addEle(ele, data) {
        var optionStr = "";
        optionStr = ' <div class="weui-cell">\n' +
            '            <div class="weui-cell__bd">\n' +
            '                <p>' + data.TradeBranchName + '[' + data.ConsumeTime + ']</p>\n' +
            '            </div>\n' +
            '            <div class="weui-cell__ft">' + data.GeneralOperateTypeName + ' ' + data.ConsumeAmount + ' 元</div>\n' +
            '        </div>';
        ele.append(optionStr);
    }
</script>
</body>
</html>
