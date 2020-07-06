<?php
header('Content-Type: text/html; charset= utf-8');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(- 1);
//require_once $_SERVER ['DOCUMENT_ROOT'].'/../lib.bra1nw0rk.com/Preloader.php';
//require_once $_SERVER ['DOCUMENT_ROOT'].'/../lib.bra1nw0rk.com/lib/Http.php';
include_once $_SERVER ['DOCUMENT_ROOT'].'/../lib.bra1nw0rk.com/lib/simple_html/HtmlDocument.php';
//require_once './simple_html_dom.php';
?>
<html>
<head>

<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<script defer type="text/javascript"
	src="//api-maps.yandex.ru/2.0/?load=package.standard&lang=ru-RU"></script>
<script src="jquery/jquery.js" type="text/javascript"></script>
<script src="jqueryui/jquery-ui.js" type="text/javascript"></script>

<script defer type="text/javascript" src="js/index.js"></script>
<link href="jqueryui/css/base/jquery-ui.css" rel="stylesheet"
	type="text/css" />

<link rel="stylesheet" type="text/css"
	href="https://yastatic.net/weather-frontend/3.14.4/desktop.bundles/index/_index.css" />
<link rel="stylesheet" type="text/css" href="index.css" />
<title></title>
</head>
<body>
	<div class="panel">
		<label class="content_description"> <span class="dark">description:</span>
			sunrise, sunset, moonrise, moonset, zenith, twilight...
		</label> <label class="content_description"> <span class="dark">copyright:</span>
			<a href="bra1nw0rk.com">Volodymyr Cherniyevskyy</a>
		</label> <label class="content_description"> <span class="dark">tech:</span>
			PHP, JavaScript, jQuery, json, HTML, CSS
		</label><label class="content_description"> <span class="dark">algorithm:</span>

		</label>
	</div>
	<div class="panel">
		<span class="otstup">[ greenwich time: <span id="greenwichDate"
			class="higlited"></span> ]
		</span> <span class="otstup">[ gmt: <span id="gmt" class="higlited"> </span>
			]
		</span>
	</div>
	<div id="control" class="panel">
		<div class="p_head higlited">[ control panel ]</div>
		<div class="p_body">
			<label class="content_description">select your local date & time:</label>
			<div class="content_row">
				[ local time: <input type="text" style="width: 125px;"
					id="datepickerCorrection"> <select id="hourCorrection">
				</select>: <select id="minuteCorrection">
				</select> <input type="checkbox" id="useSystemTime"><label
					for="useSystemTime">use system time</label> ]

			</div>
			<label class="content_description">settings for calculation. date,
				latitude & longtitude. Click on map to set up:</label>
			<div class="content_row">
				<span>[ date for calculation: <input type="text"
					style="width: 125px;" id="datepicker"> ]
				</span> <span>[ latitude: <input type="text" id="lat"> ]
				</span> <span>[ longtitude: <input type="text" id="long"> ]
				</span>
			</div>
			<button id="calculate">calculate</button>
			<button id="showTable">download excel file</button>
			<button id="showZenitTable">show zenith for a year in table</button>
			<button id="downloadZenitTable">download zenith excel file</button>

		</div>
	</div>
	<div id="map" class="map"></div>
	<div id="calculation" class="panel">
		<div class="p_head higlited">[ result calculation list ]</div>
		<div class="p_body">

			<div class="content_description">sun:</div>
			<div class="content_row">
				<div class="column">
					<div class="tabbed">
						[ sunrise astronomical twilight: <span id="sr_sumerki_a"
							class="higlited"></span> ]
					</div>
					<div class="tabbed">
						[ sunrise nautical twilight: <span id="sr_sumerki_n"
							class="higlited"></span> ]
					</div>
					<div class="tabbed">
						[ sunrise civil twilight: <span id="sr_sumerki_g" class="higlited"></span>
						]
					</div>
				</div>
				<div class="column">
					<div class="tabbed">
						[ sunrise: <span id="rise" class="higlited"></span> ]
					</div>
					<div class="tabbed">
						[ zenith: <span id="zenit" class="higlited"></span> ]
					</div>
					<div class="tabbed">
						[ sunset: <span id="set" class="higlited"></span> ]
					</div>
				</div>
				<div class="column">
					<div class="tabbed">
						[ sunset civil twilight: <span id="ss_sumerki_g" class="higlited"></span>
						]
					</div>
					<div class="tabbed">
						[ sunset nautical twilight: <span id="ss_sumerki_n"
							class="higlited"></span> ]
					</div>
					<div class="tabbed">
						[ sunset astronomical twilight: <span id="ss_sumerki_a"
							class="higlited"></span> ]
					</div>
				</div>
				<div class="column">
					<div class="tabbed">
						[ day: <span id="day" class="higlited"></span> ]
					</div>
					<div class="tabbed">
						[ night: <span id="night" class="higlited"></span> ]
					</div>
					<div class="tabbed">
						[ day/night: <span id="day_night" class="higlited"></span> ]
					</div>
				</div>
			</div>

			<div class="content_description">moon:</div>
			<div class="content_row">
				<div class="column">
					<div class="tabbed">
						[ moonrise: <span id="moonRise" class="higlited"></span> ]
					</div>
					<div class="tabbed">
						[ moon zenith: <span id="moonZenit" class="higlited"></span> ]
					</div>
					<div class="tabbed">
						[ moonset: <span id="moonSet" class="higlited"></span> ]
					</div>
				</div>
				<div class="column">
					<div class="tabbed">
						[ moon time: <span id="moonVisible" class="higlited"></span> ]
					</div>
					<div class="tabbed">
						[ moonless: <span id="moonInvisible" class="higlited"></span> ]
					</div>
					<div class="tabbed">
						[ moon/moonless: <span id="visible_invisible_nod" class="higlited"></span>
						( <span id="visible_invisible_real" class="higlited"></span> ) ]
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="panel" id="api">
		<div class="p_head higlited">[ api values ]</div>
		<div class="p_body center">
			<span> [ sunrise: <span id="rise" class="higlited"></span> ]
			</span> <span> [ zenith: <span id="zenit" class="higlited"></span> ]
			</span> <span> [ sunset: <span id="set" class="higlited"></span> ]
			</span> <span> [ day: <span id="day" class="higlited"></span> min. ]
			</span> <span> [ night: <span id="night" class="higlited"></span>
				min. ]
			</span> <span> [ day/night: <span id="day_night" class="higlited"></span>
				( <span id="day_night2" class="higlited"></span> ) ]
			</span>
		</div>
	</div>


	<div class="panel" id="results">
		<div class="p_head">[ results table ]</div>
		<div class="p_body">
			<div class="panel">
				<label class="content_description">results filter:</label>
				<div class="content_row">
					[ Соотношение: <input type="text" id="filtrSootnosheniye"> ] [ <input
						type="checkbox" id="filtrShirotaCheck"> <label
						for="filtrShirotaCheck">latitude</label>: <input type="text"
						id="filtrShirota"> ] [ <input type="checkbox"
						id="filtrDolgotaCheck"> <label for="filtrDolgotaCheck">longtitude</label>:
					<input type="text" id="filtrDolgota"> ]
					<button id="filterFind">filter</button>
				</div>
			</div>
			<div id="filterResult">
				<table id="filterTable" style="display: none;">
					<thead>
						<tr>
							<td>date</td>
							<td>latitude</td>
							<td>by zenith</td>
							<td>% отклонения</td>
							<td>day</td>
							<td>night</td>
							<td>day/night</td>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
		</div>

		<div>
                <?php
                /*
                 * $html = file_get_html('https://pogoda.yandex.ru/obninsk/details');
                 * $ya_po = '';
                 * foreach ($html->find('div.current-weather') as $value) {
                 * $ya_po = $value->outertext;
                 * }
                 * echo $ya_po;
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
	</div>





</body>
</html>
