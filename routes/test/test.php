<?php
header("Content-Type: text/html");
//unlink('cookies-advfn.txt');
//https://br.advfn.com/bolsa-de-valores/bmf/BGIK22/cotacao
//https://br.advfn.com/common/account/login
$loginUrl = 'https://secure.advfn.com/login/secure'; //action from the login form
$loginFields = array(
    'login_username' => 'oxcoin', 
    'login_password' => 'Oxcoin301012', 
    'site'=> 'br',
    'redirect_url' => 'aHR0cHM6Ly9ici5hZHZmbi5jb20='
); //login form field names and values
$remotePageUrl = 'https://br.advfn.com/bolsa-de-valores/bmf/BGIM22/cotacao'; //url of the page you want to save  

$login = getUrl($loginUrl, 'post', $loginFields); //login to the site

$remotePage = getUrl($remotePageUrl); //get the remote page

function getUrl($url, $method = '', $vars = '')
{
    $ch = curl_init();
    if ($method == 'post') {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies-advfn.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies-advfn.txt');
    $buffer = curl_exec($ch);
    curl_close($ch);
    return $buffer;
}
function htmlgetval($tag, $htmlcontent, $closetag = false)
{
    if (!$closetag) {
        $remove = ['<', '>'];
        $closetag = explode(" ", $tag)[0];
        $closetag = str_replace($remove, "", $closetag);
        $closetag = "<\/$closetag>";
    }
    preg_match("/$tag(.*?)$closetag/s", $htmlcontent, $match);
    return $match[1];
}
$val = htmlgetval('<title>', $remotePage);
echo "$remotePageUrl<br/>$val<br/>";
$x = explode('<span id="quoteElementPiece6"', $remotePage)[1];
$x = explode('</span>', $x)[0];
$x = explode('>', $x)[1];
echo $x;
//echo $remotePage;exit;
?>
<textarea style="width:100%;height:400px;border:1px solid red">
    <?= $remotePage ?>
</textarea>