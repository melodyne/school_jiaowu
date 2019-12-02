<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>所有成绩 - 正方教务</title>
    <meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=0">
    <link rel="icon" href="data:image/ico;base64,aWNv">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/teg1c/weuiplus@v1.0/css/weui.css"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/teg1c/weuiplus@v1.0/css/weuix.css"/>
    <script src="https://cdn.jsdelivr.net/gh/teg1c/weuiplus@v1.0/js/zepto.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/teg1c/weuiplus@v1.0/js/zepto.weui.js"></script>
</head>

<body ontouchstart class="page-bg">
<div class="weui-btn_primary weui-header">
    <div class="weui-header-left"><a onclick="getData()" class="icon icon-126 f-white"></a> </div>
    <h1 class="weui-header-title">成绩</h1>
<!--    <div class="weui-header-right" onclick="signOut()"><span>退出</span></div>-->
</div>


<div class="weui-cells weui-cells_checkbox"  id="list" style="padding-bottom: 50px">

</div>
<div class="page-bd-15" style="padding-bottom: 80px;display: none" id="showBotton">
    <a href="javascript:;" onclick="gpa()" class="weui-btn weui-btn_primary">手动计算绩点</a>
</div>

<div class="weui-tab tab-bottom " style="height:44px;">

    <div class="weui-tabbar">
        <a href="<?php echo jwUrl('score');?>" class="weui-tabbar__item">
            <i class="icon icon-98 f27 weui-tabbar__icon"></i>
            <p class="weui-tabbar__label">成绩</p>
        </a>

        <a href="<?php echo jwUrl('table');?>" class="weui-tabbar__item">
                    <span style="display: inline-block;position: relative;">
                        <i class="icon icon-89 f27 weui-tabbar__icon"></i>
                    </span>
            <p class="weui-tabbar__label">课表</p>
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/gh/teg1c/weuiplus@v1.0/js/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/gh/teg1c/weuiplus@v1.0/js/php.js"></script>
<script src="//cdn.jsdelivr.net/npm/timetables@1.1.0/index.min.js"></script>
<script id="tpl" type="text/html">
    {{#each data}}

        <div class="weui-cell">
        <div class="weui-cell__bd">
            <p>{{name}}</p>
        </div>
        <div class="weui-cell__ft">{{score}}</div>
        </div>

    {{else}}
    <div class="weui-msg">
        <div class="weui-msg__icon-area"><i class="weui-icon-warn  weui-icon_msg"></i></div>
        <div class="weui-msg__text-area">
            <h2 class="weui-msg__title">无数据</h2>
            <p class="weui-msg__desc">系统获取到您的成绩信息为空</p>
        </div>
        <div class="weui-msg__opr-area">
            <p class="weui-btn-area">
                <a href="javascript:;" class="weui-btn weui-btn_primary">确定</a>
            </p>
        </div>
        <div class="weui-msg__extra-area">

        </div>
    </div>
    {{/each}}

</script>

<script>
    getData();
    $(function () {
        $('.weui-tab').tab({
            defaultIndex: 0,
            activeClass: 'weui-bar__item_on',
            onToggle: function (index) {
                console.log(index)
            }
        })
    })
    function gpa() {
        var xf_array = new Array();
        var jd_array = new Array();
        $(':checked').each(function () {
            xf_array.push($(this).attr('data-xf'));
            jd_array.push($(this).attr('data-jd'));
        });
        if (jd_array == '') {
            $.alert('请选择课程');
            return false;
        }
        $.post("<?php echo jwUrl('table');?>", {xf: xf_array, jd: jd_array}, function (data, textStatus, xhr) {
            if (data == 0) {
                $.alert('请选择课程');
                return false;
            } else {
                $.alert('平均绩点:' + data);
            }
        });
    }
    function getData() {
        $.showLoading();
        $('#list').html('');

        $.post("<?php echo jwUrl('score');?>", {token: 1}, function (data, textStatus, xhr) {
            $.hideLoading();
            var comtpl = $.t7.compile(document.getElementById('tpl').innerHTML);
            document.getElementById('list').innerHTML = comtpl(data.data);
            if (data.data.length > 0){
                $('#showBotton').show();
            }
        },'json');
    }

</script>
</body>
</html>