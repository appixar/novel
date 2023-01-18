<?php
class advfn
{
    public $loginUrl = 'https://secure.advfn.com/login/secure';
    public $loginFields = array(
        'login_username' => 'oxcoin',
        'login_password' => 'Oxcoin301012',
        'site' => 'br',
        'redirect_url' => 'aHR0cHM6Ly9ici5hZHZmbi5jb20='
    );
    public $loginCookie = './price.php@cookies-advfn'; // app/jobs/src/
    //
    public $error = false;
    public $return = false;
    //
    public function login()
    {
        $this->get($this->loginUrl, 'post', $this->loginFields);
    }
    public function get($url, $method = '', $vars = '')
    {
        $ch = curl_init();
        if ($method == 'post') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->loginCookie);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->loginCookie);
        $buffer = curl_exec($ch);
        curl_close($ch);
        //if ($httpcode == 429) return false; // to many requests
        return $buffer;
    }
}
