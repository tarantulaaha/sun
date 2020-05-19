<?php
header('Content-Type: text/html; charset= utf-8');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(-1);
require_once './simple_html_dom.php';
?>
<html>
    <head>

        <meta charset="UTF-8">
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <link rel="stylesheet" type="text/css" href="https://yastatic.net/weather-frontend/3.14.4/desktop.bundles/index/_index.css" />

        <title></title>
        <style>
            body {font-family: sans-serif;font-size: 14px; margin:0; padding:0; text-align: center;}
            table{border-spacing: 0px;margin: 0px auto;margin-top: 5px;margin-bottom: 5px;}
            td{border: 1px black solid;padding: 2px;}
            input,button,select{font-size: 16px;border-radius: 5px;border: 1px black ;margin: 2px;padding: 2px;}
            button{
                padding: 10px;
            }
            input[type="text"]{
                border:1px black solid;
            }
            input[type="checkbox"]{
                height:25px;
                width:25px;
                top: 8px;
                position:relative;
            }
            input:disabled{
                color:#CECECE;
            }

            button:active{
                filter: progid:DXImageTransform.Microsoft.gradient(startColorstr = '#999999', endColorstr = '#ffffff');
                /*INNER ELEMENTS MUST NOT BREAK THIS ELEMENTS BOUNDARIES*/
                /*Element must have a height (not auto)*/
                /*All filters must be placed together*/
                -ms-filter: "progid:DXImageTransform.Microsoft.gradient(startColorstr = '#999999', endColorstr = '#ffffff')";
                /*Element must have a height (not auto)*/
                /*All filters must be placed together*/
                background-image: -moz-linear-gradient(top, #999999, #ffffff);
                background-image: -ms-linear-gradient(top, #999999, #ffffff);
                background-image: -o-linear-gradient(top, #999999, #ffffff);
                background-image: -webkit-gradient(linear, center top, center bottom, from(#999999), to(#ffffff));
                background-image: -webkit-linear-gradient(top, #999999, #ffffff);
                background-image: linear-gradient(top, #999999, #ffffff);
                /*--IE9 DOES NOT SUPPORT CSS3 GRADIENT BACKGROUNDS--*/

            }
            .border{border: 1px solid black; border-radius: 5px;padding: 5px;margin: 5px;}
            .hasDatepicker{width: 125px;}
            #lat,#long{width: 150px;}
            .otstup{padding: 4px;padding-bottom: 6px;}
            button,select{
                filter: progid:DXImageTransform.Microsoft.gradient(startColorstr = '#ffffff', endColorstr = '#c4c4c4');
                /*INNER ELEMENTS MUST NOT BREAK THIS ELEMENTS BOUNDARIES*/
                /*Element must have a height (not auto)*/
                /*All filters must be placed together*/
                -ms-filter: "progid:DXImageTransform.Microsoft.gradient(startColorstr = '#ffffff', endColorstr = '#c4c4c4')";
                /*Element must have a height (not auto)*/
                /*All filters must be placed together*/
                background-image: -moz-linear-gradient(top, #ffffff, #c4c4c4);
                background-image: -ms-linear-gradient(top, #ffffff, #c4c4c4);
                background-image: -o-linear-gradient(top, #ffffff, #c4c4c4);
                background-image: -webkit-gradient(linear, center top, center bottom, from(#ffffff), to(#c4c4c4));
                background-image: -webkit-linear-gradient(top, #ffffff, #c4c4c4);
                background-image: linear-gradient(top, #ffffff, #c4c4c4);
                /*--IE9 DOES NOT SUPPORT CSS3 GRADIENT BACKGROUNDS--*/

            }
            .current-weather{
                text-align: left!important;
            }
            .forecasts{
                margin-top: 0px;
                width: 1000px;
                margin: 0 auto;
                overflow-y: scroll;
                height: 350px;
            }
        </style>
    </head>
    <body>

        <link href="jqueryui/css/base/jqueryui/css/base/jquery-ui.css" rel="stylesheet" type="text/css"/>
        <script src="jqueryui/css/base/jquery/jquery.js" type="text/javascript"></script>
        <script src="jqueryui/css/base/jqueryui/jquery-ui.js" type="text/javascript"></script>
        <script type="text/javascript" src="//api-maps.yandex.ru/2.0/?load=package.standard&lang=ru-RU" ></script>

        <script type="text/javascript">
            $(function () {
                $("#datepicker").datepicker({changeMonth: true,
                    changeYear: true}).datepicker("setDate", new Date()).change(getValues);
                $("#datepickerCorrection").datepicker({changeMonth: true,
                    changeYear: true}).datepicker("setDate", new Date()).change(getValues);
                startTime();
                for (var i = 0; i <= 59; i++) {
                    $("#minuteCorrection").append("<option value='" + i + "')>" + i + "</option>");
                }
                for (var i = 0; i <= 23; i++) {
                    $("#hourCorrection").append("<option value='" + i + "'>" + i + "</option>");
                }
                $('#filtrShirota').attr('disabled', 'disabled');
                $('#filtrDolgota').attr('disabled', 'disabled');
                $('#filtrShirotaCheck').change(function () {
                    if ($(this).is(":checked")) {
                        $('#filtrShirota').removeAttr('disabled');
                    } else {
                        $('#filtrShirota').attr('disabled', 'disabled');
                    }
                });
                $('#filtrDolgotaCheck').change(function () {
                    if ($(this).is(":checked")) {
                        $('#filtrDolgota').removeAttr('disabled');
                    } else {
                        $('#filtrDolgota').attr('disabled', 'disabled');
                    }
                });

                $('#filterFind').click(function () {
                    var options = {
                        cmd: "getFiltered",
                        val: $('#filtrSootnosheniye').val()
                    };
                    if ($('#filtrShirotaCheck').is(":checked")) {
                        options.lat = $('#filtrShirota').val();
                    } else {
                        options.lat = ''
                    }
                    if ($('#filtrDolgotaCheck').is(":checked")) {
                        options.long = $('#filtrDolgota').val();
                    } else {
                        options.long = '';
                    }
                    $("#filterFind").text("Идет поиск...");
                    $("#filterTable tbody").text("");
                    $("#filterTable").hide();
                    $.post("srs2.php", options, function (data) {
                        table = JSON.parse(data);
                        $("#filterTable").show();
                        $("#filterFind").text("Найти");
                        $.each(table, function (p, val) {
                            row = $("#filterTable tbody").append("<tr></tr>");
                            row.append("<td>" + val[0] + "</td>");
                            row.append("<td>" + val[1] + "</td>");
                            row.append("<td>" + val[2] + "</td>");
                            row.append("<td>" + val[3] + "</td>");
                            row.append("<td>" + val[4] + "</td>");
                            row.append("<td>" + val[5] + "</td>");
                            row.append("<td>" + val[6] + "</td>");

                        });
                        if (Object.keys(table).length === 0) {
                            row = $("#filterTable tbody").append("<tr></tr>");
                            row.append("<td colspan='7' >Ничего не найдено.</td>");
                        }
                    });
                });
            });
            ymaps.ready(init);
            var myMap;
            var lat, long, times, coords, date, day, night, riseMinutes, setMinutes, nod, day_night, rise, set;
            var g_date, u_date, u_diff;
            long = '36.6100600';
            lat = '55.0968100';


            function startTime()
            {

                $.post("srs2.php", {cmd: "getGreenwichTime"}, function (data) {
                    times = JSON.parse(data);
                    var tmt = new Date(times.GMT * 1000);
                    var tm = new Date(tmt.valueOf() + tmt.getTimezoneOffset() * 60000);
                    var d = tm.getDate();
                    var mn = tm.getMonth() + 1;
                    var y = tm.getFullYear();
                    var h = tm.getHours();
                    var m = tm.getMinutes();
                    var s = tm.getSeconds();
                    d = checkTime(d);
                    mn = checkTime(mn);
                    y = checkTime(y);
                    h = checkTime(h);
                    m = checkTime(m);
                    s = checkTime(s);
                    g_date = tm;
                    $("#greenwichDate").text(d + "." + mn + "." + y + " " + h + ":" + m + ":" + s);
                    lt = new Date();
                    $("#gmt").text(tmt.getTimezoneOffset() / 60 * -1);
                });
                //$("#greenwichDate").text(d + "." + mn + "." + y + " " + h + ":" + m + ":" + s);
                //document.getElementById('txt').innerHTML = h + ":" + m + ":" + s;

                t = setTimeout('startTime()', 1000);
            }
            function dateTimeToUnix() {
                var result = new Date($('#datepickerCorrection').val());
                result.setHours($("#hourCorrection").val());
                result.setMinutes(0);
                result = result.valueOf();
                return result;
            }
            function checkTime(i) {
                if (i < 10) {
                    i = "0" + i;
                }
                return i;
            }
            function getValues() {
                $('#lat').val(lat);
                $('#long').val(long);
                $('#filtrDolgota').val(long);
                $('#filtrShirota').val(lat);
                date = (new Date($('#datepicker').val()).setHours(12).valueOf());
                udt = dateTimeToUnix();
                diff = Math.round((udt / 3600000 - Math.floor(g_date / 3600000))) * 3600;
                qtime = (new Date($('#datepicker').val()).setHours($("#hourCorrection").val()).valueOf());
                $.post("srs2.php", {
                    cmd: 'getSRS',
                    lat: lat,
                    long: long,
                    time: qtime,
                    diff: diff
                }, function (data) {
                    times = JSON.parse(data);
                    $('#t1 #day').text(times.day);
                    $('#t1 #night').text(times.night);
                    $('#t1 #day_night').text(times.day_night);
                    // $('#t1 #day_night2').text(times.day_night_real);
                    $('#t1 #sr_sumerki_g').text(times.sr_sumerki_g);
                    $('#t1 #sr_sumerki_n').text(times.sr_sumerki_n);
                    $('#t1 #sr_sumerki_a').text(times.sr_sumerki_a);

                    $('#t1 #rise').text(times.sunRise);
                    $('#t1 #zenit').text(times.sunZenit);
                    $('#t1 #set').text(times.sunSet);
                    $('#t1 #ss_sumerki_g').text(times.ss_sumerki_g);
                    $('#t1 #ss_sumerki_n').text(times.ss_sumerki_n);
                    $('#t1 #ss_sumerki_a').text(times.ss_sumerki_a);
                    $('#t1 #moonRise').text(times.moonRise);
                    $('#t1 #moonZenit').text(times.moonZenit);
                    $('#t1 #moonSet').text(times.moonSet);
                    $('#t1 #moonVisible').text(times.moonVisible);
                    $('#t1 #moonInvisible').text(times.moonInvisible);
                    $('#t1 #visible_invisible_real').text(times.visible_invisible_real);
                    $('#t1 #visible_invisible_nod').text(times.visible_invisible_nod);
                    //$('#t1 #sun_Zenit_Zenit').text(times.sun_Zenit_Zenit);

                });
                $.post("srs2.php", {
                    cmd: 'getT3',
                    lat: lat,
                    long: long,
                    time: qtime,
                    diff: diff
                }, function (data) {
                    times = JSON.parse(data);
                    $('#t3 #day').text(times.day);
                    $('#t3 #night').text(times.night);
                    $('#t3 #day_night').text(times.day_night);
                    $('#t3 #day_night2').text(times.day_night_real);
                    $('#t3 #rise').text(times.sunRise);
                    $('#t3 #zenit').text(times.sunZenit);
                    $('#t3 #set').text(times.sunSet);
                });
            }

            function getByDegrees() {
                vdate = (new Date("01/01/15")).valueOf() + (1000 * 60 * 60 * 12);
                for (var startDate = vdate; startDate <= (vdate + (365 * 24 * 60 * 60 * 1000)); startDate += 24 * 60 * 60 * 1000) {
                    date = new Date(startDate);
                    tdate = date.getDate() + '.' + (date.getMonth() + 1) + '.' + date.getFullYear();
                    for (var i = 0; i <= 90; i++) {
                        var row = $("#tablica tbody").append('<tr/>');
                        times = SunCalc.getTimes(date, i, long);
                        var dayl = getDay(times.sunset, times.sunrise);
                        var nightl = (24 * 60) - dayl;
                        nod = NOD([dayl, nightl]);
                        var dnl = (dayl / nod) + '/' + (nightl / nod);
                        var dnl2 = ((dayl / nod) / (nightl / nod)).toFixed(4);
                        row.append("<td>" + tdate + "</td>");
                        row.append("<td>" + i + "</td>");
                        row.append("<td>" + long + "</td>");
                        row.append("<td>" + getGreenvichTime(times.sunrise) + "</td>");
                        row.append("<td>" + getGreenvichTime(times.solarNoon) + "</td>");
                        row.append("<td>" + getGreenvichTime(times.sunset) + "</td>");
                        row.append("<td>" + dayl + "</td>");
                        row.append("<td>" + nightl + "</td>");
                        row.append("<td>" + dnl + "</td>");
                        row.append("<td>" + dnl2 + "</td>");
                    }
                }
            }

            function getTableFromServer() {
                udt = dateTimeToUnix();
                diff = Math.round((udt / 3600000 - Math.floor(g_date / 3600000))) * 3600;
                $.download("srs2.php", {cmd: 'getTable', long: long, diff: diff});

            }
            function getZenitTableFromServer() {
                udt = dateTimeToUnix();
                diff = Math.round((udt / 3600000 - Math.floor(g_date / 3600000))) * 3600;
                $.post("srs2.php", {cmd: 'getZenit', lat: lat, long: long, diff: diff}, function (data) {
                    table = JSON.parse(data);
                    $("#zenitTable tbody").text("");
                    $.each(table, function (p, val) {
                        row = $("#zenitTable tbody").append("<tr></tr>");
                        row.append("<td>" + val.sunRise + "</td>");
                        row.append("<td>" + val.sunZenit + "</td>");
                        row.append("<td>" + val.sunSet + "</td>");
                        row.append("<td>" + val.day + "</td>");
                        row.append("<td>" + val.night + "</td>");
                        row.append("<td>" + val.day_night + "</td>");
                        row.append("<td>" + val.day_night_real + "</td>");
                        row.append("<td>" + val.moonRise + "</td>");
                        row.append("<td>" + val.moonZenit + "</td>");
                        row.append("<td>" + val.moonSet + "</td>");
                        row.append("<td>" + val.moonVisible + "</td>");
                        row.append("<td>" + val.moonInvisible + "</td>");
                        row.append("<td>" + val.visible_invisible_real + "</td>");
                        row.append("<td>" + val.visible_invisible_nod + "</td>");
                        //row.append("<td>" + val.sun_Zenit_Zenit + "</td>");
                    });
                });
            }
            function downloadZenitTableFromServer() {
                udt = dateTimeToUnix();
                diff = Math.round((udt / 3600000 - Math.floor(g_date / 3600000))) * 3600;
                $.download("srs2.php", {cmd: 'getZenitTable', long: long, lat: lat, diff: diff});
            }

            function init()
            {
                myMap = new ymaps.Map("map",
                        {
                            center: [lat, long],
                            zoom: 10,
                            behaviors: ["default", "scrollZoom"]
                        },
                {
                    balloonMaxWidth: 300
                });
                myMap.events.add("click", function (e)
                {
                    coords = e.get("coordPosition");
                    lat = coords[0].toPrecision(10);
                    long = coords[1].toPrecision(10);
                    getValues();
                });
                myMap.controls.add("searchControl");
                myMap.controls.add("zoomControl");
                myMap.controls.add("mapTools");
                myMap.controls.add("typeSelector");
            }
            $(function () {

                $('#lat').change(function () {
                    lat = $('#lat').val();
                });
                $('#long').change(function () {
                    long = $('#long').val();
                });
                $('#calculate').click(function () {
                    getValues();
                });
                $('#showTable').click(function () {
                    $('#tablica tbody').html('');
                    //getByDegrees();
                    getTableFromServer();
                });
                $('#showZenitTable').click(function () {
                    $('#zenitTablica tbody').html('');
                    getZenitTableFromServer();
                });
                $('#downloadZenitTable').click(function () {
                    downloadZenitTableFromServer();
                });
                $('#hideTable').click(function () {
                    $('#tablica tbody').html('');
                });
                $.download = function (url, data, method, callback) {
                    var inputs = '';
                    if (url && data) {
                        $.each(data, function (p, val) {
                            inputs += '<input type="hidden" name="' + p + '" value="' + val + '" />';
                        });
                        //create form to send request 
                        $('<form action="' + url + '" method="' + (method || 'post') + '" target="iframeX">' + inputs + '</form>').appendTo('body').submit().remove();
                    }
                    ;
                };
            });</script>
        <div style=" width:980px; margin: 0px auto; margin-top: 5px;
             font-size: 20px;    height: 35px;">
            <span class="otstup" style="background-color: #9DC59D;">Время Гринвич: <span  id="greenwichDate"></span></span>
            <span class="otstup" style="background-color: #C59DA1;">Часовой пояс: <span  id="gmt"></span></span>
            <span class="otstup" style="background-color: #9D9EC5;">Время местное: <input type="text" style="width:125px;" id="datepickerCorrection">
                <select id="hourCorrection">
                </select>:
                <select id="minuteCorrection">
                </select></span>
        </div>
        <div style="    background-color: #BFA98C;
             width: 920px;
             margin: 0px auto;margin-bottom: 5px;">
            <span>Дата: <input type="text" style="width:125px;" id="datepicker"></span>
            <span>Широта: <input type="text" id="lat"></span>
            <span>Долгота: <input type="text" id="long"></span>
        </div>

        <div id="map" style="width:920px; height:500px;    margin: 0px auto;"></div>


        <table id="t1">
            <thead>
                <tr>
                    <td>Гр. сумерки</td>
                    <td>Нав. сумерки</td>
                    <td>Астрон. сумерки</td>
                    <td>Восход</td>
                    <td>Зенит</td>
                    <td>Закат</td>
                    <td>Гр. сумерки</td>
                    <td>Нав. сумерки</td>
                    <td>Астрон. сумерки</td>
                    <td>День</td>
                    <td>Ночь</td>
                    <td>день/ночь</td>

                    <td>Восход луны</td>
                    <td>Зенит луны</td>
                    <td>Закат луны</td>
                    <td>Луна</td>
                    <td>Безлуние</td>
                    <td>луна/безлуние</td>
                    <td>луна/безлуние</td>

                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span id="sr_sumerki_g"></span></td>
                    <td><span id="sr_sumerki_n"></span></td>
                    <td><span id="sr_sumerki_a"></span></td>
                    <td><span id="rise"></span></td>
                    <td><span id="zenit"></span></td>
                    <td><span id="set"></span></td>
                    <td><span id="ss_sumerki_g"></span></td>
                    <td><span id="ss_sumerki_n"></span></td>
                    <td><span id="ss_sumerki_a"></span></td>
                    <td><span id="day"></span> минут</td>
                    <td><span id="night"></span> минут</td>
                    <td><span id="day_night"></span></td>

                    <td><span id="moonRise"></span></td>
                    <td><span id="moonZenit"></span></td>
                    <td><span id="moonSet"></span></td>
                    <td><span id="moonVisible"></span></td>
                    <td><span id="moonInvisible"></span></td>
                    <td><span id="visible_invisible_nod"></span></td>
                    <td><span id="visible_invisible_real"></span></td>

                </tr>
            </tbody>
        </table>
        API:
        <table id="t3">
            <thead>
                <tr>                        
                    <td>Восход</td>
                    <td>Зенит</td>
                    <td>Закат</td>
                    <td>День</td>
                    <td>Ночь</td>
                    <td>день/ночь</td>
                    <td>день/ночь</td>
                </tr>
            </thead>
            <tbody>
                <tr>

                    <td><span id="rise"></span></td>
                    <td><span id="zenit"></span></td>
                    <td><span id="set"></span></td>
                    <td><span id="day"></span> минут</td>
                    <td><span id="night"></span> минут</td>
                    <td><span id="day_night"></span></td>
                    <td><span id="day_night2"></span></td>                       
                </tr>
            </tbody>
        </table>            
        <div><button id="calculate">Расчитать</button>
            <button id="showTable">Скачать Excel файлом</button>
            <button id="showZenitTable">Показать зениты на год в таблице</button>
            <button id="downloadZenitTable">Скачать Зениты Excel файлом</button>
        </div>
        <div style="background-color: #B39393;
             width: 920px;
             margin: 0px auto;margin-bottom: 5px;" >
            <label>Соотношение:<input type="text" id="filtrSootnosheniye"></label>
            <label><input type="checkbox" id="filtrShirotaCheck">Широта:<input type="text" id="filtrShirota"></label>
            <label><input type="checkbox" id="filtrDolgotaCheck">Долгота:<input type="text" id="filtrDolgota"></label>
            <button id="filterFind">Найти</button>
        </div>
        <div id="filterResult">
            <table id="filterTable" style="display:none;">
                <thead>
                    <tr>
                        <td>Дата</td>
                        <td>Широта</td>
                        <td>по зенитам</td>
                        <td>% отклонения</td>
                        <td>день</td>
                        <td>ночь</td>
                        <td>день/ночь</td>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div>
            <?php
            $html = file_get_html('https://pogoda.yandex.ru/obninsk/details');
            $ya_po = '';
            foreach ($html->find('div.current-weather') as $value) {
                $ya_po = $value->outertext;
            }
            echo $ya_po;
            ?>
        </div>
        <div>
            <table id="zenitTable">
                <thead>
                    <tr>
                        <td>Восход</td>
                        <td>Зенит</td>
                        <td>Закат</td>
                        <td>День</td>
                        <td>Ночь</td>
                        <td>день/ночь</td>
                        <td>день/ночь</td>
                        <td>Восход луны</td>
                        <td>Зенит луны</td>
                        <td>Закат луны</td>
                        <td>Луна</td>
                        <td>Безлуние</td>
                        <td>луна/безлуние</td>
                        <td>луна/безлуние</td>
                        <td>sun_Zenit_Zenit</td>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>




        <div id="select_local_time"></div>

    </body>
</html>
