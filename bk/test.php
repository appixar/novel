<?php

function compare_directories_recursive($dir1, $dir2)
{
  $only_exist_2 = array();
  $different = array();
  $only_exist_1 = array();

  $files1 = scandir($dir1);
  $files2 = scandir($dir2);

  foreach ($files1 as $file) {
    if (in_array($file, array(".", ".."))) {
      continue;
    }

    if (!in_array($file, $files2)) {
      $only_exist_1[] = "$dir1/$file";
    } else {
      $file1 = $dir1 . "/" . $file;
      $file2 = $dir2 . "/" . $file;
      if (is_dir($file1)) {
        $results = compare_directories_recursive($file1, $file2);
        $only_exist_2 = array_merge($only_exist_2, $results['only_exist_2']);
        $different = array_merge($different, $results['different']);
        $only_exist_1 = array_merge($only_exist_1, $results['only_exist_1']);
      } else {
        $hash1 = md5(file_get_contents($file1));
        $hash2 = md5(file_get_contents($file2));
        if ($hash1 !== $hash2) {
          $different[] = "$dir2/$file";
        }
      }
    }
  }

  foreach ($files2 as $file) {
    if (in_array($file, array(".", ".."))) {
      continue;
    }

    if (!in_array($file, $files1)) {
      $only_exist_2[] = "$dir2/$file";
    }
  }

  return array(
    "only_exist_1" => $only_exist_1,
    "only_exist_2" => $only_exist_2,
    "different" => $different
  );
}
$diff = compare_directories_recursive("src/modules/mysql", "mysql");
print_r($diff);
exit;

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);
$redis->set("chave", "valor");
echo $redis->get("chave");

exit;

$yaml = <<<EOD
---
invoice: 34843
date: "2001-01-23"
bill-to: &id001
  given: Chris
  family: Dumars
  address:
    lines: |-
      458 Walkman Dr.
              Suite #292
    city: Royal Oak
    state: MI
    postal: 48046
ship-to: *id001
product:
- sku: BL394D
  quantity: 4
  description: Basketball
  price: 450
- sku: BL4438H
  quantity: 1
  description: Super Hoop
  price: 2392
tax: 251.420000
total: 4443.520000
comments: Late afternoon is best. Backup contact is Nancy Billsmer @ 338-4338.
...
EOD;

$parsed = yaml_parse($yaml);
print_r($parsed);
exit;

// get .env data
$_ENV = parse_ini_file('.env', true);
print_r($_ENV);
