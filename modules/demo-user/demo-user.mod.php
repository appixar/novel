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
            'user_gender' => @$body['user_genre'],
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
}
