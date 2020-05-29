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
        <link rel="stylesheet" type="text/css" href="index.css" />
        <title></title>
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
            long = '30.52541521';
            lat = '50.45332223';


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
                    $('#calculation #day').text(times.day);
                    $('#calculation #night').text(times.night);
                    $('#calculation #day_night').text(times.day_night);
                    // $('#calculation #day_night2').text(times.day_night_real);
                    $('#calculation #sr_sumerki_g').text(times.sr_sumerki_g);
                    $('#calculation #sr_sumerki_n').text(times.sr_sumerki_n);
                    $('#calculation #sr_sumerki_a').text(times.sr_sumerki_a);

                    $('#calculation #rise').text(times.sunRise);
                    $('#calculation #zenit').text(times.sunZenit);
                    $('#calculation #set').text(times.sunSet);
                    $('#calculation #ss_sumerki_g').text(times.ss_sumerki_g);
                    $('#calculation #ss_sumerki_n').text(times.ss_sumerki_n);
                    $('#calculation #ss_sumerki_a').text(times.ss_sumerki_a);
                    $('#calculation #moonRise').text(times.moonRise);
                    $('#calculation #moonZenit').text(times.moonZenit);
                    $('#calculation #moonSet').text(times.moonSet);
                    $('#calculation #moonVisible').text(times.moonVisible);
                    $('#calculation #moonInvisible').text(times.moonInvisible);
                    $('#calculation #visible_invisible_real').text(times.visible_invisible_real);
                    $('#calculation #visible_invisible_nod').text(times.visible_invisible_nod);
                    //$('#calculation #sun_Zenit_Zenit').text(times.sun_Zenit_Zenit);

                });
                $.post("srs2.php", {
                    cmd: 'getT3',
                    lat: lat,
                    long: long,
                    time: qtime,
                    diff: diff
                }, function (data) {
                    times = JSON.parse(data);
                    $('.api #day').text(times.day);
                    $('.api #night').text(times.night);
                    $('.api #day_night').text(times.day_night);
                    $('.api #day_night2').text(times.day_night_real);
                    $('.api #rise').text(times.sunRise);
                    $('.api #zenit').text(times.sunZenit);
                    $('.api #set').text(times.sunSet);
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
                        row.append("<td>" + val.day_night_real + " ( " + val.day_night + " )</td>");                        
                        row.append("<td>" + val.moonRise + "</td>");
                        row.append("<td>" + val.moonZenit + "</td>");
                        row.append("<td>" + val.moonSet + "</td>");
                        row.append("<td>" + val.moonVisible + "</td>");
                        row.append("<td>" + val.moonInvisible + "</td>");
                        row.append("<td>" + val.visible_invisible_nod + " ( " + val.visible_invisible_real + " )</td>");                        
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
        <div class="panel">
            <span class="otstup">[ greenwich time: <span  id="greenwichDate" class="higlited"></span> ]</span>
            <span class="otstup">[ gmt: <span  id="gmt" class="higlited"> </span> ]</span>            
        </div>

        <div id="control" class="panel">
            <div class="p_head higlited">[ control panel ]</div>
            <div class="p_body">
                <div class="content_row">
                    [ local time: <input type="text" style="width:125px;" id="datepickerCorrection">
                    <select id="hourCorrection">
                    </select>:
                    <select id="minuteCorrection">
                    </select>
                    ]
                </div>
                <div class="content_row">
                    <span >[ date for calculation: <input type="text" style="width:125px;" id="datepicker"> ]</span>
                    <span >[ latitude: <input type="text" id="lat"> ]</span>
                    <span >[ longtitude: <input type="text" id="long"> ]</span>
                </div>
                <button id="calculate">[ calculate ]</button>
                <button id="showTable">[ download excel file ]</button>
                <button id="showZenitTable">[ show zenith for a year in table ]</button>
                <button id="downloadZenitTable">[ download zenith excel file ]</button>

            </div>
        </div>
        <div id="map" class="map"></div>       
        <div id="calculation" class="api">
            <div class="p_head higlited">[ result calculation list ]</div>
            <div class="p_body">
                <div class="content_row">[ sunrise civil twilight: <span id="sr_sumerki_g" class="higlited"></span> ]</div>
                <div class="content_row">[ sunrise nautical twilight: <span id="sr_sumerki_n" class="higlited"></span> ]</div>
                <div class="content_row">[ sunrise astronomical twilight: <span id="sr_sumerki_a" class="higlited"></span> ]</div>
                <div class="content_row">[ sunrise: <span id="rise" class="higlited"></span> ]</div>
                <div class="content_row">[ zenith: <span id="zenit" class="higlited"></span> ]</div>
                <div class="content_row">[ sunset: <span id="set" class="higlited"></span> ]</div>
                <div class="content_row">[ sunset civil twilight: <span id="ss_sumerki_g" class="higlited"></span> ]</div>
                <div class="content_row">[ sunset nautical twilight: <span id="ss_sumerki_n" class="higlited"></span> ]</div>
                <div class="content_row">[ sunset astronomical twilight: <span id="ss_sumerki_a" class="higlited"></span> ]</div>
                <div class="content_row">[ day: <span id="day" class="higlited"></span> ]</div>
                <div class="content_row">[ night: <span id="night" class="higlited"></span> ]</div>
                <div class="content_row">[ day/night: <span id="day_night" class="higlited"></span> ]</div>

                <div class="content_row">[ moonrise: <span id="moonRise" class="higlited"></span> ]</div>
                <div class="content_row">[ moon zenith: <span id="moonZenit" class="higlited"></span> ]</div>
                <div class="content_row">[ moonset: <span id="moonSet" class="higlited"></span> ]</div>
                <div class="content_row">[ moon time: <span id="moonVisible" class="higlited"></span> ]</div>
                <div class="content_row">[ moonless: <span id="moonInvisible" class="higlited"></span> ]</div>
                <div class="content_row">[ moon/moonless: <span id="visible_invisible_nod" class="higlited"></span> ( <span id="visible_invisible_real" class="higlited"></span> ) ]</div>                

            </div>
        </div>  
        <div class="api">
            <div class="p_head higlited">[ api values ]</div>
            <div class="p_body">
                <div class="content_row">[ sunrise: <span id="rise" class="higlited"></span> ]</div>
                <div class="content_row">[ zenith: <span id="zenit" class="higlited"></span> ]</div>
                <div class="content_row">[ sunset: <span id="set" class="higlited"></span> ]</div>
                <div class="content_row">[ day: <span id="day" class="higlited"></span> min. ]</div>
                <div class="content_row">[ night: <span id="night" class="higlited"></span> min. ]</div>
                <div class="content_row">[ day/night: <span id="day_night" class="higlited"></span> ( <span id="day_night2" class="higlited"></span> ) ]</div>
            </div>
        </div>             

        <div class="panel" id="filtering">
            <div class="p_head">[ results filtering ]</div>
            <div class="p_body">
                <div class="content_row">[ Соотношение: <input type="text" id="filtrSootnosheniye"> ]
                    [ <input type="checkbox" id="filtrShirotaCheck"> latitude: <input type="text" id="filtrShirota"> ]
                    [ <input type="checkbox" id="filtrDolgotaCheck"> longtitude: <input type="text" id="filtrDolgota"> ]                
                </div>

                <button id="filterFind">[ filter ]</button>
            </div>
        </div>
        <div class="panel" id="results">
            <div class="p_head">[ results table ]</div>
            <div class="p_body">
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
            </div>
            <div>
                <?php
                /*
                  $html = file_get_html('https://pogoda.yandex.ru/obninsk/details');
                  $ya_po = '';
                  foreach ($html->find('div.current-weather') as $value) {
                  $ya_po = $value->outertext;
                  }
                  echo $ya_po;
                 * 
                 */
                ?>
            </div>
            <div>
                <table id="zenitTable">
                    <thead>
                        <tr>
                            <td>sunrise</td>
                            <td>zenith</td>
                            <td>sunset</td>
                            <td>day</td>
                            <td>night</td>
                            <td>day/night</td>
                            <td>moonrise</td>
                            <td>moon zenith</td>
                            <td>moonset</td>
                            <td>moon</td>
                            <td>moonless</td>
                            <td>moon/moonless</td>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>




            <div id="select_local_time"></div>

    </body>
</html>
