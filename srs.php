<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of srs
 *
 * @author tarantul
 */
date_default_timezone_set('Greenwich');

class srs {
    var $dayMs = 86400000,
            $J1970 = 2440588,
            $J2000 = 2451545;

    var $rad, $e;
    var $times = Array(
        Array(-0.833, 'sunrise', 'sunset'),
        Array(-0.3, 'sunriseEnd', 'sunsetStart'),
        Array(-6, 'dawn', 'dusk'),
        Array(-12, 'nauticalDawn', 'nauticalDusk'),
        Array(-18, 'nightEnd', 'night'),
        Array(6, 'goldenHourEnd', 'goldenHour')
    );
    var $J0 = 0.0009;

    public function srs($date) {
        $this->rad = M_PI / 180;
        $this->e = $this->rad * 23.4397;
    }

    function toJulian($date) {
        return (($date / $this->dayMs) - 0.5 + $this->J1970);
    }

    function fromJulian($j) {
        return ($j + 0.5 - $this->J1970) * $this->dayMs;
    }

    function toDays($date) {
        return $this->toJulian($date) - $this->J2000;
    }

    function rightAscension($l, $b) {
        return atan(sin($l) * cos($this->e) - tan($b) * sin($this->e), cos($l));
    }

    function declination($l, $b) {
        return asin(sin($b) * cos($this->e) + cos($b) * sin($this->e) * sin($l));
    }

    function azimuth($H, $phi, $dec) {
        return atan(sin($H), cos($H) * sin($phi) - tan($dec) * cos($phi));
    }

    function altitude($H, $phi, $dec) {
        return asin(sin($phi) * sin($dec) + cos($phi) * cos($dec) * cos($H));
    }

    function siderealTime($d, $lw) {
        return $this->rad * (280.16 + 360.9856235 * $d) - $lw;
    }

    function solarMeanAnomaly($d) {
        return $this->rad * (357.5291 + 0.98560028 * $d);
    }

    function eclipticLongitude($M) {
        $C = $this->rad * (1.9148 * sin($M) + 0.02 * sin(2 * $M) + 0.0003 * sin(3 * $M)); // equation of center
        $P = $this->rad * 102.9372; // perihelion of the Earth
        return $M + $C + $P + M_PI;
    }

    function sunCoords($d) {
        $M = $this->solarMeanAnomaly($d);
        $L = $this->eclipticLongitude($M);
        $result['dec'] = $this->declination($L, 0);
        $result['ra'] = $this->rightAscension($L, 0);
        return $result;
    }

    function getPosition($date, $lat, $lng) {
        $lw = $this->rad * ($lng * -1);
        $phi = $this->rad * $lat;
        $d = $this->toDays($date);
        $c = $this->sunCoords($d);
        $H = $this->siderealTime($d, $lw) - $c['ra'];
        $result['azimuth'] = $this->azimuth($H, $phi, $c['dec']);
        $result['altitude'] = $this->altitude($H, $phi, $c['dec']);
        return $result;
    }

    function julianCycle($d, $lw) {
        return round($d - $this->J0 - $lw / (2 * M_PI));
    }

    function approxTransit($Ht, $lw, $n) {
        return $this->J0 + ($Ht + $lw) / (2 * M_PI) + $n;
    }

    function solarTransitJ($ds, $M, $L) {
        return $this->J2000 + $ds + 0.0053 * sin($M) - 0.0069 * sin(2 * $L);
    }

    function hourAngle($h, $phi, $d) {
        return acos((sin($h) - sin($phi) * sin($d)) / (cos($phi) * cos($d)));
    }

    function getSetJ($h, $lw, $phi, $dec, $n, $M, $L) {
        $w = $this->hourAngle($h, $phi, $dec);
        $a = $this->approxTransit($w, $lw, $n);
        return $this->solarTransitJ($a, $M, $L);
    }

    function NOD($A) {
        $n = count($A);
        $x = abs($A[0]);
        for ($i = 1; $i < $n; $i++) {
            $y = abs($A[$i]);
            while ($x && $y) {
                $x > $y ? $x %= $y : $y %= $x;
            }
            $x += $y;
        }
        return $x;
    }

