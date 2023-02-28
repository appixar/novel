
<?php
class Auth extends Arion
{
    public $error = false;
    public $return = false;
    public $rules;
    //
    public function __construct($rules = array())
    {
        global $_HEADER, $_APP, $_AUTH, $_BODY, $_URI, $_KEY;
        $_AUTH = false;
        $_KEY = false;

        // Default rules
        if (!isset($rules['required'])) $this->rules['required'] = false;
        else $this->rules['required'] = true;
        if (!isset($rules['auth_post'])) $this->rules['auth_post'] = false; // AUTH DATA IN POST (NOT IN HEADER). UTIL FOR FORMS.
        else $this->rules['auth_post'] = true;
        //---------------------------------------------------------
        // GET HEADER DATA
        // -> Authorization: <user_key>
        //---------------------------------------------------------

        // HEADER AUTH DATA
        if (!$this->rules['auth_post']) {
            if (!@$_HEADER['authorization'] and $this->rules['required']) http::die(401);
            if (!@$_HEADER['authorization'] and !$this->rules['required']) return true;
            $_KEY = $_HEADER['authorization'];
        }
        // $_POST AUTH DATA
        else {
            if (!@$_POST['auth']) http::die(401);
            $_KEY = $_POST['auth'];
        }
        if ($_APP['AUTH']['MODE'] === 'SKR') $_AUTH = $this->skr_execute();
        return $_AUTH;
    }
    public function skr_execute()
    {
        global $_HEADER, $_APP, $_AUTH, $_BODY, $_URI, $_KEY;

        // DECODE HEADER KEY
        $_KEY = base64_decode($_KEY);

        //---------------------------------------------------------
        // GET USER DATA
        //---------------------------------------------------------
        $my = new my();
        $user = $my->query("SELECT * FROM ox_user WHERE user_key = :user_key", ['user_key' => $_KEY]);

        // VERIFY USER KEY
        if (!@$user[0] and $this->rules['required']) http::die(401, 'Invalid user key');
        if (@$user[0]["user_status"] != 1 and $this->rules['required']) http::die(401, 'Disabled account');

        // VERIFY SESSION KEY
        if ($user) {
            $sess = $my->query("SELECT session_id, session_status FROM ox_user_session WHERE session_key = :key AND user_id = {$user[0]["user_id"]}", ['key' => $_SESS_KEY]);
            if (!@$sess[0]) http::die(401, 'Invalid session key');
            if (@$sess[0]['session_status'] != 1) http::die(401, 'Expired session key');
        }

        // REGISTER ACTIVITY
        if (!empty(@$sess)) {

            // beautify db
            if ($_POST) $post_json = json_encode(@$_POST, true);
            else $post_json = '';

            // data
            $ins = array(
                'user_id' => $user[0]["user_id"],
                'session_id' => $sess[0]['session_id'],
                'log_endpoint' => implode('/', $_URI),
                'log_method' => @$_HEADER['method'],
                'log_header' => json_encode($_HEADER, true),
                'log_data_body' => json_encode(@$_BODY, true),
                'log_data_post' => $post_json,
                'log_date_insert' => date("Y-m-d H:i:s")
            );
            $my->insert('ox_user_session_log', $ins);
        }
        // RETURN
        $_AUTH = @$user[0];
        auth_end:
        return $_AUTH;
    }
    public static function license($class, $function)
    {
        return true;
        global $_AUTH, $_URI, $_HEADER;
        include __DIR__ . '/license.array.php';

        //----------------------------------------------
        // FREE ROUTES
        //----------------------------------------------
        // CHECK BY CLASS & FUNCTION
        //if (in_array($uri, "$class:$function") return true;

        // ... OR CHECK BY URI (ENDPOIT ROUTE)
        $uri = implode('/', $_URI);
        $uri_method = $uri . "." . low($_HEADER['method']);
        if (in_array($uri, $license['free']) or in_array($uri_method, $license['free'])) return true;

        //----------------------------------------------
        // PRIVATE ROUTES
        //----------------------------------------------
        if (in_array($uri, $license['private']) or in_array($uri_method, $license['private'])) {
            if (@$_AUTH) return true;
            else return false;
        }

        return false;
    }
    public function registers($body)
    {
        //new bleu();
        $this->return = 'aaas';
        return true;
    }
    private function createJwt($user_id, $aud)
    {
        global $_APP;
        $exp = intval($_APP['AUTH']['JWT_EXP']);

        // User data
        $my = new my();
        $user = $my->query("SELECT user_name FROM $aud WHERE user_id = $user_id")[0];
        if (!@$user) return false;

        // Gera o payload do token JWT
        $payload = array(
            "sub" => $user_id,
            "name" => $user['user_name'],
            "iat" => time(),
            "exp" => time() + $exp
        );

        // Codifica o payload em base64
        $base64Url = base64_encode(json_encode($payload));

        // Gera a assinatura
        $secret = $_APP['secret'];
        $signature = hash_hmac("sha256", $base64Url, $secret, true);

        // Gera o token JWT
        $jwt = $base64Url . "." . $signature;

        // Envia o token JWT como resposta do login
        return $jwt;
    }

    private function validateJwt()
    {
        global $_APP;
        $secret = $_APP['secret'];

        // Recupera o token JWT enviado pelo cliente
        $jwt = $_SERVER["HTTP_AUTHORIZATION"];

        // Quebra o token em partes
        $parts = explode(".", $jwt);

        // Verifica se o token tem 3 partes
        if (count($parts) != 3) {
            return false;
            //echo json_encode(array("message" => "Token inválido"));
            //exit;
        }

        // Verifica se a assinatura é válida
        $signature = hash_hmac("sha256", $parts[0] . "." . $parts[1], $secret, true);
        if ($parts[2] != $signature) {
            return false;
            //echo json_encode(array("message" => "Assinatura inválida"));
            //exit;
        }

        $payload = json_decode(base64_decode($parts[0]), true);
        if (time() > $payload["exp"]) {
            echo json_encode(array("message" => "Token expirado"));
            exit;
        }

        // O usuário tem permissão para acessar o recurso
        return true;
    }
}
