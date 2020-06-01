<?php
header('Content-Type: text/html; charset= utf-8');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(- 1);
require_once './simple_html_dom.php';
?>
<html>
<head>

<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link href="jqueryui/css/base/jquery-ui.css" rel="stylesheet"
	type="text/css" />
<script src="jquery/jquery.js" type="text/javascript"></script>
<script src="jqueryui/jquery-ui.js" type="text/javascript"></script>
<script type="text/javascript"
	src="//api-maps.yandex.ru/2.0/?load=package.standard&lang=ru-RU"></script>
<link rel="stylesheet" type="text/css"
	href="https://yastatic.net/weather-frontend/3.14.4/desktop.bundles/index/_index.css" />
<link rel="stylesheet" type="text/css" href="index.css" />
<title></title>
</head>
<body>
	<script type="text/javascript" src="js/index.js">
           </script>
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
			<div class="content_row">
				[ local time: <input type="text" style="width: 125px;"
					id="datepickerCorrection"> <select id="hourCorrection">
				</select>: <select id="minuteCorrection">
				</select> <input type="checkbox" id="useSystemTime"> use system time
				]

			</div>
			<div class="content_row">
				<span>[ date for calculation: <input type="text"
					style="width: 125px;" id="datepicker"> ]
				</span> <span>[ latitude: <input type="text" id="lat"> ]
				</span> <span>[ longtitude: <input type="text" id="long"> ]
				</span>
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
			<div class="content_row">
				[ sunrise astronomical twilight: <span id="sr_sumerki_a"
					class="higlited"></span> ]
			</div>
			<div class="content_row">
				[ sunrise nautical twilight: <span id="sr_sumerki_n"
					class="higlited"></span> ]
			</div>
			<div class="content_row">
				[ sunrise civil twilight: <span id="sr_sumerki_g" class="higlited"></span>
				]
			</div>

			<div class="content_row">
				[ sunrise: <span id="rise" class="higlited"></span> ]
			</div>
			<div class="content_row">
				[ zenith: <span id="zenit" class="higlited"></span> ]
			</div>
			<div class="content_row">
				[ sunset: <span id="set" class="higlited"></span> ]
			</div>
			<div class="content_row">
				[ sunset civil twilight: <span id="ss_sumerki_g" class="higlited"></span>
				]
			</div>
			<div class="content_row">
				[ sunset nautical twilight: <span id="ss_sumerki_n" class="higlited"></span>
				]
			</div>
			<div class="content_row">
				[ sunset astronomical twilight: <span id="ss_sumerki_a"
					class="higlited"></span> ]
			</div>
			<div class="content_row">
				[ day: <span id="day" class="higlited"></span> ]
			</div>
			<div class="content_row">
				[ night: <span id="night" class="higlited"></span> ]
			</div>
			<div class="content_row">
				[ day/night: <span id="day_night" class="higlited"></span> ]
			</div>

			<div class="content_row">
				[ moonrise: <span id="moonRise" class="higlited"></span> ]
			</div>
			<div class="content_row">
				[ moon zenith: <span id="moonZenit" class="higlited"></span> ]
			</div>
			<div class="content_row">
				[ moonset: <span id="moonSet" class="higlited"></span> ]
			</div>
			<div class="content_row">
				[ moon time: <span id="moonVisible" class="higlited"></span> ]
			</div>
			<div class="content_row">
				[ moonless: <span id="moonInvisible" class="higlited"></span> ]
			</div>
			<div class="content_row">
				[ moon/moonless: <span id="visible_invisible_nod" class="higlited"></span>
				( <span id="visible_invisible_real" class="higlited"></span> ) ]
			</div>

		</div>
	</div>
	<div class="api" id="api">
		<div class="p_head higlited">[ api values ]</div>
		<div class="p_body">
			<div class="content_row">
				[ sunrise: <span id="rise" class="higlited"></span> ]
			</div>
			<div class="content_row">
				[ zenith: <span id="zenit" class="higlited"></span> ]
			</div>
			<div class="content_row">
				[ sunset: <span id="set" class="higlited"></span> ]
			</div>
			<div class="content_row">
				[ day: <span id="day" class="higlited"></span> min. ]
			</div>
			<div class="content_row">
				[ night: <span id="night" class="higlited"></span> min. ]
			</div>
			<div class="content_row">
				[ day/night: <span id="day_night" class="higlited"></span> ( <span
					id="day_night2" class="higlited"></span> ) ]
			</div>
		</div>
	</div>

	<div class="panel" id="filtering">
		<div class="p_head">[ results filtering ]</div>
		<div class="p_body">
			<div class="content_row">
				[ Соотношение: <input type="text" id="filtrSootnosheniye"> ] [ <input
					type="checkbox" id="filtrShirotaCheck"> latitude: <input
					type="text" id="filtrShirota"> ] [ <input type="checkbox"
					id="filtrDolgotaCheck"> longtitude: <input type="text"
					id="filtrDolgota"> ]
			</div>

			<button id="filterFind">[ filter ]</button>
		</div>
	</div>
	<div class="panel" id="results">
		<div class="p_head">[ results table ]</div>
		<div class="p_body">
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




		<div id="select_local_time"></div>

</body>
</html>
