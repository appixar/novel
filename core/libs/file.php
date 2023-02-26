<?php
function getDirContents($dir, &$results = array())
{
    $files = scandir($dir);
    foreach ($files as $key => $value) {
        $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
        if (!is_dir($path)) {
            $results[] = $path;
        } else if ($value != "." && $value != "..") {
            getDirContents($path, $results);
            $results[] = $path;
        }
    }
    return $results;
}
function repo_exists($github_url)
{
    $headers = @get_headers($github_url);
    if ($headers[12] != 'HTTP/1.1 200 OK') return false;
    return true;
}
function url_exists($url)
{
    return curl_init($url) !== false;
}

function SizeUnits($bytes, $dec = 2)
{
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, $dec) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, $dec) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, $dec) . ' KB';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' bytes';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' byte';
    } else {
        $bytes = '0 bytes';
    }
    return $bytes;
}

function Size($path)
{
    $bytes = sprintf('%u', filesize($path));
    if ($bytes > 0) {
        $unit = intval(log($bytes, 1024));
        $units = array('B', 'KB', 'MB', 'GB');
        if (array_key_exists($unit, $units) === true) {
            return sprintf('%d %s', $bytes / pow(1024, $unit), $units[$unit]);
        }
    }
    return $bytes;
}

// send entire path structure after ftp connect
function ftp_putAll($conn_id, $src_dir, $dst_dir)
{
    global $ftp_error;
    $d = dir($src_dir);
    // do this for each file in the directory
    while ($file = $d->read()) {
        // to prevent an infinite loop
        if ($file != "." && $file != "..") {
            // do the following if it is a directory
            if (is_dir($src_dir . "/" . $file)) {
                if (!@ftp_chdir($conn_id, $dst_dir . "/" . $file)) {
                    // create directories that do not yet exist
                    if (!ftp_mkdir($conn_id, $dst_dir . "/" . $file)) {
                        $ftp_error++;
                    }
                }
                // recursive part
                ftp_putAll($conn_id, $src_dir . "/" . $file, $dst_dir . "/" . $file);
            }
            // put the files
            else {
                $upload = ftp_put($conn_id, $dst_dir . "/" . $file, $src_dir . "/" . $file, FTP_BINARY);
                if (!$upload) {
                    $ftp_error++;
                }
            }
        }
    }
    $d->close();
}
