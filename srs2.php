<?php
header('Content-Type: text/html; charset= utf-8');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('memory_limit', '4096M');
ini_set("max_execution_time", "" . (60 * 60));

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of srs2
 *
 * @author tarantul
 */
// date_default_timezone_set('Greenwich');
class Moon
{

    /**
     * Calculates the moon rise/set for a given location and day of year
     */
    public static function calculateMoonTimes($date_in, $lat, $lon, $timezone, $summer, $recurs = false)
    {
        $retVal = new stdClass();
        $utrise = $utset = 0;
        $day = $date_in->format("d");
        $month = $date_in->format("m");
        $year = $date_in->format("Y");

        // $timezone = (int) ($lon / 15);
        // echo $timezone;
        if ($month <= 2) {
            $month += 12;
            $year -= 1;
        }
        ;
        $nc = floor($year / 100);
        $vc = ((floor($nc / 3) + floor($nc / 4)) + 6) - $nc;
        $a = ($year / 19);
        $b = (($a - (int) ($a)) * 209);
        $c = ($b + $month + $day + $vc) / 30;
        $MoonDay = round((($c - (int) ($c)) * 30) + 1);
        $retVal->moonDay = $MoonDay;

        $date = self::modifiedJulianDate($month, $day, $year);
        $date -= ($timezone) / 24;

        $latRad = deg2rad($lat);
        $sinho = 0.0023271056;
        $sglat = sin($latRad);
        $cglat = cos($latRad);

        $rise = false;
        $set = false;
        $above = false;
        $hour = 1;
        $ym = self::sinAlt($date, $hour - 1, $lon, $cglat, $sglat) - $sinho;

        $above = $ym > 0;
        while ($hour < 25 && (false == $set || false == $rise)) {

            $yz = self::sinAlt($date, $hour, $lon, $cglat, $sglat) - $sinho;
            $yp = self::sinAlt($date, $hour + 1, $lon, $cglat, $sglat) - $sinho;

            $quadout = self::quad($ym, $yz, $yp);
            $nz = $quadout[0];
            $z1 = $quadout[1];
            $z2 = $quadout[2];
            $xe = $quadout[3];
            $ye = $quadout[4];

            if ($nz == 1) {
                if ($ym < 0) {
                    $utrise = $hour + $z1;
                    $rise = true;
                } else {
                    $utset = $hour + $z1;
                    $set = true;
                }
            }

            if ($nz == 2) {
                if ($ye < 0) {
                    $utrise = $hour + $z2;
                    $utset = $hour + $z1;
                } else {
                    $utrise = $hour + $z1;
                    $utset = $hour + $z2;
                }
            }

            $ym = $yp;
            $hour += 2.0;
        }
        // Convert to unix timestamps and return as an object

        $utrise = self::convertTime($utrise);
        $utset = self::convertTime($utset);
        $retVal->isRise = $rise;
        $retVal->isSet = $set;
        $retVal->rise = $rise ? mktime($utrise['hrs'], $utrise['min'], 0, $month, $day, $year) : mktime(0, 0, 0, $month, $day + 1, $year);
        $retVal->set = $set ? mktime($utset['hrs'], $utset['min'], 0, $month, $day, $year) : mktime(0, 0, 0, $month, $day + 1, $year);

        if (($retVal->rise > $retVal->set) && ! $recurs) {
            $date_nextDay = $date_in;
            $date_nextDay->add(new DateInterval('P1D'));
            $nextMoon = self::calculateMoonTimes($date_nextDay, $lat, $lon, $timezone, $summer, true);
            $retVal->set = $nextMoon->set;
        }
        $retVal->visible = $retVal->set - $retVal->rise;
        $retVal->invisible = (24 * 60 * 60) - $retVal->visible;
        $retVal->zenit = floor(($retVal->visible / 2) + $retVal->rise);

        return $retVal;
    }

    /**
     * finds the parabola throuh the three points (-1,ym), (0,yz), (1, yp)
     * and returns the coordinates of the max/min (if any) xe, ye
     * the values of x where the parabola crosses zero (roots of the self::quadratic)
     * and the number of roots (0, 1 or 2) within the interval [-1, 1]
     *
     * well, this routine is producing sensible answers
     *
     * results passed as array [nz, z1, z2, xe, ye]
     */
    private static function quad($ym, $yz, $yp)
    {
        $nz = $z1 = $z2 = 0;
        $a = 0.5 * ($ym + $yp) - $yz;
        $b = 0.5 * ($yp - $ym);
        $c = $yz;
        $xe = - $b / (2 * $a);
        $ye = ($a * $xe + $b) * $xe + $c;
        $dis = $b * $b - 4 * $a * $c;
        if ($dis > 0) {
            $dx = 0.5 * sqrt($dis) / abs($a);
            $z1 = $xe - $dx;
            $z2 = $xe + $dx;
            $nz = abs($z1) < 1 ? $nz + 1 : $nz;
            $nz = abs($z2) < 1 ? $nz + 1 : $nz;
            $z1 = $z1 < - 1 ? $z2 : $z1;
        }

        return array(
            $nz,
            $z1,
            $z2,
            $xe,
            $ye
        );
    }

