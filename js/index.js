/**
 * 
 */
try{
	ymaps.ready(init);
}catch{
	
}

var myMap;
var lat, long, times, coords, date, day, night, riseMinutes, setMinutes, nod, day_night, rise, set;
var g_date, u_date, u_diff;
long = '30.52541521';
lat = '50.45332223';

function startTime(){
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
	// $("#greenwichDate").text(d + "." + mn + "." + y + " " + h +
	// ":" + m + ":" + s);
	// document.getElementById('txt').innerHTML = h + ":" + m + ":"
	// + s;

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
		'cmd': 'getSRS',
		'lat': lat,
		'long': long,
		'time': qtime,
		'diff': diff
	}, function (data) {
		times = JSON.parse(data);
		// console.log(times);
		$('#calculation #day').text(times.day);
		$('#calculation #night').text(times.night);
		$('#calculation #day_night').text(times.day_night);
		$('#calculation #day_night2').text(times.day_night_real);
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
		// $('#calculation
		// #sun_Zenit_Zenit').text(times.sun_Zenit_Zenit);

	});
	$.post("srs2.php", {
		cmd: 'getT3',
		lat: lat,
		long: long,
		time: qtime,
		diff: diff
	}, function (data) {
		times = JSON.parse(data);
		$('#api #day').text(times.day);
		$('#api #night').text(times.night);
		$('#api #day_night').text(times.day_night);
		$('#api #day_night2').text(times.day_night_real);
		$('#api #rise').text(times.sunRise);
		$('#api #zenit').text(times.sunZenit);
		$('#api #set').text(times.sunSet);
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
		// console.log(table);
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
			row.append("<td> " + val.visible_invisible_real + " </td>");
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
function setCurrentTime(){
	var d = new Date();
	var hours=d.getHours();
	var minutes=d.getMinutes();
	$('#hourCorrection').val(hours);
	$('#minuteCorrection').val(minutes);
}
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
	$('#lat').change(function () {
		lat = $('#lat').val();
	});
	$('#useSystemTime').on('change',function(){
		if($('#useSystemTime').prop( "checked" )){
			var real_time_timer=setInterval(() => {
				setCurrentTime();
			}, 1000);
			setCurrentTime();
		}else{
			clearInterval(real_time_timer);
		}
	});
	$('#useSystemTime').click();
	$('#long').change(function () {
		long = $('#long').val();
	});
	$('#calculate').click(function () {
		getValues();
	});
	$('#showTable').click(function () {
		$('#tablica tbody').html('');
		// getByDegrees();
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
			// create form to send request
			$('<form action="' + url + '" method="' + (method || 'post') + '" target="iframeX">' + inputs + '</form>').appendTo('body').submit().remove();
		} ;
	};
});