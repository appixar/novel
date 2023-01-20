<?php
function getIp()
{
    // CLOUDFLARE PROXY
    if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
        $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"]; // SOMETIMES RETURN IPV6
    }
    // OTHER VERIFICATIONS...
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    return $ip;
}
function getOS()
{
    $os_platform = "Unknown OS Platform";
    $os_array = array(
        '/linux/i' => 'Linux',
        '/windows nt 10/i' => 'Windows 10',
        '/windows nt 6.3/i' => 'Windows 8.1',
        '/windows nt 6.2/i' => 'Windows 8',
        '/windows nt 6.1/i' => 'Windows 7',
        '/windows nt 6.0/i' => 'Windows Vista',
        '/windows nt 5.2/i' => 'Windows Server 2003/XP x64',
        '/windows nt 5.1/i' => 'Windows XP',
        '/windows xp/i' => 'Windows XP',
        '/windows nt 5.0/i' => 'Windows 2000',
        '/windows me/i' => 'Windows ME',
        '/win98/i' => 'Windows 98',
        '/win95/i' => 'Windows 95',
        '/win16/i' => 'Windows 3.11',
        '/macintosh|mac os x/i' => 'Mac OS X',
        '/mac_powerpc/i' => 'Mac OS 9',
        '/ubuntu/i' => 'Ubuntu',
        '/iphone/i' => 'iPhone',
        '/ipod/i' => 'iPod',
        '/ipad/i' => 'iPad',
        '/android/i' => 'Android',
        '/blackberry/i' => 'BlackBerry',
        '/webos/i' => 'Mobile',
        '/sunos/i' => 'SunOS',
        '/beos/i' => 'BeOS',
        '/risc os/i' => 'RISC OS',
        '/os/2/i' => 'OS/2',
        '/freebsd/i' => 'FreeBSD',
        '/openbsd/i' => 'OpenBSD',
        '/netbsd/i' => 'NetBSD',
        '/irix/i' => 'IRIX',
        '/plan9/i' => 'Plan9',
        '/osf/i' => 'OSF',
        '/aix/i' => 'AIX',
        '/GNU Hurd/i' => 'GNU Hurd',
        '/fedora/i' => 'Linux - Fedora',
        '/kubuntu/i' => 'Linux - Kubuntu',
        '/ubuntu/i' => 'Linux - Ubuntu',
        '/debian/i' => 'Linux - Debian',
        '/CentOS/i' => 'Linux - CentOS',
        '/Mandriva/i' => 'Linux - Mandriva',
        '/SUSE/i' => 'Linux - SUSE',
        '/Dropline/i' => 'Linux - Slackware (Dropline GNOME)',
        '/ASPLinux/i' => 'Linux - ASPLinux',
        '/Red Hat/i' => 'Linux - Red Hat',
        '/amigaos)([0-9]{1,2}\.[0-9]{1,2}/i' => 'AmigaOS',
        '/amiga-aweb/i' => 'AmigaOS',
        '/amiga/i' => 'Amiga',
        '/AvantGo/i' => 'PalmOS',
        '/Symbian/i' => 'Symbian',
        '/Windows Phone/i' => 'Windows Phone'
    );
    foreach ($os_array as $regex => $value) {
        if (preg_match($regex, $_SERVER['HTTP_USER_AGENT'])) {
            $os_platform = $value;
        }
    }
    return $os_platform;
}

function getBrowser()
{
    $browser = "Unknown Browser";
    $browser_array = array(
        '/msie|trident/i' => 'Internet Explorer',
        '/firefox/i' => 'Firefox',
        '/safari/i' => 'Safari',
        '/chrome/i' => 'Chrome',
        '/opera/i' => 'Opera',
        '/netscape/i' => 'Netscape',
        '/maxthon/i' => 'Maxthon',
        '/konqueror/i' => 'Konqueror',
        '/mobile/i' => 'Handheld Browser'
    );
    foreach ($browser_array as $regex => $value) {
        if (preg_match($regex, $_SERVER['HTTP_USER_AGENT'])) {
            $browser = $value;
        }
    }
    return $browser;
}