    /**
     * this rather mickey mouse function takes a lot of
     * arguments and then returns the sine of the altitude of the moon
     */
    private static function sinAlt($mjd, $hour, $glon, $cglat, $sglat)
    {
        $mjd += $hour / 24;
        $t = ($mjd - 51544.5) / 36525;
        $objpos = self::minimoon($t);

        $ra = $objpos[1];
        $dec = $objpos[0];
        $decRad = deg2rad($dec);
        $tau = 15 * (self::lmst($mjd, $glon) - $ra);

        return $sglat * sin($decRad) + $cglat * cos($decRad) * cos(deg2rad($tau));
    }

    /**
     * returns an angle in degrees in the range 0 to 360
     */
    private static function degRange($x)
    {
        $b = $x / 360;
        $a = 360 * ($b - (int) $b);
        $retVal = $a < 0 ? $a + 360 : $a;
        return $retVal;
    }

    private static function lmst($mjd, $glon)
    {
        $d = $mjd - 51544.5;
        $t = $d / 36525;
        $lst = self::degRange(280.46061839 + 360.98564736629 * $d + 0.000387933 * $t * $t - $t * $t * $t / 38710000);
        return $lst / 15 + $glon / 15;
    }

    /**
     * takes t and returns the geocentric ra and dec in an array mooneq
     * claimed good to 5' (angle) in ra and 1' in dec
     * tallies with another approximate method and with ICE for a couple of dates
     */
    private static function minimoon($t)
    {
        $p2 = 6.283185307;
        $arc = 206264.8062;
        $coseps = 0.91748;
        $sineps = 0.39778;

        $lo = self::frac(0.606433 + 1336.855225 * $t);
        $l = $p2 * self::frac(0.374897 + 1325.552410 * $t);
        $l2 = $l * 2;
        $ls = $p2 * self::frac(0.993133 + 99.997361 * $t);
        $d = $p2 * self::frac(0.827361 + 1236.853086 * $t);
        $d2 = $d * 2;
        $f = $p2 * self::frac(0.259086 + 1342.227825 * $t);
        $f2 = $f * 2;

        $sinls = sin($ls);
        $sinf2 = sin($f2);

        $dl = 22640 * sin($l);
        $dl += - 4586 * sin($l - $d2);
        $dl += 2370 * sin($d2);
        $dl += 769 * sin($l2);
        $dl += - 668 * $sinls;
        $dl += - 412 * $sinf2;
        $dl += - 212 * sin($l2 - $d2);
        $dl += - 206 * sin($l + $ls - $d2);
        $dl += 192 * sin($l + $d2);
        $dl += - 165 * sin($ls - $d2);
        $dl += - 125 * sin($d);
        $dl += - 110 * sin($l + $ls);
        $dl += 148 * sin($l - $ls);
        $dl += - 55 * sin($f2 - $d2);

        $s = $f + ($dl + 412 * $sinf2 + 541 * $sinls) / $arc;
        $h = $f - $d2;
        $n = - 526 * sin($h);
        $n += 44 * sin($l + $h);
        $n += - 31 * sin(- $l + $h);
        $n += - 23 * sin($ls + $h);
        $n += 11 * sin(- $ls + $h);
        $n += - 25 * sin(- $l2 + $f);
        $n += 21 * sin(- $l + $f);

        $L_moon = $p2 * self::frac($lo + $dl / 1296000);
        $B_moon = (18520.0 * sin($s) + $n) / $arc;

        $cb = cos($B_moon);
        $x = $cb * cos($L_moon);
        $v = $cb * sin($L_moon);
        $w = sin($B_moon);
        $y = $coseps * $v - $sineps * $w;
        $z = $sineps * $v + $coseps * $w;
        $rho = sqrt(1 - $z * $z);
        $dec = (360 / $p2) * atan($z / $rho);
        $ra = (48 / $p2) * atan($y / ($x + $rho));
        $ra = $ra < 0 ? $ra + 24 : $ra;

        return array(
            $dec,
            $ra
        );
    }

    /**
     * returns the self::fractional part of x as used in self::minimoon and minisun
     */
    private static function frac($x)
    {
        $x -= (int) $x;
        return $x < 0 ? $x + 1 : $x;
    }