    function getTimes($date, $lat, $lng) {
        //$date=round($date/100);
        $date = DateTime::createFromFormat('U',round($date/1000));
        $date->setTime(12,0,0);
        //echo $date->format("d.m.Y H:i:s");
        $lw = $this->rad * ($lng * -1);
        $phi = $this->rad * $lat;
        $d = $this->toDays($date->format("U")*1000);
        $n = $this->julianCycle($d, $lw);
        $ds = $this->approxTransit(0, $lw, $n);
        $M = $this->solarMeanAnomaly($ds);
        $L = $this->eclipticLongitude($M);
        $dec = $this->declination($L, 0);
        $Jnoon = $this->solarTransitJ($ds, $M, $L);
        $i = '';
        $len = '';
        $time = '';
        $Jset = '';
        $Jrise = '';
        $result['solarNoon'] = $this->fromJulian($Jnoon);
        $result['nadir'] = $this->fromJulian($Jnoon - 0.5);
        for ($i = 0, $len = count($this->times); $i < $len; $i += 1) {
            $time = $this->times[$i];
            $Jset = $this->getSetJ($time[0] * $this->rad, $lw, $phi, $dec, $n, $M, $L);
            $Jrise = $Jnoon - ($Jset - $Jnoon);
            $result[$time[1]] = $this->fromJulian($Jrise);
            $result[$time[2]] = $this->fromJulian($Jset);
        }
        $result['sunriseDateTime'] = date('d.m.Y H:i:s', round($result['sunrise'] / 1000));
        $result['sunriseMinutes'] = date('H', round($result['sunrise'] / 1000)) * 60 + date('m', round($result['sunrise'] / 1000));
        $result['sunsetMinutes'] = date('H', round($result['sunset'] / 1000)) * 60 + date('m', round($result['sunset'] / 1000));
        $result['sunsetDateTime'] = date('d.m.Y H:i:s', round($result['sunset'] / 1000));
        $result['solarNoonDateTime'] = date('d.m.Y H:i:s', round($result['solarNoon'] / 1000));

        $result['day'] = round(($result['sunset'] - $result['sunrise']) / 60000, 0);
        $result['night'] = (24 * 60) - $result['day'];
        $nod = $this->NOD([$result['day'], $result['night']]);
        $result['day_nod'] = $result['day'] / $nod;
        $result['night_nod'] = $result['night'] / $nod;
        $result['day_night'] = $result['day_nod'] . '/' . $result['night_nod'];
        $result['day_night2'] = round($result['day_nod'] / $result['night_nod'], 4);
        $moon=$this->getMoonTimes($date->format("U"),$lat,$lng);
        $result["moonRise"]=date('d.m.Y H:i:s', round($moon["rise"] / 1000));
        $result["moonSet"]=date('d.m.Y H:i:s', round($moon["set"] / 1000));
        $result["moonAlwaysUp"]=$moon["alwaysUp"];
        $result["moonAlwaysDown"]=$moon["alwaysDown"];        
        return $result;
    }

    function moonCoords($d) {
        $L = $this->rad * (218.316 + 13.176396 * $d);
        $M = $this->rad * (134.963 + 13.064993 * $d);
        $F = $this->rad * (93.272 + 13.229350 * $d);
        $l = $L + $this->rad * 6.289 * sin($M);
        $b = $this->rad * 5.128 * sin($F);
        $dt = 385001 - 20905 * cos($M);
        return Array("ra" => $this->rightAscension($l, $b), "dec" => $this->declination($l, $b), "dist" => $dt);
    }

    function getMoonPosition($date, $lat, $lng) {
        $lw = $this->rad * $lng * -1;
        $phi = $this->rad * $lat;
        $d = $this->toDays($date);
        $c = $this->moonCoords($d);
        $H = $this->siderealTime($d, $lw) - $c["ra"];
        $h = $this->altitude($H, $phi, $c["dec"]);
        $h = $h + $this->rad * 0.017 / tan($h + $this->rad * 10.26 / ($h + $this->rad * 5.10));
        return Array("azimuth" => $this->azimuth($H, $phi, $c["dec"]), "altitude" => $h, "distance" => $c["dist"]);
    }

    function getMoonIllumination($date) {
        $d = $this->toDays($date);
        $s = $this->sunCoords($d);
        $m = $this->moonCoords($d);
        $sdist = 149598000;
        $phi = acos(sin($s["dec"]) * sin($m["dec"]) + cos($s["dec"]) * cos($m["dec"]) * cos($s["ra"] - $m["ra"]));
        $inc = atan($sdist * sin($phi), $m["dist"] - $sdist * cos($phi));
        $angle = atan(cos($s["dec"]) * sin($s["ra"] - $m["ra"]), sin($s["dec"]) * cos($m["dec"]) - cos($s["dec"]) * sin($m["dec"]) * cos($s["ra"] - $m["ra"]));
        return Array("fraction" => (1 + cos($inc)) / 2, "phase" => 0.5 + 0.5 * $inc * ($angle < 0 ? -1 : 1) / M_PI, "angle" => $angle);
    }

    function hoursLater($date, $h) {
        return ($date + ($h * ($this->dayMs / 24)));
    }

