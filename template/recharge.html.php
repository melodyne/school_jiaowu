<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>充值电费</title>
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
    <h1 class="title">充值电费</h1>
</header>

<div class="weui-cells__title">一卡通余额:<? echo $balance; ?> <a href="<?php echo url('recharge_history').'&ck='.$cookie;?>"" style="float: right;">充值记录</a></div>
<form>
    <div class="weui-cells weui-cells_form">
        <div class="weui-cell">
            <div class="weui-cell__hd"><label class="weui-label">楼</label></div>
            <div class="weui-cell__bd">
                <select class="weui-select" name="buildingId" id="building">
                    <option value="">请选择</option>
                    <?php foreach ($getBuilding['result']['value'] as $val): ?>
                        <option value="<?php echo $val['buildingId'] ?>" ><?php echo $val['buildingName'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="weui-cell">
            <div class="weui-cell__hd"><label class="weui-label">层</label></div>
            <div class="weui-cell__bd">
                <select class="weui-select" id="floor" name="floorId">

                </select>
            </div>
        </div>
        <div class="weui-cell">
            <div class="weui-cell__hd"><label class="weui-label">房间号</label></div>
            <div class="weui-cell__bd">
                <select class="weui-select" id="room" name="roomNo">

                </select>
            </div>
        </div>
        <div class="weui-cell">
            <div class="weui-cell__hd"><label class="weui-label">金额</label></div>
            <div class="weui-cell__bd">
                <input class="weui-input" type="number" pattern="[0-9]*" name="money" placeholder="请输入充值金额">
            </div>
        </div>
        <input type="hidden" name="studentId" value="<?php echo $userInfo['username']; ?>">
        <input type="hidden" name="clientId" value="<?php echo $userInfo['username']; ?>">
        <input type="hidden" name="ck" value="<?php echo $cookie ?>">
        <input type="hidden" name="dormId" value="1">

    </div>
</form>
<div class="page-bd-15">
    <div class="button-sp-area" style="padding-top: 20px">
        <a href="javascript:;" id="sub" class="weui-btn weui-btn_block weui-btn_primary">充值</a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/gh/teg1c/weuiplus@v1.0/js/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/gh/teg1c/weuiplus@v1.0/js/php.js"></script>
<script>
    var buildObj = $("#building"),
        floorObj = $("#floor"),
        roomObj = $("#room");
    var ck = "<?php echo $cookie;?>";
    var buildText, floorText, roomItem;
    $(function () {
        $('#sub').click(function () {
            $.showLoading();
            var data = $("form").serialize();
            $.post("<?php echo url('recharge');?>", data, function (data, textStatus, xhr) {
                $.hideLoading();
                if (data.status == 0) {
                    $.toptip(data.msg);
                    return false;
                }
                $.toast('充值成功');
                setTimeout("window.location.reload()", 200);
            }, 'json');
        });
    })
    buildObj.on("change", function () {
        buildText = $(this).val();
        console.log(buildText);
        removeEle(floorObj);
        removeEle(roomObj);
        $.showLoading();
        $.post("<?php echo url('getBuild');?>", {
            type: 'floor',
            buildId: $(this).val(),
            ck: ck
        }, function (data, textStatus, xhr) {
            console.log(data);
            $.hideLoading();
            $.each(data.result.value, function (i, item) {
                addEle(floorObj, item.floorId, item.floorName)
            })
        }, 'json');


    });
    floorObj.on("change", function () {
        removeEle(roomObj);
        $.showLoading();
        $.post("<?php echo url('getBuild');?>", {
            type: 'room',
            floorId: $(this).val(),
            buildId: buildObj.val(),
            ck: ck
        }, function (data, textStatus, xhr) {
            console.log(data);
            $.hideLoading();
            $.each(data.result.value, function (i, item) {
                addEle(roomObj, item.roomNo, item.roomName)
            })
        }, 'json');
    });

    function addEle(ele, key, value) {
        var optionStr = "";
        optionStr = "<option value=" + key + ">" + value + "</option>";
        ele.append(optionStr);
    }

    function removeEle(ele) {
        ele.find("option").remove();
        var optionStar = "<option value=\"\">请选择</option>";
        ele.append(optionStar);
    }
</script>
</body>
</html>