    /**
     * Takes the day, month, year and hours in the day and returns the
     * modified julian day number defined as mjd = jd - 2400000.5
     * checked OK for Greg era dates - 26th Dec 02
     */
    private static function modifiedJulianDate($month, $day, $year)
    {
        if ($month <= 2) {
            $month += 12;
            $year --;
        }

        $a = 10000 * $year + 100 * $month + $day;
        $b = 0;
        if ($a <= 15821004.1) {
            $b = - 2 * (int) (($year + 4716) / 4) - 1179;
        } else {
            $b = (int) ($year / 400) - (int) ($year / 100) + (int) ($year / 4);
        }

        $a = 365 * $year - 679004;
        return $a + $b + (int) (30.6001 * ($month + 1)) + $day;
    }

    /**
     * Converts an hours decimal to hours and minutes
     */
    private static function convertTime($hours)
    {
        $hrs = (int) ($hours * 60 + 0.5) / 60.0;
        $h = (int) ($hrs);
        $m = (int) (60 * ($hrs - $h) + 0.5);
        return array(
            'hrs' => $h,
            'min' => $m
        );
    }
}

class srs2
{

    function NOD($A)
    {
        $n = count($A);
        $x = abs($A[0]);
        for ($i = 1; $i < $n; $i ++) {
            $y = abs($A[$i]);
            while ($x && $y) {
                $x > $y ? $x %= $y : $y %= $x;
            }
            $x += $y;
        }
        return $x;
    }

