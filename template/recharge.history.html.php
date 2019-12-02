
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>电费充值记录</title>
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
    <h1 class="title">电费充值记录</h1>
</header>
<div class="weui-cells__title">详细数据 <a
            href="<?php echo url('recharge');?>"
            style="float: right;">充电</a>
</div>

<div class="page-bd">
    <div class="weui-panel">
        <div class="weui-panel__bd" id="content">

        </div>
    </div>
</div>
<div class="page-bd-15">
    <a href="javascript:;" id="load_mode" class="weui-btn weui-btn_block weui-btn_primary">加载更多</a>

</div>
<script src="https://cdn.jsdelivr.net/gh/teg1c/weuiplus@v1.0/js/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/gh/teg1c/weuiplus@v1.0/js/php.js"></script>
<script>
    var ck = "<?php echo $cookie;?>";
    var page = 1;
    getData({
        page: page,
        ck: ck,
    });
    $(function () {

        $('#load_mode').click(function () {
            var data = {
                page: page,
                ck: ck,
            };
            getData(data)
        });
    });

    function getData(postData) {
        $.showLoading();
        $.post("<?php echo url('recharge_history');?>", postData, function (data, textStatus, xhr) {
            $.hideLoading();
            if (data.totalPage < postData.page) {
                $.toptip('暂无更多数据');
                return false;
            }
            if (data.totalPage == postData.page) {
                $('#load_mode').text('暂无数据');
            }
            $.each(data.list, function (i, item) {
                addEle($('#content'), item)
            })
            page++;
        }, 'json');
    }

    function addEle(ele, data) {
        console.log(data)
        var optionStr = "";
        optionStr = '<div class="weui-media-box weui-media-box_text">\n' +
            '                <h4 class="weui-media-box__title">' + data.pmlThirdName + '</h4>\n' +
            '                <p class="weui-media-box__desc">购电金额:' + data.pmlAmount + ' 元</p>\n' +
            '                <ul class="weui-media-box__info">\n' +
            '                    <li class="weui-media-box__info__meta">' + data.pmlAccName + '</li>\n' +
            '                    <li class="weui-media-box__info__meta">' + new Date(data.pmlEventdate).toLocaleString() + '</li>\n' +
            '                    <li class="weui-media-box__info__meta weui-media-box__info__meta_extra">' + data.pmlReserve2 + '</li>\n' +
            '                </ul>\n' +
            '            </div>';
        ele.append(optionStr);
    }

    function getLocalTime(nS) {
        return new Date(parseInt(nS) * 1000).toLocaleString().replace(/年|月/g, "-").replace(/日/g, " ");
    }
</script>
</body>
</html>
