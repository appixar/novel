<div class="mb-4">
    <a href="?clear=*" class="btn btn-sm btn-secondary">Clear cache</a>
    <a href="#" class="clear-contains btn btn-sm btn-secondary">Clear cache contains</a>
</div>
<pre style="line-height:18px">
<?php
if ($clear) {
    echo "* $clearCount keys containing '<span class='red'>$clear</span>' have been removed.\n\n\n";
}
// Exibe chave específica
if ($key) {
    echo "Key: $key\n\n\n";
    echo "<textarea style='width:100%;height:350px'>$value</textarea>";
}
// Exibe todas as chaves
else {
    echo "Total cache keys: " . count($keys) . "\n\n\n";
    $totalSize = 0; // Inicializa a variável para armazenar o tamanho total
    if (!empty($keys)) {
        foreach ($keys as $key) {
            $value = $redis->get($key);
            $ttl = $redis->ttl($key);
            $sizeInBytes = strlen($value);
            $totalSize += $sizeInBytes;
            $keyEncoded = urlencode($key);
            echo "<a href='/_sys/cache/?key=$keyEncoded' target='_blank' style='text-decoration:none'><span class='green'>$key</span></a>\n";
            echo ($ttl == -1 ? "<span class='gray'>No expire</span>" : "$ttl secs") . " - " . round($sizeInBytes / 1024, 2) . " KB\n";
            echo "<hr>";
        }

        // Exibe o tamanho total do cache
        echo "Total size: " . number_format($totalSize / 1024 / 1024, 2, ".", ",") . " MB\n";
    } else {
        echo "Cache is empty.\n";
    }
}
?>
</pre>