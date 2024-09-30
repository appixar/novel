<?php
function save($options = array())
{
    // upload options
    if (!@$options["field"]) $options["field"] = "file"; // field name
    if (!@$options["size"]) $options["size"] = 2; // mb
    if (!@$options["path"]) $options["path"] = Xplend::DIR_ROOT . '/public/upload/';
    if (!@$options["prependName"]) $options["prependName"] = ''; // prepend to random name
    if (@$options["subpath"]) $options["path"] .= $options["subpath"];
    if (!isset($options["type"])) $options["type"] = "image";
    if (!@$options["name"]) $options["name"] = false; // set file name (not random)

    // default messages
    $error_msg = array(
        0 => 'There is no error, the file uploaded with success',
        1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        3 => 'The uploaded file was only partially uploaded',
        4 => 'No file was uploaded',
        6 => 'Missing a temporary folder',
        7 => 'Failed to write file to disk.',
        8 => 'A PHP extension stopped the file upload.',
    );

    $error = false;

    if (empty($_FILES)) {
        $error = "Arquivo não selecionado.";
        goto jump;
    }

    if (!@$_FILES[$options['field']]) {
        $error = "Campo name='{$options['field']}' não encontrado.";
        goto jump;
    }

    if (!file_exists(@$options["path"])) {
        $error = "Diretório não encontrado: '{$options["path"]}'";
        goto jump;
    }

    $image = $_FILES[$options['field']];
    if (@$image['error'] !== 0) {
        $error = @$error_msg[$image['error']];
        goto jump;
    }

    if (!file_exists($image['tmp_name'])) {
        $error = 'Arquivo não recebido pelo servidor.';
        goto jump;
    }

    $maxFileSize = $options['size'] * 10e6; // = 2 000 000 bytes = 2MB
    if ($image['size'] > $maxFileSize) {
        $error = 'Tamanho máximo do arquivo excedido (2mb).';
        goto jump;
    }

    // file ext
    $ext = @end(explode('.', $image['name']));

    // image only
    if ($options["type"] === 'image') {
        $imageData = getimagesize($image['tmp_name']);
        if (!$imageData) {
            $error = 'Imagem inválida.';
            goto jump;
        }
        $mimeType = $imageData['mime'];
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($mimeType, $allowedMimeTypes)) {
            $error = 'Formatos permitidos: PNG, JPG ou GIF.';
            goto jump;
        }
    }
    // specific ext (prevent .php)
    else if ($options["type"] !== $ext) {
        $error = 'Formato permitido: ' . up($options["type"]);
        goto jump;
    }

    // new file name
    if (!$options["name"]) {
        $random_lenght = 16;
        $random_str = substr(str_shuffle(str_repeat($x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($random_lenght / strlen($x)))), 1, $random_lenght);
        $fn = @$options["prependName"] . $random_str . '.' . $ext;
        $path = $options['path'] . '/' . $fn;
    } else {
        $fn = $options["name"] . '.' . $ext;
        $path = $options['path'] . '/' . $fn;
    }

    // save file
    $isUploaded = @move_uploaded_file($_FILES[$options['field']]["tmp_name"], $path);

    if ($isUploaded) return ['file' => $fn];
    else return ['error' => 'Não foi possível mover o arquivo.'];

    jump:
    if ($error) return ['error' => $error];
    else return ['error' => 'Erro desconhecido'];
}
