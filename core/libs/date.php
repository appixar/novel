<?php
function meses()
{
    return array("Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
}
//verifica ultimo dia do mes
function last_day($day, $month, $year)
{
    $last_day = date("t", mktime(0, 0, 0, $month, '01', $year));
    if ($day == $last_day && ($last_day == 28 || $last_day == 29 || $last_day == 30)) {
        switch ($last_day) {
            case 28:
                $day = '28,29,30,31';
                break;
            case 29:
                $day = '29,30,31';
                break;
            case 30:
                $day = '30,31';
                break;
        }
        return $day;
    } else {
        return $day;
    }
}

// Diff between 2 dates in minutes
function diffInMinutes($dt0, $dt1)
{
    $dt1 = strtotime($dt1);
    $dt0 = strtotime($dt0);
    return round(abs($dt1 - $dt0) / 60, 2);
}

// Idade de acordo com dias, e não anos (ex: ainda não completou)
function idade($y_m_d)
{
    $bday = new DateTime($y_m_d);
    $today = new DateTime(date("Y-m-d")); // for testing purposes
    $diff = $today->diff($bday);
    return sprintf('%d', $diff->y);
}
// Mostrar dia da semana de uma data
function dayofweek($date)
{
    $res = date('w', strtotime($date));
    $arr = array("dom", "seg", "ter", "qua", "qui", "sex", "sáb");
    return $arr[$res];
}
//-------------------------------
// Validar data
//-------------------------------
function validateDate($date, $format = 'Y-m-d')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}

//-------------------------------
// Mostrar "tempo atrás"
//-------------------------------
function time_elapsed_string($datetime, $full = false, $ago_str = false)
{
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;
    $string = array(
        'y' => 'ano',
        'm' => 'mês',
        'w' => 'semana',
        'd' => 'dia',
        'h' => 'hora',
        'i' => 'min',
        's' => 'seg',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            if ($k == "m" && $diff->$k > 1) { // plural diferente
                $v = $diff->$k . ' ' . 'meses';
            } else {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            }
        } else {
            unset($string[$k]);
        }
    }
    if (!$full) {
        $string = array_slice($string, 0, 1);
    }
    if (!$ago_str) {
        return $string ? 'há ' . implode(', ', $string) . '' : 'agora mesmo';
    } else {
        return $string ? implode(', ', $string) . ' atrás' : 'agora mesmo';
    }
}
//-------------------------------
// Transforma segundos em H:i:s
//-------------------------------
function secToTime($seconds)
{
    $hours = str_pad(floor($seconds / 3600), 2, 0, STR_PAD_LEFT);
    $seconds -= $hours * 3600;
    $minutes = str_pad(floor($seconds / 60), 2, 0, STR_PAD_LEFT);
    $seconds -= $minutes * 60;
    $seconds = str_pad($seconds, 2, 0, STR_PAD_LEFT);
    return "$hours:$minutes:$seconds";
}

// seconds to 00:00:00
function sec2time($seconds)
{
    return gmdate("H:i:s", $seconds);
}

// 00:00:00 to seconds
function time2sec($time = '00:00:00')
{
    list($hours, $mins, $secs) = explode(':', $time);
    return ($hours * 3600) + ($mins * 60) + $secs;
}

//-------------------------------
// Transforma segundos em dias, horas, m, s...
//-------------------------------

function secondsToTime($seconds)
{
    $dtF = new DateTime("@0");
    $dtT = new DateTime("@$seconds");
    return $dtF->diff($dtT)->format('%a d %h h %i m %s s');
}

// Data EN
function dateEN($dateBR)
{
    $d = explode("/", $dateBR);
    return $d[2] . "-" . $d[1] . "-" . $d[0];
}

// Data BR
function dateBR($datetime, $mini = "")
{
    if (!$datetime) return false;
    $arr = explode(" ", $datetime);
    $d = explode("-", $arr[0]);
    if ($mini) {
        $data = "$d[2]/$d[1]";
    } else {
        $data = "$d[2]/$d[1]/$d[0]";
    }
    if ($arr[1]) {
        $h = explode(":", $arr[1]);
        if ($mini) {
            $data .= " $h[0]:$h[1]";
        } else {
            $data .= " " . $arr[1];
        }
    }
    return $data;
}

function dateBRnoTime($datetime)
{
    $arr = explode(" ", $datetime);
    $d = explode("-", $arr[0]);
    $data = "$d[2]/$d[1]/$d[0]";
    return $data;
}

