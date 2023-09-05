<?php
// Save debug on session
class Debug
{
  public function __construct($class = "", $data = "", $colors = "")
  {
    global $_SESSION;
    global $_SERVER;
    global $_APP;
    if ($class == "") $class = "CLASS_NULL";

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
}
