<?php
// Save debug on session
function debug($class = "", $data = "", $colors = "")
{
  global $_SESSION;
  global $_SERVER;
  global $_APP;
  if ($class == "") {
    $class = "CLASS_NULL";
  }
  if (isset($_SESSION["DEBUG"]) and $_APP['DEBUG']) {
    $data = str_replace("'", '"', $data);
    $url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $_SESSION['DEBUG_DATA'][] = array(
      "deb_data" => $data,
      "deb_class" => $class,
      "deb_color" => $colors,
      "deb_date" => date("H:i:s"),
      "deb_url" => $url
    );
  }
}
function debug_init()
{
  global $debug_init;
  $debug_init = microtime(true); // start clock
}
function debug_end($txt, $class = "")
{
  global $debug_init;
  $time_elapsed_secs = number_format((microtime(true) - $debug_init), 4);
  debug($class, "$txt in $time_elapsed_secs s");
}