function mes($mounth_number)
{
    $m = $mounth_number;
    if ($m == '01') {
        $M = 'janeiro';
    }

    if ($m == '02') {
        $M = 'fevereiro';
    }

    if ($m == '03') {
        $M = 'março';
    }

    if ($m == '04') {
        $M = 'abril';
    }

    if ($m == '05') {
        $M = 'maio';
    }

    if ($m == '06') {
        $M = 'junho';
    }

    if ($m == '07') {
        $M = 'julho';
    }

    if ($m == '08') {
        $M = 'agosto';
    }

    if ($m == '09') {
        $M = 'setembro';
    }

    if ($m == '10') {
        $M = 'outubro';
    }

    if ($m == '11') {
        $M = 'novembro';
    }

    if ($m == '12') {
        $M = 'dezembro';
    }

    return $M;
}
function date_rest($date_end)
{
    $now = time(); // or your date as well
    $your_date = strtotime($date_end);
    $datediff = $now - $your_date;
    return str_replace("-", "", floor($datediff / (60 * 60 * 24)));
}

/**
 * Calculate differences between two dates with precise semantics. Based on PHPs DateTime::diff()
 * implementation by Derick Rethans. Ported to PHP by Emil H, 2011-05-02. No rights reserved.
 *
 * See here for original code:
 * http://svn.php.net/viewvc/php/php-src/trunk/ext/date/lib/tm2unixtime.c?revision=302890&view=markup
 * http://svn.php.net/viewvc/php/php-src/trunk/ext/date/lib/interval.c?revision=298973&view=markup
 */
//$diff = _date_diff(strtotime($start), strtotime($end));
//$dur = "{$diff["h"]}h {$diff["i"]}m {$diff["s"]}s";

function _date_range_limit($start, $end, $adj, $a, $b, &$result)
{
    if ($result[$a] < $start) {
        $result[$b] -= intval(($start - $result[$a] - 1) / $adj) + 1;

        $result[$a] += $adj * intval(($start - $result[$a] - 1) / $adj + 1);
    }
    if ($result[$a] >= $end) {
        $result[$b] += intval($result[$a] / $adj);
        $result[$a] -= $adj * intval($result[$a] / $adj);
    }
    return $result;
}

function _date_range_limit_days(&$base, &$result)
{
    $days_in_month_leap = array(31, 31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    $days_in_month = array(31, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    _date_range_limit(1, 13, 12, "m", "y", $base);
    $year = $base["y"];
    $month = $base["m"];
    if (!$result["invert"]) {
        while ($result["d"] < 0) {
            $month--;
            if ($month < 1) {
                $month += 12;
                $year--;
            }
            $leapyear = $year % 400 == 0 || ($year % 100 != 0 && $year % 4 == 0);
            $days = $leapyear ? $days_in_month_leap[$month] : $days_in_month[$month];
            $result["d"] += $days;
            $result["m"]--;
        }
    } else {
        while ($result["d"] < 0) {
            $leapyear = $year % 400 == 0 || ($year % 100 != 0 && $year % 4 == 0);
            $days = $leapyear ? $days_in_month_leap[$month] : $days_in_month[$month];
            $result["d"] += $days;
            $result["m"]--;
            $month++;
            if ($month > 12) {
                $month -= 12;
                $year++;
            }
        }
    }
    return $result;
}

function _date_normalize(&$base, &$result)
{
    $result = _date_range_limit(0, 60, 60, "s", "i", $result);
    $result = _date_range_limit(0, 60, 60, "i", "h", $result);
    $result = _date_range_limit(0, 24, 24, "h", "d", $result);
    $result = _date_range_limit(0, 12, 12, "m", "y", $result);
    $result = _date_range_limit_days($base, $result);
    $result = _date_range_limit(0, 12, 12, "m", "y", $result);
    return $result;
}
/**
 * Accepts two unix timestamps.
 */
function _date_diff($one, $two)
{
    $invert = false;
    if ($one > $two) {
        list($one, $two) = array($two, $one);
        $invert = true;
    }
    $key = array("y", "m", "d", "h", "i", "s");
    $a = array_combine($key, array_map("intval", explode(" ", date("Y m d H i s", $one))));
    $b = array_combine($key, array_map("intval", explode(" ", date("Y m d H i s", $two))));
    $result = array();
    $result["y"] = $b["y"] - $a["y"];
    $result["m"] = $b["m"] - $a["m"];
    $result["d"] = $b["d"] - $a["d"];
    $result["h"] = $b["h"] - $a["h"];
    $result["i"] = $b["i"] - $a["i"];
    $result["s"] = $b["s"] - $a["s"];
    $result["invert"] = $invert ? 1 : 0;
    $result["days"] = intval(abs(($one - $two) / 86400));
    if ($invert) {
        _date_normalize($a, $result);
    } else {
        _date_normalize($b, $result);
    }
    return $result;
}