    function getMoonTimes($date, $lat, $lng) {
        $date = DateTime::createFromFormat('U',$date);
        $date->setTime(0,0,0);
        $t = $date->format("U")*1000;
//        echo date('d.m.Y H:i:s', round($t / 1000));
        $hc = 0.133 * $this->rad;
        $h0 = $this->getMoonPosition($t, $lat, $lng)["altitude"] - $hc;
        $h1 = '';
        $h2 = '';
        $rise = '';
        $set = '';
        $a = '';
        $b = '';
        $xe = '';
        $ye = '';
        $d = '';
        $roots = '';
        $x1 = '';
        $x2 = '';
        $dx = "";

        // go in 2-hour chunks, each time seeing if a 3-point quadratic curve crosses zero (which means rise or set)
        for ($i = 1; $i <= 24; $i += 2) {
            $h1 = $this->getMoonPosition($this->hoursLater($t, $i), $lat, $lng)["altitude"] - $hc;
            $h2 = $this->getMoonPosition($this->hoursLater($t, $i + 1), $lat, $lng)["altitude"] - $hc;
            $a = ($h0 + $h2) / 2 - $h1;
            $b = ($h2 - $h0) / 2;
            $xe = -$b / (2 * $a);
            $ye = ($a * $xe + $b) * $xe + $h1;
            $d = $b * $b - 4 * $a * $h1;
            $roots = 0;

            if ($d >= 0) {
                $dx = sqrt($d) / (abs($a) * 2);
                $x1 = $xe - $dx;
                $x2 = $xe + $dx;
                if (abs($x1) <= 1) {
                    $roots++;
                }
                if (abs($x2) <= 1) {
                    $roots++;
                }
                if ($x1 < -1) {
                    $x1 = $x2;
                }
            }

            if ($roots === 1) {
                if ($h0 < 0) {
                    $rise = $i + $x1;
                } else {
                    $set = $i + $x1;
                }
            } elseif ($roots === 2) {
                $rise = $i + ($ye < 0 ? $x2 : $x1);
                $set = $i + ($ye < 0 ? $x1 : $x2);
            }
            if ($rise && $set) {
                break;
            }
            $h0 = $h2;
        }
        $result = Array();
        
        if ($rise) {
            $result["rise"] = $this->hoursLater($t, $rise);
        }
        if ($set) {
            $result["set"] = $this->hoursLater($t, $set);
        }
        if (!$rise && !$set) {
            $result[$ye > 0 ? 'alwaysUp' : 'alwaysDown'] = true;
        }
        return $result;
    }

}

$sun = new srs();
switch ($_REQUEST['cmd']) {
    case 'getSRS':
        $result = $sun->getTimes($_REQUEST['time'], $_REQUEST['lat'], $_REQUEST['long']);
        echo json_encode($result);
        break;
    case 'getTable':
        $result = '';
        $vdate = mktime(12, 0, 0, 1, 1, date('Y')) * 1000;
        for ((file_exists("{$vdate}_{$_REQUEST['long']}.date")) ? $ldate = file_get_contents("{$vdate}_{$_REQUEST['long']}.date") : $ldate = $vdate; ($ldate < $vdate + (365 * 24 * 60 * 60 * 1000)); $ldate+=(24 * 60 * 60 * 1000)) {
            file_put_contents("{$vdate}_{$_REQUEST['long']}.date", $ldate);
            for ((file_exists("{$vdate}_{$_REQUEST['long']}.lat")) ? $lat = file_get_contents("{$vdate}_{$_REQUEST['long']}.lat") : $lat = 0; $lat < 90; $lat++) {
                file_put_contents("{$vdate}_{$_REQUEST['long']}.lat", $lat);
                $tmp = $sun->getTimes($ldate, $lat, $_REQUEST['long']);
                $rdate = date('d.m.Y', ($ldate / 1000));
                $tmp['rdate'] = $rdate;
                $tmp['long'] = $_REQUEST['long'];
                $tmp['lat'] = $lat;
                //$result = json_encode($tmp)."\n";                
                //$result = "{$rdate};{$lat};{$_REQUEST['long']};{$tmp['sunriseDateTime']};{$tmp['solarNoonDateTime']};{$tmp['sunsetDateTime']};{$tmp['day']};{$tmp['night']};{$tmp['day_night']};{$tmp['day_night2']}\n";
                $result = "<tr><td>{$rdate}</td><td>{$lat}</td><td>{$_REQUEST['long']}</td><td>{$tmp['sunriseDateTime']}</td><td>{$tmp['solarNoonDateTime']}</td><td>{$tmp['sunsetDateTime']}</td><td>{$tmp['day']}</td><td>{$tmp['night']}</td><td>{$tmp['day_night']}</td><td>{$tmp['day_night2']}</td></tr>\n";

                file_put_contents("{$vdate}_{$_REQUEST['long']}.table", $result, FILE_APPEND | LOCK_EX);
            }

            file_put_contents("{$vdate}_{$_REQUEST['long']}.lat", '0');
        }
        $result = file_get_contents("{$vdate}_{$_REQUEST['long']}.table");
        echo $result;
        //$result = $sun->getTimes($_REQUEST['time'], $_REQUEST['lat'], $_REQUEST['long']);        
        break;

    default:
        break;
}