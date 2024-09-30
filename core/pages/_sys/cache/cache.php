<?php

$redis = new Redis();
try {
    // Conecta ao servidor Redis
    $redis->connect(@$_APP['CACHE']['IP'], @$_APP['CACHE']['PORT']);
    // Limpar cache?
    $clear = @$_GET['clear'];
    if ($clear) {
        if ($clear == "*") {
            $clearCount = count($redis->keys('*'));
            $redis->flushAll();
        }
        else {
            $clearCount = 0;
            $keys = $redis->keys('*' . $clear . '*');
            if (!empty($keys)) {
                // Deleta todas as chaves que foram encontradas
                foreach ($keys as $key) {
                    $redis->del($key);
                    $clearCount++;
                }
            }
        }
    }
    // Obtém chave específica
    $key = @$_GET['key'];
    if ($key) $value = $redis->get($key);
    // Obtém todas as chaves do Redis
    else $keys = $redis->keys('*');
} catch (Exception $e) {
    echo "Redis connect error: " . $e->getMessage();
}
