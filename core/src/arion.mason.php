<?php
/*
* Example:
*      > php mason domain.com --restart www.domain.com test --blau=12345
*
* Results:
*      $argx
*          ['--restart'] = true
*          ['--blau'] = 12345
*      $args
*          [0] = domain.com
*          [1] = www.domain.com (1! jumps --restart)
*          [2] = test
*/
class mason extends arion
{
    const DIR_CMD = __DIR__ . '/cmd/';

    public function __construct()
    {
        global $argv, $_APP;

        // TERMINAL ONLY
        if (PHP_SAPI !== 'cli' or isset($_SERVER['HTTP_USER_AGENT'])) die($this->say('Console only.'));
        if (!isset($argv[1])) die(ARION_VERSION . PHP_EOL);

        // INCLUDE ALL CMD
        $files = scandir(self::DIR_CMD);
        for ($i = 0; $i < count($files); $i++) {
            $f = self::DIR_CMD . $files[$i];
            if (is_file($f)) require_once($f);
        }

        // INVOKE CMD CLASS
        if (class_exists($argv[1])) new $argv[1]();
    }
    // RETURN ARGS OR ARGX
    public static function argx()
    {
        return self::args(true);
    }
    // RETURN ARGS OR ARGX
    public static function args($return_argx = false)
    {
        global $argv;
        $args = array();
        $argx = array();

        // BUILD ARGX & ARGS
        for ($i = 1; $i < count($argv); $i++) {
            $param = $argv[$i];
            if (substr($param, 0, 2) === '--') {
                $equal = @explode('=', $param)[1];
                if ($equal) $argx[explode('=', $param)[0]] = $equal;
                else $argx[$param] = true;
            } else $args[] = $param;
        }

        // RETURN ARGS OR ARGX?
        if ($return_argx) return $argx;
        else return $args;
    }
    // AUTOLOAD METHOD BASED IN FIRST PARAM
    public static function autoload($parentClass, $appendArg = false, $valueRequired = false)
    {
        $args = self::args();
        if (!@$args[1]) die(self::say('Missing parameters.'));
        if (!method_exists(get_class($parentClass), @$args[1])) die(self::say('Command not found.'));
        if ($appendArg) {
            if ($valueRequired and !@$args[2]) die(self::say('Missing parameters.'));
            if (@$args[2]) $parentClass->{$args[1]}($args[2]);
            else $parentClass->{$args[1]}();
        } else $parentClass->{$args[1]}();
    }

    public static function say($text, $header = false, $color = '')
    {
        $header_width = 50;
        $header_symbol = "·";
        $colors = array(
            'header' => "\033[95m",
            //
            'pink' => "\033[94m",
            'cyan' => "\033[36m",
            'green' => "\033[92m",
            'yellow' => "\033[93m",
            'red' => "\033[91m",
            'blue' => "\033[1m",
            'magenta' => "\033[35m",
            //
            'blink' => "\033[5m",
            'strong' => "\033[1m",
            'u' => "\033[4m",
            'end' => "\033[0m"
        );
        foreach ($colors as $k => $v) {
            $text = str_replace("<$k>", $v, $text);
            $text = str_replace("</$k>", $colors['end'], $text);
        }

        if (!$color) $c = '';
        else $c = $colors[$color];

        // OPEN HEADER BAR
        if ($header) {
            $_content = $c . str_repeat($header_symbol, $header_width) . $colors['end'];
            echo $_content . PHP_EOL;
        }

        // TEXT
        $_content = "{$c}$text{$colors['end']}";
        echo $_content . PHP_EOL;

        // CLOSE HEADER BAR
        if ($header) {
            $_content = $c . str_repeat($header_symbol, $header_width) . $colors['end'];
            echo $_content . PHP_EOL;
        }
    }
}
