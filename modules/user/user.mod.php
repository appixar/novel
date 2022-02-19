<?php

class user
{
    public $error = false;
    public $return = false;
    //------------------------------------------------
    // GET
    //------------------------------------------------
    public function get($body)
    {
        global $_AUTH;
        if (!@$_AUTH['adm'] or !@$body['user_id']) return false;

        $my = new my(dynamic());

        $res = $my->query("SELECT * FROM qmz_user WHERE user_id = :user_id", $body);
        if (!@$res[0]) {
            $this->error = 'E-mail não cadastrado.';
            return false;
        }
        unset($res[0]['user_pass']);
        $this->return = $res[0];
        return $this->return;
    }
    //------------------------------------------------
    // GET PERIOD
    //------------------------------------------------
    public function getPeriod($body)
    {
        global $_AUTH;
        if (!@$_AUTH['api'] and !@$_AUTH['adm']) return false;
        if (!@$body['date_start'] or !@$body['date_end']) return false;

        $my = new my(dynamic());

        $qr = "SELECT u.*, a.addr_cep, a.addr_street, a.addr_number, a.addr_refer, s.state AS addr_state, c.city AS addr_city, d.district AS addr_district FROM qmz_user u "
            . "LEFT JOIN qmz_user_address a ON a.user_id = u.user_id AND addr_date_delete IS NULL "
            . "LEFT JOIN mirror_geo_state s ON s.state_id = a.state_id "
            . "LEFT JOIN mirror_geo_city c ON c.city_id = a.city_id "
            . "LEFT JOIN mirror_geo_district d ON d.district_id = a.district_id "
            . "WHERE user_date_insert BETWEEN :date_start AND :date_end";

        $res = $my->query($qr, $body);
        //
        if (@!$res[0]) return true;
        else {
            unset($res[0]['user_pass']);
            unset($res[0]['user_key']);
            unset($res[0]['user_key_date_insert']);
            unset($res[0]['user_key_date_update']);
            unset($res[0]['user_key_date_expiry']);
            unset($res[0]['group_id']);
            //unset($res[0]['user_vip_update']);
        }
        //
        $this->return = $res;
        return $this->return;
    }
    //------------------------------------------------
    // GET ALL
    //------------------------------------------------
    public function getAll($body)
    {
        global $_AUTH;
        if (!@$_AUTH['adm']) return false;

        $my = new my(dynamic());
        $fields = 'user_id, user_fname, user_lname, user_login, user_phone, user_date_insert, user_status, user_vip_points';
        $res = $my->query("SELECT $fields FROM qmz_user WHERE user_id > 0 ORDER BY user_fname, user_lname", $body);

        $this->return = $res;
        return $this->return;
    }
    //------------------------------------------------
    // CHECK UNIQUE CPF
    // -> $body ['user_cpf']
    //------------------------------------------------
    public function cpf($body)
    {
        // data
        $cpf = clean(clean_spaces(@$body['user_cpf']));
        if (!is_numeric($cpf) or strlen($cpf) != 11) http::die(400, "CPF no formato inválido: $cpf");
        if (!validaCPF($cpf)) http::die(400, "CPF inválido: $cpf");

        // check if cpf exists
        $my = new my(dynamic());
        $res = $my->query("SELECT user_id FROM qmz_user WHERE user_cpf = '$cpf' LIMIT 1");
        if (!@$res[0]) return true;
        else {
            $this->error = 'CPF já cadastrado.';
            return false;
        }
    }
    //------------------------------------------------
    // AUTH WITH LOGIN & PASS
    //------------------------------------------------
    public function auth($body)
    {
        global $_AUTH;
        if (!@$_AUTH) return false;

        $my = new my(dynamic());
        if (@$body['user_login']) {
            $res = $my->query("SELECT * FROM qmz_user WHERE user_login = :user_login", $body);
            if (!@$res[0]) {
                $this->error = 'E-mail não cadastrado.';
                return false;
            }
        } elseif (@$body['user_cpf']) {
            $user_cpf = clean($body['user_cpf']);
            $res = $my->query("SELECT * FROM qmz_user WHERE user_cpf = '$user_cpf'");
            if (!@$res[0]) {
                $this->error = 'CPF não cadastrado.';
                return false;
            }
        }
        if (!password_verify($body['user_pass'], $res[0]['user_pass'])) {
            $this->error = 'Senha incorreta.';
            return false;
        }
        unset($res[0]['user_pass']);
        $this->return = $res[0];
        return $this->return;
    }
    //------------------------------------------------
    // ADMIN AUTH WITH LOGIN & PASS
    //------------------------------------------------
    public function authAdmin($body)
    {
        global $_AUTH;
        if (!@$body['adm_login'] or !@$body['adm_pass']) return false;

        $my = new my();
        $qr = ""
            . "SELECT a.*, g.group_title, c.* FROM qmz_admin a "
            . "INNER JOIN qmz_admin_group g ON g.group_code = a.group_code "
            . "LEFT JOIN qmz_company c ON c.com_code = a.com_code "
            . "WHERE adm_login = :adm_login AND adm_status = 1";
        //
        $res = $my->query($qr, $body);
        if (!@$res[0]) {
            $this->error = 'E-mail não cadastrado.';
            return false;
        }
        if (!password_verify($body['adm_pass'], $res[0]['adm_pass'])) {
            $this->error = 'Senha incorreta.';
            return false;
        }
        unset($res[0]['adm_pass']);

        // COMPANY CONFIG
        if (@$res[0]['com_code']) {
            $my = new my(['id' => 'dynamic', 'wildcard' => $res[0]['com_code']]);
            $conf = $my->query('SELECT * FROM qmz_config WHERE conf_id = 1');
        }

        // APPEND COMPANY DATA
        $data = $res[0];
        $data['conf'] = @$conf[0];

        // RETURN
        $this->return = $data;
        return $this->return;
    }
    //------------------------------------------------
    // POST
    //------------------------------------------------
    public function post($body)
    {
        // dyn db
        $my = new my(dynamic());

        // util
        $ip = getIp();
        $now = date('Y-m-d H:i:s');

        // bday
        $bday = explode("/", $body['user_bday']);
        $bday = "{$bday[2]}-{$bday[1]}-{$bday[0]}";
        $body['user_bday'] = $bday;

        if (!@$body['user_fname'] or !@$body['user_lname'] or !@$body['user_login'] or !@$body['user_cpf']) {
            $this->error = 'Desculpe, dados obrigatórios estão faltando.';
            return false;
        }

        // data
        $user_key = geraSenha(32, true, true, true);
        $data = array(
            'user_fname' => trim(mb_ucwords($body['user_fname'])),
            'user_lname' => trim(mb_ucwords($body['user_lname'])),
            'user_login' => $body['user_login'],
            'user_cpf' => clean($body['user_cpf']),
            'user_pass' => password_hash($body['user_pass'], PASSWORD_DEFAULT), // $verify = password_verify($plaintext_password, $hash);
            'user_genre' => @$body['user_genre'],
            'user_bday' => @$body['user_bday'],
            'user_phone' => clean(clean_spaces(@$body['user_phone'])),
            'user_status' => 1,
            // api key
            'user_key' => $user_key,
            'user_key_date_insert' => $now,
            'user_date_insert' => $now
        );

        // verify if user exists
        $check = $my->query('SELECT user_id FROM qmz_user WHERE user_cpf=:user_cpf OR user_login=:user_login OR user_phone=:user_phone', $data);
        if (@$check[0]) {
            $this->error = 'Desculpe, ocorreu uma falha na verificação dos dados únicos.';
            return false;
        }
        // save data
        $id = $my->insert('qmz_user', $data);
        if (!is_numeric($id)) {
            $this->error = 'Desculpe, ocorreu um erro ao inserir os dados.';
            return false;
        }
        $this->return = array('user_key' => $user_key, 'user_id' => $id);
        return $this->return;
    }
    //------------------------------------------------
    // PUT
    //------------------------------------------------
    public function put($body)
    {
        global $_AUTH;
        if (!@$_AUTH['user'] and (!@$_AUTH['adm'] and !@$body['user_id'])) return false;

        if (@$_AUTH['user']) $user_id = $_AUTH['user']['user_id'];
        elseif (@$_AUTH['adm']) $user_id = $body['user_id'];

        $body['user_id'] = $user_id; // 'check' bugfix
        $email = $body['user_login'];

        // validate new email
        if (!validaMail($email)) {
            $this->error = "Este e-mail é inválido.";
            return false;
        }

        $my = new my(dynamic());

        // check new email
        $check = $my->query("SELECT user_id FROM qmz_user WHERE user_login = :user_login AND user_id != :user_id", $body);
        if (@$check[0]) {
            $this->error = "Desculpe, este e-mail já está sendo usado.";
            return false;
        }

        // data
        $data = array(
            'user_fname' => trim(mb_ucwords($body['user_fname'])),
            'user_lname' => trim(mb_ucwords($body['user_lname'])),
            'user_login' => $email
        );
        if (@isset($body['user_status'])) $data['user_status'] = $body['user_status'];
        if (@isset($body['user_vip_points'])) $data['user_vip_points'] = $body['user_vip_points'];

        // save data
        $my->update('qmz_user', $data, array('user_id' => $user_id));
        return true;
    }
    //------------------------------------------------
    // UPDATE PASS
    //------------------------------------------------
    public function updatePass($body)
    {
        global $_AUTH;
        if (!@$_AUTH['user']['user_id']) return false;

        $old_pass = $body['old_pass'];
        $new_pass = $body['new_pass'];

        // validate lenght
        if (strlen($new_pass) < 4 or strlen($new_pass) > 16) {
            $this->error = "A sua nova senha deve conter entre 4 e 16 caracteres.";
            return false;
        }

        // query
        $my = new my(dynamic());
        $res = $my->query("SELECT user_pass FROM qmz_user WHERE user_id = '{$_AUTH['user']['user_id']}'");
        if (!@$res[0]) {
            $this->error = 'Desculpe, ocorreu um erro ao alterar sua senha.';
            return false;
        }

        // verify
        if (!password_verify($old_pass, $res[0]['user_pass'])) {
            $this->error = 'A senha atual está incorreta.';
            return false;
        }

        $user_key = geraSenha(32, true, true, true); // update key too

        $upd = array(
            'user_key' => $user_key,
            'user_pass' => password_hash($new_pass, PASSWORD_DEFAULT),
        );

        // save data
        $my->update('qmz_user', $upd, array('user_id' => $_AUTH['user']['user_id']));
        $this->return = array('user_key' => $user_key, 'user_id' => $_AUTH['user']['user_id']);
        return $this->return;
    }
    //------------------------------------------------
    // RECOVER PASS WITH EMAIL
    //------------------------------------------------
    public function recover($body)
    {
        global $_AUTH;
        if (!@$body['user_login']) return false;

        $my = new my(dynamic());

        // FIND ACCOUNT
        $res = $my->query("SELECT user_id, user_login, user_fname FROM qmz_user WHERE user_login = :user_login", $body);
        if (!@$res[0]) {
            $this->error = 'E-mail não cadastrado.';
            return false;
        }

        // CODE
        $code = geraSenha(32);
        $ip = getIp();
        $ins = array(
            'rec_code' => $code,
            'user_id' => $res[0]['user_id'],
            'rec_target' => $res[0]['user_login'],
            'rec_date' => date("Y-m-d H:i:s"),
            'rec_ip' => $ip,
            'rec_status' => 1
        );
        $my->insert("qmz_user_recover", $ins);

        // SEND MAIL
        arion::module('mail');
        $mail = new mail();
        $url = "https://cloud.qmoleza.com/_web/recover/{$_AUTH['com']['com_code']}/$code";
        $message = ''
            . '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html xmlns="http://www.w3.org/1999/xhtml">
             <head>
              <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
              <title>Demystifying Email Design</title>
              <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
            </head><body>'
            . '<div style="color:#333;font-family:Arial,sans-serif;font-size:16px;line-height:26px">'
            . '<p>Olá, ' . $res[0]['user_fname'] . '!</p>'
            . '<p>Para recuperar a sua conta, por favor acesse o link abaixo:</p>'
            . '<p><a href="' . $url . '">' . $url . '</a></p>'
            . '<p>Se não foi você que solicitou esta ação, favor desconsiderar este e-mail.</p>'
            . '<p>Obrigado.<br/><strong>' . $_AUTH['conf']['conf_title'] . '</strong><br/>by Qmoleza</p>'
            . '</div>'
            . '</body></html>';
        if ($mail->send($_AUTH['conf']['conf_title'], $res[0]['user_login'], $res[0]['user_fname'], 'Recuperação de senha', $message)) {
            return true;
        }
        return false;
    }
    //------------------------------------------------
    // INCREASE VIP POINTS (METHODS: POST/DELETE)
    //------------------------------------------------
    public function postVip($body)
    {
        global $_AUTH, $_HEADER;
        if (!@$_AUTH['api'] or !is_numeric(@$body['user_vip_points']) or !@$body['user_cpf']) return false;
        $now = date("Y-m-d H:i:s");

        // only int
        $body['user_vip_points'] = intval($body['user_vip_points']);

        // signal
        if ($_HEADER['method'] === 'POST') $signal = "+";
        elseif ($_HEADER['method'] === 'DELETE') $signal = "-";

        // dyn db
        $my = new my(dynamic());

        // check cpf
        $user = $my->query("SELECT * FROM qmz_user WHERE user_cpf = :user_cpf", $body);
        if (!@$user[0]) {
            $this->error = 'User not found';
            return false;
        }

        // check coupon
        if (@$body['coupon']) {
            $coupon = $my->query("SELECT * FROM qmz_user_vip WHERE uv_coupon = :coupon AND uv_signal = '$signal'", $body);
            if (@$coupon[0]) {
                $this->error = "Transaction coupon code already used ($signal)";
                return false;
            }
        }

        // update balance
        $my->query("UPDATE qmz_user SET user_vip_points = IFNULL(user_vip_points, 0) $signal {$body['user_vip_points']}, user_vip_update = '$now' WHERE user_cpf = :user_cpf", $body);

        // check new current balance
        $points = $my->query("SELECT user_vip_points FROM qmz_user WHERE user_cpf = :user_cpf", $body);
        $points = @$points[0]['user_vip_points'];

        // history
        $ins = array(
            'user_id' => $user[0]['user_id'],
            'uv_points' => $body['user_vip_points'],
            'uv_balance' => $points,
            'uv_date' => $now,
            'uv_signal' => $signal,
            'uv_coupon' => @$body['coupon']
        );
        $my->insert('qmz_user_vip', $ins);
        return true;
    }
}
