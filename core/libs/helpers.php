<?php
//-------------------------------------
// GENERAL UI HELPERS
//-------------------------------------
// Form options
class FormOptions
{
    public $selectedValue;
    public function __construct($selectedValue)
    {
        $this->selectedValue = $selectedValue;
    }
    public function option($val, $name = false, $appendToTag = '')
    {
        if (!$name) $name = $val;
        $sel = ($val == $this->selectedValue) ? 'selected' : '';
        echo "<option $sel value='$val' $appendToTag>$name</option>";
    }
}

// Back to the last url
function back($modify_url = "")
{
    $url = $_SERVER['HTTP_REFERER'];
    $rule = $modify_url;
    if ($rule) {
        $firstChar = substr($rule, 0, 1);
        $removeFirst = substr($rule, 1);
        if ($rule === '-?') $url = explode("?", $url)[0];
        elseif ($firstChar === '-') $url = str_replace($removeFirst, '', $url);
        elseif ($firstChar === '+') $url .= $removeFirst;
    }
    header("Location: $url");
    exit;
}
//--------------------------------------------------
// call back alerts (need bootstrap)
//--------------------------------------------------
// format:
// [cb]
//     [type]   = success, warning, info, danger
//     [ico]    = (font awesome)
//     [text]   = text
//     [target] = cb page position (target id)
//--------------------------------------------------
function cb($target = '')
{
    global $_SESSION;
    // pending cb?
    if (!@$_SESSION['cb']) return;
    // loop cbs
    $cb = (object) $_SESSION['cb'];
    foreach ($cb as $k => $data) {
        // wrong target?
        if ($target and @$data['target'] and ($target !== @$data['target'])) goto jump;
        // default
        $type = @$data['type'];
        if (!$type) $type = "success";
        // icons
        $ico = @$data['ico'];
        if (!$ico) {
            if ($type == "success") $ico = "check";
            if ($type == "warning") $ico = "warning";
            if ($type == "info") $ico = "info-circle";
            if ($type == "danger") $ico = "times-circle";
        }
        // text
        $text = $data['text'];
        // print
        echo "<div class='alert alert-$type'><i class='fa fa-$ico'></i> $text</div>";
        // remove current cb
        unset($_SESSION['cb'][$k]);
        jump:
    }
}
function makeCb($res = 1, $customMessage = "")
{
    $now = date("Y-m-d H:i:s");
    if ($res === true or $res === 1 or @$res['success']) {
        $type = "success";
        if (!$customMessage) $customMessage = "Alterações efetuadas com sucesso.";
    } else {
        $type = "warning";
        if (!$customMessage) $customMessage = "Por favor, verifique os campos e tente novamente.";
    }
    $_SESSION['cb'][] = [
        "type" => $type,
        "text" => "$customMessage ($now)"
    ];
}
// Estados brasileiros
function uf()
{
    $uf = array(
        'AC' => 'Acre',
        'AL' => 'Alagoas',
        'AP' => 'Amapá',
        'AM' => 'Amazonas',
        'BA' => 'Bahia',
        'CE' => 'Ceará',
        'DF' => 'Distrito Federal',
        'ES' => 'Espírito Santo',
        'GO' => 'Goiás',
        'MA' => 'Maranhão',
        'MT' => 'Mato Grosso',
        'MS' => 'Mato Grosso do Sul',
        'MG' => 'Minas Gerais',
        'PA' => 'Pará',
        'PB' => 'Paraíba',
        'PR' => 'Paraná',
        'PE' => 'Pernambuco',
        'PI' => 'Piauí',
        'RJ' => 'Rio de Janeiro',
        'RN' => 'Rio Grande do Norte',
        'RS' => 'Rio Grande do Sul',
        'RO' => 'Rondônia',
        'RR' => 'Roraima',
        'SC' => 'Santa Catarina',
        'SP' => 'São Paulo',
        'SE' => 'Sergipe',
        'TO' => 'Tocantins'
    );
    return $uf;
}