function getBrowser2()
{
    $u_agent = $_SERVER['HTTP_USER_AGENT'];
    $bname = 'Unknown';
    $platform = 'Unknown';
    $version = "";

    //First get the platform?
    if (preg_match('/linux/i', $u_agent)) {
        $platform = 'linux';
    } elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
        $platform = 'mac';
    } elseif (preg_match('/windows|win32/i', $u_agent)) {
        $platform = 'windows';
    }
    // Next get the name of the useragent yes seperately and for good reason
    if (preg_match('/MSIE/i', $u_agent) && !preg_match('/Opera/i', $u_agent)) {
        $bname = 'Internet Explorer';
        $ub = "MSIE";
    } elseif (preg_match('/Firefox/i', $u_agent)) {
        $bname = 'Mozilla Firefox';
        $ub = "Firefox";
    } elseif (preg_match('/Chrome/i', $u_agent)) {
        $bname = 'Google Chrome';
        $ub = "Chrome";
    } elseif (preg_match('/Safari/i', $u_agent)) {
        $bname = 'Apple Safari';
        $ub = "Safari";
    } elseif (preg_match('/Opera/i', $u_agent)) {
        $bname = 'Opera';
        $ub = "Opera";
    } elseif (preg_match('/Netscape/i', $u_agent)) {
        $bname = 'Netscape';
        $ub = "Netscape";
    }
    // finally get the correct version number
    $known = array('Version', $ub, 'other');
    $pattern = '#(?<browser>' . join('|', $known) .
        ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
    if (!preg_match_all($pattern, $u_agent, $matches)) {
        // we have no matching number just continue
    }
    // see how many we have
    $i = count($matches['browser']);
    if ($i != 1) {
        //we will have two since we are not using 'other' argument yet
        //see if version is before or after the name
        if (strripos($u_agent, "Version") < strripos($u_agent, $ub)) {
            $version = $matches['version'][0];
        } else {
            $version = $matches['version'][1];
        }
    } else {
        $version = $matches['version'][0];
    }
    // check if we have a number
    if ($version == null || $version == "") {
        $version = "?";
    }

    return array(
        'userAgent' => $u_agent,
        'name'      => $bname,
        'version'   => $version,
        'platform'  => $platform,
        'pattern'    => $pattern
    );
}

//===========================================
// CLOUDFLARE isCloudflare() getRequestIP()
//===========================================

function ip_in_range($ip, $range)
{
    if (strpos($range, '/') == false)
        $range .= '/32';

    // $range is in IP/CIDR format eg 127.0.0.1/24
    list($range, $netmask) = explode('/', $range, 2);
    $range_decimal = ip2long($range);
    $ip_decimal = ip2long($ip);
    $wildcard_decimal = pow(2, (32 - $netmask)) - 1;
    $netmask_decimal = ~$wildcard_decimal;
    return (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
}

function _cloudflare_CheckIP($ip)
{
    $cf_ips = array(
        '199.27.128.0/21',
        '173.245.48.0/20',
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '141.101.64.0/18',
        '108.162.192.0/18',
        '190.93.240.0/20',
        '188.114.96.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.158.0.0/15',
        '104.16.0.0/12',
    );
    $is_cf_ip = false;
    foreach ($cf_ips as $cf_ip) {
        if (ip_in_range($ip, $cf_ip)) {
            $is_cf_ip = true;
            break;
        }
    }
    return $is_cf_ip;
}

function _cloudflare_Requests_Check()
{
    $flag = true;

    if (!isset($_SERVER['HTTP_CF_CONNECTING_IP']))   $flag = false;
    if (!isset($_SERVER['HTTP_CF_IPCOUNTRY']))       $flag = false;
    if (!isset($_SERVER['HTTP_CF_RAY']))             $flag = false;
    if (!isset($_SERVER['HTTP_CF_VISITOR']))         $flag = false;
    return $flag;
}

function isCloudflare()
{
    $ipCheck        = _cloudflare_CheckIP($_SERVER['REMOTE_ADDR']);
    $requestCheck   = _cloudflare_Requests_Check();
    return ($ipCheck && $requestCheck);
}

// Use when handling ip's
function getRequestIP()
{
    $check = isCloudflare();

    if ($check) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}
