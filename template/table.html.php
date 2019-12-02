<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>课表 - 正方教务</title>
    <meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=0">
    <link rel="icon" href="data:image/ico;base64,aWNv">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/teg1c/weuiplus@v1.0/css/weui.css"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/teg1c/weuiplus@v1.0/css/weuix.css"/>
    <script src="https://cdn.jsdelivr.net/gh/teg1c/weuiplus@v1.0/js/zepto.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/teg1c/weuiplus@v1.0/js/zepto.weui.js"></script>
    <style>

        .Courses-head {
            background-color: #edffff;
        }

        .Courses-head > div {
            text-align: center;
            font-size: 14px;
            line-height: 28px;
        }

        .left-hand-TextDom, .Courses-head {
            background-color: #f2f6f7;
        }

        .Courses-leftHand {
            background-color: #f2f6f7;
            font-size: 12px;
        }

        .Courses-leftHand .left-hand-index {
            color: #9c9c9c;
            margin-bottom: 4px !important;
        }

        .Courses-leftHand .left-hand-name {
            color: #666;
        }

        .Courses-leftHand p {
            text-align: center;
            font-weight: 900;
        }

        .Courses-head > div {
            border-left: none !important;
        }

        .Courses-leftHand > div {
            padding-top: 5px;
            border-bottom: 1px dashed rgb(219, 219, 219);
        }

        .Courses-leftHand > div:last-child {
            border-bottom: none !important;
        }

        .left-hand-TextDom, .Courses-head {
            border-bottom: 1px solid rgba(0, 0, 0, 0.1) !important;
        }

        .Courses-content > ul {
            border-bottom: 1px dashed rgb(219, 219, 219);
            box-sizing: border-box;
        }

        .Courses-content > ul:last-child {
            border-bottom: none !important;
        }

        .highlight-week {
            color: #02a9f5 !important;
        }

        .Courses-content li {
            text-align: center;
            color: #666666;
            font-size: 14px;
            line-height: 50px;
        }

        .Courses-content li span {
            padding: 6px 2px;
            box-sizing: border-box;
            line-height: 18px;
            border-radius: 4px;
            white-space: normal;
            word-break: break-all;
            cursor: pointer;
        }

        .grid-active {
            z-index: 9999;
        }

        .grid-active span {
            box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>

<body ontouchstart class="page-bg">

<div class="weui-btn_primary weui-header">
    <div class="weui-header-left"><a onclick="getData()" class="icon icon-126 f-white"></a> </div>
    <h1 class="weui-header-title">课表</h1>
<!--    <div class="weui-header-right" onclick="signOut()"><span>退出</span></div>-->
</div>
<div class="weui-msg" id="table-info" style="display: none">
    <div class="weui-msg__icon-area"><i class="weui-icon-warn  weui-icon_msg"></i></div>
    <div class="weui-msg__text-area">
        <h2 class="weui-msg__title">无课表信息</h2>
        <p class="weui-msg__desc">系统获取到您的课表信息为空</p>
    </div>
    <div class="weui-msg__opr-area">
        <p class="weui-btn-area">
            <a href="javascript:;" class="weui-btn weui-btn_primary">确定</a>
        </p>
    </div>
    <div class="weui-msg__extra-area">

    </div>
</div>
<div id="coursesTable" style="padding-bottom: 50px"></div>
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
<script>
    getData();
    $(function () {
        $('.weui-tab').tab({
            defaultIndex: 2,
            activeClass: 'weui-bar__item_on',
            onToggle: function (index) {
            }
        })
    })
    function getData() {
        $('#coursesTable').html('');
        $.showLoading();

        $.post("<?php echo jwUrl('table');?>", {
            type:'table'
        }, function (data, textStatus, xhr) {
            $.hideLoading();
            setTable(data.data.data,data.data.week)

        }, 'json');
    }
    function setTable(courseList,week) {
        console.log(courseList)
        if (courseList.length == 0){
            $('#table-info').show();
            return false;
        }
        var day = new Date().getDay();
        var courseType = [
            [{index: '1'}, 1],
            [{index: '2'}, 1],
            [{index: '3'}, 1],
            [{index: '4'}, 1],
            [{index: '5'}, 1],
            [{index: '6'}, 1],
            [{index: '7'}, 1],
            [{index: '8'}, 1],
            [{index: '9'}, 1],
            [{index: '10'}, 1],
            [{index: '11'}, 1],
            [{index: '12'}, 1]
        ];
        // 实例化(初始化课表)
        var Timetable = new Timetables({
            el: '#coursesTable',
            timetables: courseList,
            week: week,
            timetableType: courseType,
            highlightWeek: day,
            gridOnClick: function (e) {
                alert(e.name + '  ' + e.week + ', 第' + e.index + '节课, 课长' + e.length + '节');
                console.log(e);
            },
            styles: {
                Gheight: 50
            }
        });
    }

    function signOut() {
        $.confirm("您确定要退出吗?", "确认?", function() {
            window.location.href = "{{route('situ.login')}}";
        }, function() {
            //取消操作
        });

    }
</script>
</body>
</html>