    function getGreenwichTime()
    {
        $ts = time();
        $dy = date('I', $ts);
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone('Greenwich'));
        $result = Array();
        $result['summer'] = $dy;
        $result['GMT'] = $date->format('U');
        $result['time'] = $date->format("H:i:s");
        return $result;
    }

    /**
     *
     * @param Array $params
     *            передаваемые параметры:
     *            ['dateTimeString'] в формате 'd.m.Y H:i:s'
     */
    function getSecondsFrom1900($params = [])
    {
        $inDate = DateTime::createFromFormat("d.m.Y H:i:s", $params['dateTimeString']);
        $result = ($inDate->format("H") * 60 * 60) + ($inDate->format("i") * 60) + $inDate->format("s");
        for ($ldate = DateTime::createFromFormat("d.m.Y", "01.01.1900"); $ldate->format("d.m.Y") !== $inDate->format("d.m.Y"); $ldate->add(new DateInterval('P1D'))) {
            $result += 24 * 60 * 60;
            // echo $result . " - " . $ldate->format("d.m.Y") . "\n";
        }
        return $result;
    }

    function getSunset($date, $long = 13.408056, $lat = 52.518611, $sunrise = true, $diff = 3)
    {
        $zenith = 90.8333333333;
        $D2R = M_PI / 180;
        $R2D = 180 / M_PI;
        $lnHour = $long / 15;

        $t = 0;
        if ($sunrise) {
            $t = ($date->format("z") + 1) + ((6 - $lnHour) / 24);
        } else {
            $t = ($date->format("z") + 1) + ((18 - $lnHour) / 24);
        }
        $M = (0.9856 * $t) - 3.289;
        $L = $M + (1.916 * sin($M * $D2R)) + (0.020 * sin(2 * $M * $D2R)) + 282.634;
        if ($L > 360) {
            $L = $L - 360;
        } else if ($L < 0) {
            $L = $L + 360;
        }
        $RA = $R2D * atan(0.91764 * tan($L * $D2R));
        if ($RA > 360) {
            $RA = $RA - 360;
        } else if ($RA < 0) {
            $RA = $RA + 360;
        }
        $Lquadrant = (floor($L / (90))) * 90;
        $RAquadrant = (floor($RA / 90)) * 90;
        $RA = $RA + ($Lquadrant - $RAquadrant);
        $RA = $RA / 15;
        $sinDec = 0.39782 * sin($L * $D2R);
        $cosDec = cos(asin($sinDec));
        $cosH = (cos($zenith * $D2R) - ($sinDec * sin($lat * $D2R))) / ($cosDec * cos($lat * $D2R));
        $cosH_G = (cos(96 * $D2R) - ($sinDec * sin($lat * $D2R))) / ($cosDec * cos($lat * $D2R));
        $cosH_N = (cos(102 * $D2R) - ($sinDec * sin($lat * $D2R))) / ($cosDec * cos($lat * $D2R));
        $cosH_A = (cos(108 * $D2R) - ($sinDec * sin($lat * $D2R))) / ($cosDec * cos($lat * $D2R));
        $H = 0;
        if ($sunrise) {
            if ($cosH > 1) {}
            if ($cosH < - 1) {}
            $H = 360 - $R2D * acos($cosH);
            $H_G = 360 - $R2D * acos($cosH_G);
            $H_N = 360 - $R2D * acos($cosH_N);
            $H_A = 360 - $R2D * acos($cosH_A);
        } else {
            if ($cosH > 1) {}
            if ($cosH < - 1) {}
            $H = $R2D * acos($cosH);
            $H_G = $R2D * acos($cosH_G);
            $H_N = $R2D * acos($cosH_N);
            $H_A = $R2D * acos($cosH_A);
        }

        $H = $H / 15;
        $H_G = $H_G / 15;
        $H_N = $H_N / 15;
        $H_A = $H_A / 15;

        $T = $H + $RA - (0.06571 * $t) - 6.622;
        $T_G = $H_G + $RA - (0.06571 * $t) - 6.622;
        $T_N = $H_N + $RA - (0.06571 * $t) - 6.622;
        $T_A = $H_A + $RA - (0.06571 * $t) - 6.622;

        $UT = $T - $lnHour;
        $UT_G = $T_G - $lnHour;
        $UT_N = $T_N - $lnHour;
        $UT_A = $T_A - $lnHour;
        if ($UT > 24) {
            $UT = $UT - 24;
        } else if ($UT < 0) {
            $UT = $UT + 24;
        }

        if ($UT_G > 24) {
            $UT_G = $UT_G - 24;
        } else if ($UT_G < 0) {
            $UT_G = $UT_G + 24;
        }
        if ($UT_N > 24) {
            $UT_N = $UT_N - 24;
        } else if ($UT_N < 0) {
            $UT_N = $UT_N + 24;
        }
        if ($UT_A > 24) {
            $UT_A = $UT_A - 24;
        } else if ($UT_A < 0) {
            $UT_A = $UT_A + 24;
        }

        $millisec = $date->format("U") + round($UT * 3600) + $diff;

        $millisec_G = $date->format("U") + round($UT_G * 3600) + $diff;
        $millisec_N = $date->format("U") + round($UT_N * 3600) + $diff;
        $millisec_A = $date->format("U") + round($UT_A * 3600) + $diff;

        if (is_nan($millisec)) {
            $millisec = 0;
        }
        if (is_nan($millisec_G)) {
            $millisec_G = 0;
        }
        if (is_nan($millisec_N)) {
            $millisec_N = 0;
        }
        if (is_nan($millisec_A)) {
            $millisec_A = 0;
        }

        $result["o"] = DateTime::createFromFormat("U", $millisec);
        $result["g"] = DateTime::createFromFormat("U", $millisec_G);
        $result["n"] = DateTime::createFromFormat("U", $millisec_N);
        $result["a"] = DateTime::createFromFormat("U", $millisec_A);
        // echo $result->format("d.m.Y H:i:s");
        return $result;
    }

    function get($day, $month, $year, $longitude, $latitude, $diff)
    {
        $summer = $this->getGreenwichTime();
        $tz = round($diff / (60 * 60));
        $diff = /* ($summer["summer"] * 3600)+ */($tz * 3600);
        $date_in = DateTime::createFromFormat("d.m.Y", $day . "." . $month . "." . $year);
        $date_in->setTimezone(new DateTimeZone('Greenwich'));
        $date_in->setTime(00, 00, 00);

        $date_sr = $this->getSunset($date_in, $longitude, $latitude, true, $diff)["o"];
        $date_sr_sumerki_g = $this->getSunset($date_in, $longitude, $latitude, true, $diff)["g"];
        $date_sr_sumerki_n = $this->getSunset($date_in, $longitude, $latitude, true, $diff)["n"];
        $date_sr_sumerki_a = $this->getSunset($date_in, $longitude, $latitude, true, $diff)["a"];
        $date_ss = $this->getSunset($date_in, $longitude, $latitude, false, $diff)["o"];
        $date_ss_sumerki_g = $this->getSunset($date_in, $longitude, $latitude, false, $diff)["g"];
        $date_ss_sumerki_n = $this->getSunset($date_in, $longitude, $latitude, false, $diff)["n"];
        $date_ss_sumerki_a = $this->getSunset($date_in, $longitude, $latitude, false, $diff)["a"];
        $date_zenit = DateTime::createFromFormat("U", round((($date_ss->format("U") - $date_sr->format("U")) / 2) + $date_sr->format("U"), 0));

        $result = Array();
        $result["sunRise"] = $date_sr->format("H:i:s");
        $result["sunSet"] = $date_ss->format("H:i:s");
        $result["sunZenit"] = $date_zenit->format("H:i:s");
        $result["sr_sumerki_g"] = $date_sr_sumerki_g->format("H:i:s");
        $result["sr_sumerki_n"] = $date_sr_sumerki_n->format("H:i:s");
        $result["sr_sumerki_a"] = $date_sr_sumerki_a->format("H:i:s");

        $result["ss_sumerki_g"] = $date_ss_sumerki_g->format("H:i:s");
        $result["ss_sumerki_n"] = $date_ss_sumerki_n->format("H:i:s");
        $result["ss_sumerki_a"] = $date_ss_sumerki_a->format("H:i:s");

        $result["day"] = round(($date_ss->format("U") - $date_sr->format("U")) / 60, 0);
        $result["night"] = round((24 * 60) - $result["day"], 0);
        // $result["day_night"] = $result["day"] . "/" . $result["night"];
        // $result["day_night_real"] = round($result["day"] / $result["night"], 3);
        // $nod = $this->NOD(Array($result["day"], $result["night"]));
        // $result["day_night_nod"] = ($result["day"] / $nod) . "/" . ($result["night"] / $nod);

        /*
         * ot zenita do zenita
         */
        // sun
        $date_P = DateTime::createFromFormat("d.m.Y", $day . "." . $month . "." . $year);
        $date_P->setTimezone(new DateTimeZone('Greenwich'));
        $date_P->setTime(23, 59, 59);
        $date_P->add(new DateInterval('PT1S'));

        $next_sr = $this->getSunset($date_P, $longitude, $latitude, true, $diff)["o"];
        $next_ss = $this->getSunset($date_P, $longitude, $latitude, false, $diff)["o"];
        $next_zenit = DateTime::createFromFormat("U", round((($next_ss->format("U") - $next_sr->format("U")) / 2) + $next_sr->format("U"), 0));

        $todayZZ = $date_zenit->format("U") - $date_ss->format("U"); // segodnya ot zenita do zakata
        $todatZP = $date_ss->format("U") - $date_P->format("U"); // segodnya ot zakata do polunochi

        $nextDayVZ = $next_sr->format("U") - $next_zenit->format("U"); // zavtra ot voskhoda do zenita
        $nextDayPV = $date_P->format("U") - $next_sr->format("U"); // zavtra ot polunochi do voshoda
        $test1 = ($todatZP + $nextDayPV);
        if ($test1 !== 0) {
            $dateZZ = ($todayZZ + $nextDayVZ) / $test1;
        } else {
            $dateZZ = 0;
        }

        $result["day_night"] = round($dateZZ, 3);
        // moon
        // ----------------------
        /*
         * $nextMoon = Moon::calculateMoonTimes($date_P->format("d"), $date_P->format("m"), $date_P->format("y"), $latitude, $longitude, $tz, $summer["summer"]);
         * $next_mr = $nextMoon->rise;
         * $next_ms = $nextMoon->set;
         *
         * $next_moon_zenit;
         * $todayMoonZZ;
         * $todatMoonZP;
         * $nextDayMoonVZ;
         * $nextDayMoonZP;
         * $dateMoonZZ;
         */
        $moon = Moon::calculateMoonTimes($date_in, $latitude, $longitude, $tz, $summer["summer"]);
        $date_mr = DateTime::createFromFormat("U", $moon->rise + $diff);

        // $result["moonIsRise"] = $moon->isRise;
        // $result["moonIsSet"] = $moon->isSet;

        $result["moonRise"] = $date_mr->format("d.m.Y H:i:s");
        $date_ms = DateTime::createFromFormat("U", $moon->set + $diff);

        // $mzv = $moon->visible / 2;
        // $mz = $mzv + $date_mr->format("U");
        // $result["mzv"]=$mzv;
        $date_moonzenit = DateTime::createFromFormat("U", $moon->zenit + $diff);
        $result["moonZenit"] = $date_moonzenit->format("d.m.Y H:i:s");
        $result["moonSet"] = $date_ms->format("d.m.Y H:i:s");
        // $result["moonDay"] = $moon->moonDay;
        $result["moonVisible"] = floor($moon->visible / 60);
        $result["moonInvisible"] = floor($moon->invisible / 60);
        $result["visible_invisible"] = $result["moonVisible"] . "/" . $result["moonInvisible"];
        if ($result["moonInvisible"] != 0) {
            $result["visible_invisible_real"] = round($result["moonVisible"] / $result["moonInvisible"], 3);
        } else {
            $result["visible_invisible_real"] = $result["moonVisible"];
        }
        $nod = $this->NOD(Array(
            $result["moonVisible"],
            $result["moonInvisible"]
        ));
        $result["visible_invisible_nod"] = ($result["moonVisible"] / $nod) . "/" . ($result["moonInvisible"] / $nod);

        return $result;
    }

    function getZenit($day, $month, $year, $longitude, $latitude, $diff)
    {
        $summer = $this->getGreenwichTime();
        $tz = round($diff / (60 * 60));
        $diff = /* ($summer["summer"] * 3600)+ */($tz * 3600);
        $date_in = DateTime::createFromFormat("d.m.Y", $day . "." . $month . "." . $year);
        $date_in->setTimezone(new DateTimeZone('Greenwich'));
        $date_in->setTime(00, 00, 00);

        $date_sr = $this->getSunset($date_in, $longitude, $latitude, true, $diff)["o"];
        $date_ss = $this->getSunset($date_in, $longitude, $latitude, false, $diff)["o"];
        $date_zenit = DateTime::createFromFormat("U", round((($date_ss->format("U") - $date_sr->format("U")) / 2) + $date_sr->format("U"), 0));

        $result = Array();
        $result["sunRise"] = $date_sr->format("H:i:s");
        $result["sunZenit"] = $date_zenit->format("H:i:s");
        $result["sunSet"] = $date_ss->format("H:i:s");
        $moon = Moon::calculateMoonTimes($day, $month, $year, $latitude, $longitude, $tz, $summer["summer"]);
        $date_mr = DateTime::createFromFormat("U", $moon->rise + $diff);
        $result["moonRise"] = $date_mr->format("d.m.Y H:i:s");
        $date_moonzenit = DateTime::createFromFormat("U", floor((floor($moon->invisible / 60) / 2) + $date_mr->format("U")));
        $result["moonZenit"] = $date_moonzenit->format("d.m.Y H:i:s");
        $date_ms = DateTime::createFromFormat("U", $moon->set + $diff);
        $result["moonSet"] = $date_ms->format("d.m.Y H:i:s");

        return $result;
    }

    function getT2($day, $month, $year, $longitude, $latitude, $diff)
    {
        $result = Array();
        $diff = floor($diff / (60 * 60));
        $date = DateTime::createFromFormat("d.m.Y", $day . "." . $month . "." . $year);
        $srd = date_sunrise($date->format("U"), SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, 90, $diff);
        $ssd = date_sunset($date->format("U"), SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, 90, $diff);
        $result["sunRise"] = DateTime::createFromFormat("U", $srd)->format("d.m.Y H:i:s");
        $result["sunSet"] = DateTime::createFromFormat("U", $ssd)->format("d.m.Y H:i:s");
        return $result;
    }

    function getT3($day, $month, $year, $longitude, $latitude, $diff)
    {
        $diff = floor($diff / (60 * 60));
        $date = DateTime::createFromFormat("d.m.Y H:i:s", $day . "." . $month . "." . $year . " 12:00:00");
        $s = "http://api.sunrise-sunset.org/json?lat={$latitude}&lng={$longitude}&date=" . $date->format("Y-m-d");
        $res = file_get_contents($s);
        $res = json_decode($res);
        $result = Array();
        $date_sr = DateTime::createFromFormat("d.m.Y h:i:s A", $day . "." . $month . "." . $year . " {$res->results->sunrise}");
        if ($diff > 0) {
            $date_sr->add(new DateInterval("PT{$diff}H"));
        }
        if ($diff < 0) {
            $date_sr->sub(new DateInterval("PT" . ($diff * - 1) . "H"));
        }
        $date_ss = DateTime::createFromFormat("d.m.Y h:i:s A", $day . "." . $month . "." . $year . " {$res->results->sunset}");
        if ($diff > 0) {
            $date_ss->add(new DateInterval("PT{$diff}H"));
        }
        if ($diff < 0) {
            $date_ss->sub(new DateInterval("PT" . ($diff * - 1) . "H"));
        }
        $date_zenit = DateTime::createFromFormat("U", round((($date_ss->format("U") - $date_sr->format("U")) / 2) + $date_sr->format("U"), 0));
        if ($diff > 0) {
            $date_zenit->add(new DateInterval("PT{$diff}H"));
        }
        if ($diff < 0) {
            $date_zenit->sub(new DateInterval("PT" . ($diff * - 1) . "H"));
        }
        $result["sunRise"] = $date_sr->format("H:i:s");
        $result["sunSet"] = $date_ss->format("H:i:s");
        $result["sunZenit"] = $date_zenit->format("H:i:s");
        $result["day"] = round(($date_ss->format("U") - $date_sr->format("U")) / 60, 0);
        $result["night"] = round((24 * 60) - $result["day"], 0);
        $result["day_night"] = $result["day"] . "/" . $result["night"];
        $result["day_night_real"] = round($result["day"] / $result["night"], 3);
        $nod = $this->NOD(Array(
            $result["day"],
            $result["night"]
        ));
        $result["day_night_nod"] = ($result["day"] / $nod) . "/" . ($result["night"] / $nod);
        return $result;
    }
}

