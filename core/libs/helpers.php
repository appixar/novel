<?php
// Back to the last url
function back($redirect = true, $appendToUrl = '')
{
    if ($redirect) header("Location: " . $_SERVER['HTTP_REFERER'] . $appendToUrl);
    else return $_SERVER['HTTP_REFERER'] . $appendToUrl;
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
    global $_SESSION, $_GET;

    // pending cb?
    $url = explode("?", $_SERVER['REQUEST_URI'])[0];
    if (@$_GET['error']) {
        $_SESSION['cb'][] = ['type' => 'danger', 'text' => $_GET['error']];
        echo "<script>window.location.href='$url'</script>";
        exit;
    }
    if (@$_GET['success']) {
        $_SESSION['cb'][] = ['type' => 'success', 'text' => 'Alterações efetuadas com sucesso.'];
        echo "<script>window.location.href='$url'</script>";
        exit;
    }
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
            if ($type == "danger") $ico = "remove";
        }
        // text
        $text = $data['text'];
        // print
        echo "<div class='alert alert-$type'><i class='fa fa-$ico'></i> &nbsp; $text</div>";
        // repost?
        if (@$data['repost']) {
            $script = "<script>function repost() {";
            foreach ($data['repost'] as $name => $val) {
                $script .= "document.querySelector('input[name=$name]').value = '" . addslashes($val) . "';";
            }
            $script .= "}setTimeout(function() { repost(); },250);</script>";
            echo $script;
        }
        // remove current cb
        unset($_SESSION['cb'][$k]);
        jump:
    }
}

// Form select option
function option($current, $selected)
{
    $data = "value='$current'";
    if ($current === $selected) $data .= " selected";
    echo $data;
}