$srs = new srs2();

// $srs->get(25, 6, 1990, -74.3, 40.9);
// print_r($srs->get(25, 8, 2015, 30.500000, 50.400000));
function cleanData(&$str)
{
    $str = preg_replace("/\t/", "\\t", $str);
    $str = preg_replace("/\r?\n/", "\\n", $str);
    if (strstr($str, '"')) {
        $str = '"' . str_replace('"', '""', $str) . '"';
    }
}

switch (@$_REQUEST['cmd']) {
    case 'getSRS':
        $time = DateTime::createFromFormat("U", round($_REQUEST['time'] / 1000, 0));
        $result = $srs->get($time->format("d"), $time->format("m"), $time->format("Y"), $_REQUEST['long'], $_REQUEST['lat'], $_REQUEST['diff']);
        echo json_encode($result);
        break; /*
                * case 'getT2':
                * $time = DateTime::createFromFormat("U", round($_REQUEST['time'] / 1000, 0));
                * $result = $srs->getT2($time->format("d"), $time->format("m"), $time->format("Y"), $_REQUEST['long'], $_REQUEST['lat'], $_REQUEST['diff']);
                * echo json_encode($result);
                * break;
                */
    case 'getT3':
        $time = DateTime::createFromFormat("U", round($_REQUEST['time'] / 1000, 0));
        $result = $srs->getT3($time->format("d"), $time->format("m"), $time->format("Y"), $_REQUEST['long'], $_REQUEST['lat'], $_REQUEST['diff']);
        echo json_encode($result);
        break;

    case 'getTable':
        $result = Array();
        $vdate = DateTime::createFromFormat("d.m.Y H:i:s", "01.01." . date('Y') . " 12:00:00");
        for ($ldate = DateTime::createFromFormat("d.m.Y H:i:s", "01.01." . date('Y') . " 12:00:00"); $ldate->format("U") <= ($vdate->format("U") + (365 * 24 * 60 * 60));) {
            for ($lat = 86; $lat >= - 86; $lat --) {
                $rdate = $ldate->format("d.m.Y");

                $tm = $srs->get($ldate->format("d"), $ldate->format("m"), $ldate->format("Y"), $_REQUEST['long'], $lat, $_REQUEST['diff']);
                $tmp['date'] = $rdate;
                $tmp['lat'] = $lat;
                $tmp['long'] = $_REQUEST['long'];
                $tmp = array_merge($tmp, $tm);
                $result[] = $tmp;
                // exit(0);
            }
            $ldate = DateTime::createFromFormat("U", $ldate->format("U") + (24 * 60 * 60));
        }
        $filename = "website_data_" . date('Ymd') . ".xls";

        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Content-Type: application/vnd.ms-excel");

        $flag = false;
        foreach ($result as $row) {
            if (! $flag) {
                // display field/column names as first row
                echo implode("\t", array_keys($row)) . "\r\n";
                $flag = true;
            }
            array_walk($row, 'cleanData');
            echo implode("\t", array_values($row)) . "\r\n";
        }
        exit();

        // $result = $sun->getTimes($_REQUEST['time'], $_REQUEST['lat'], $_REQUEST['long']);
        break;
    case "getGreenwichTime":
        $result = $srs->getGreenwichTime();
        echo json_encode($result);
        break;
    case "getZenit":
        $result = Array();
        $vdate = DateTime::createFromFormat("d.m.Y H:i:s", "01.01." . date('Y') . " 12:00:00");
        for ($ldate = DateTime::createFromFormat("d.m.Y H:i:s", "01.01." . date('Y') . " 12:00:00"); $ldate->format("U") <= ($vdate->format("U") + (365 * 24 * 60 * 60));) {
            $rdate = $ldate->format("d.m.Y");
            $tm = $srs->get($ldate->format("d"), $ldate->format("m"), $ldate->format("Y"), $_REQUEST['long'], $_REQUEST['lat'], $_REQUEST['diff']);
            $tmp['date'] = $rdate;
            $tmp['lat'] = $_REQUEST['lat'];
            $tmp['long'] = $_REQUEST['long'];
            $tmp = array_merge($tmp, $tm);
            $result[] = $tmp;
            // exit(0);
            $ldate = DateTime::createFromFormat("U", $ldate->format("U") + (24 * 60 * 60));
        }
        echo json_encode($result);
        break;
    case 'getZenitTable':
        $result = Array();
        $vdate = DateTime::createFromFormat("d.m.Y H:i:s", "01.01." . date('Y') . " 12:00:00");
        for ($ldate = DateTime::createFromFormat("d.m.Y H:i:s", "01.01." . date('Y') . " 12:00:00"); $ldate->format("U") <= ($vdate->format("U") + (364 * 24 * 60 * 60));) {

            $rdate = $ldate->format("d.m.Y");
            $tm = $srs->get($ldate->format("d"), $ldate->format("m"), $ldate->format("Y"), $_REQUEST['long'], $_REQUEST['lat'], $_REQUEST['diff']);
            $tmp['date'] = $rdate;
            $tmp['lat'] = $_REQUEST['lat'];
            $tmp['long'] = $_REQUEST['long'];
            $tmp = array_merge($tmp, $tm);
            $result[] = $tmp;
            // exit(0);

            $ldate = DateTime::createFromFormat("U", $ldate->format("U") + (24 * 60 * 60));
        }
        $filename = "website_data_" . date('Ymd') . ".xls";

        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Content-Type: application/vnd.ms-excel");

        $flag = false;
        foreach ($result as $row) {
            if (! $flag) {
                // display field/column names as first row
                echo implode("\t", array_keys($row)) . "\r\n";
                $flag = true;
            }
            array_walk($row, 'cleanData');
            echo implode("\t", array_values($row)) . "\r\n";
        }
        exit();

        // $result = $sun->getTimes($_REQUEST['time'], $_REQUEST['lat'], $_REQUEST['long']);
        break;
    case 'getFiltered':
        if(file_exists(dirname(__FILE__) . "/3pr.csv")) {
            $fp = fopen(dirname(__FILE__) . "/3pr.csv", "r");
            $result = [];
            if (isset($_REQUEST['val'])) {
                $_REQUEST['val'] = str_replace(",", ".", $_REQUEST['val']);
            }
            while (!feof($fp)) {
                $line = fgets($fp, 1024);
                $line = trim($line);
                if ($line == '') {
                    continue;
                }
                $records = explode(";", $line);
                $u1 = round($records[2], 3) === round(@$_REQUEST['val'], 3);
                $u2 = true;
                if ($_REQUEST['lat'] !== '') {
                    $u2 = round($records[1], 1) === round($_REQUEST['lat'], 1);
                }

                // $u3=round($records[1], 1) === round(@$_REQUEST['lat'], 1);
                if ($u1 && $u2) {
                    $result[] = $records;
                }
            }
            fclose($fp);
            $fp = fopen(dirname(__FILE__) . "/100pr.csv", "r");
            while (!feof($fp)) {
                $line = fgets($fp, 1024);
                $line = trim($line);
                if ($line == '') {
                    continue;
                }
                $records = explode(";", $line);
                $u1 = round($records[2], 3) == round(@$_REQUEST['val'], 3);
                $u2 = true;
                if ($_REQUEST['lat'] !== '') {
                    $u2 = round($records[1], 1) == round($_REQUEST['lat'], 1);
                }
                if ($u1 && $u2) {
                    $result[] = $records;
                }
            }
            fclose($fp);
            echo json_encode($result);
        }else{
            echo json_encode([]);
        }
        break;

    default:
        break;
}
// var_dump($argv);
if (@$argv[1] == 'getResults1') {
    $result = Array();
    $_REQUEST['long'] = '36.61280658';
    $_REQUEST['diff'] = 3 * 60 * 60;
    $vdate = DateTime::createFromFormat("d.m.Y H:i:s", "01.01." . date('Y') . " 12:00:00");
    $digits = file(dirname(__FILE__) . "/digits", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    // print_r($digits);
    // exit();
    for ($ldate = DateTime::createFromFormat("d.m.Y H:i:s", "01.01." . date('Y') . " 12:00:00"); $ldate->format("U") <= ($vdate->format("U") + (364 * 24 * 60 * 60));) {
        echo $ldate->format("d.m.Y") . "\n";
        for ($lat = 65; $lat >= - 65; $lat = round($lat - 0.1, 1)) {
            $rdate = $ldate->format("d.m.Y");
            $tm = $srs->get($ldate->format("d"), $ldate->format("m"), $ldate->format("Y"), $_REQUEST['long'], $lat, $_REQUEST['diff']);
            $tmp['date'] = $rdate;
            $tmp['lat'] = $lat;
            $tmp['long'] = $_REQUEST['long'];
            $tmp = array_merge($tmp, $tm);
            if ($tmp['sun_Zenit_Zenit'] == '') {
                continue;
            }
            foreach ($digits as $key => $val) {
                $razn = $tmp['sun_Zenit_Zenit'] - $val;
                $proc = $razn / $val;
                $prc = round($proc * 100, 2);
                if ($tmp['sun_Zenit_Zenit'] == $val) {
                    file_put_contents(dirname(__FILE__) . "/100pr.csv", "{$tmp['date']};{$lat};{$tmp['sun_Zenit_Zenit']};{$prc}%;{$tmp['day']};{$tmp['night']};{$tmp['day_night_real']};green\n", FILE_APPEND);
                } else if ($proc <= 0.03 && $proc >= - 0.03) {
                    file_put_contents(dirname(__FILE__) . "/3pr.csv", "{$tmp['date']};{$lat};{$tmp['sun_Zenit_Zenit']};{$prc}%;{$tmp['day']};{$tmp['night']};{$tmp['day_night_real']};yellow\n", FILE_APPEND);
                }
            }

            // $result[] = $tmp;
        }

        $ldate = DateTime::createFromFormat("U", $ldate->format("U") + (24 * 60 * 60));
    }
}
if (@$argv[1] == 'getSeconds') {
    // echo @$argv[2];
    echo $srs->getSecondsFrom1900([
        'dateTimeString' => @$argv[2]
    ]) . "\n";
}
if (@$argv[1] == 'loadFile') {
    $fp = fopen(dirname(__FILE__) . "/3pr.csv", "r");

    while (! feof($fp)) {
        $line = fgets($fp, 1024);
        $line = trim($line);
        if ($line == '') {
            continue;
        }
        $records = explode(";", $line);

        if ($records[2] == @$argv[2]) {
            // var_dump($records);
            echo "{$line}\n";
        }
    }
    fclose($fp);
    // echo count($lines);
}