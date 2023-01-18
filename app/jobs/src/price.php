<?php
// START ARION FRAMEWORK
include __DIR__ . "/../../../core/autoload.php";
new arion();
$my = new my();

// START JOB
$job = new job(true); // true = ignore path permissions
$job->start();
$job->say('CRON START', true, true);

// RUN ADVFN MODULE
arion::module('advfn');
$advfn = new advfn();

// GET SYMBOLS
$res = $my->query('SELECT * FROM ox_symbol WHERE sym_status = 1');
foreach ($res as $r) {
    $current_time = date("H:i");
    $job->say("* {$r['sym_title']} ({$r['sym_prefix']}) => {$r['sym_month_future']} months");
    // FUTURE MONTHS LOOP
    for ($i = 0; $i < $r['sym_month_future']; $i++) {
        // GET FULL SYMBOL (MONTH+YEAR)
        $month = date('m', strtotime("first day of +$i month"));
        $month_x = intval($month) - 1; // array pos
        $year = date('y', strtotime("first day of +$i month"));
        $month_letters = explode(",", $r['sym_month']);
        @$symbol = $r['sym_prefix'] . $month_letters[$month_x] . $year;
        // CHECK TIME INTERVAL
        if ($current_time >= $r['sym_time_start'] && $current_time <= $r['sym_time_end']) {
            // LOGIN BEFORE GET
            if (!@$login) $login = $advfn->login();
            // GET
            $job->say("[$symbol] LOCATING PRICE...");
            $content = $advfn->get("https://br.advfn.com/bolsa-de-valores/bmf/$symbol/cotacao");
            // INTEGRITY 1: DIE
            if (!$content) {
                $job->error("[$symbol] CONTENT NOT FOUND. EXIT.");
                $job->end();
            }
            if (@explode('Página Não Encontrada', $content)[1]) {
                $job->error("[$symbol] PAGE NOT FOUND. EXIT.");
                $job->end();
            }
            if (!@explode('"premium"', $content)[1]) {
                $job->error("[$symbol] PREMIUM DISABLED. EXIT.");
                $job->end();
            }
            // GET HTML DATA
            $price_buy = @explode('<span id="quoteElementPiece6"', $content)[1];
            if ($price_buy) {
                $price_buy = explode('</span>', $price_buy)[0];
                $price_buy = explode('>', $price_buy)[1];
                $price_buy = moneyToFloat($price_buy);
            }
            $price_sell = @explode('<span id="quoteElementPiece7"', $content)[1];
            if ($price_sell) {
                $price_sell = explode('</span>', $price_sell)[0];
                $price_sell = explode('>', $price_sell)[1];
                $price_sell = moneyToFloat($price_sell);
            }
            // INTEGRITY 2: JUMP TO NEXT
            if (@explode('429 Too Many Requests', $content)[1]) {
                $job->error("[$symbol] TOO MANY REQUESTS. SLEEP 15. NEXT...");
                sleep(15);
                goto nextSymbol;
            }
            if ($price_buy == 0 or $price_sell == 0) {
                $job->error("[$symbol] PRICE NOT FOUND. NEXT...");
                goto nextSymbol;
            }
            if ($price_buy === $price_sell) {
                $job->error("[$symbol] PRICES ARE EQUAL ($price_buy). NEXT...");
                goto nextSymbol;
            }
            // INSERT
            $ins = array(
                'price_symbol' => $symbol,
                'price_buy' => $price_buy,
                'price_sell' => $price_sell,
                'price_date_insert' => date("Y-m-d H:i:s")
            );
            $price_id = $my->insert('ox_price', $ins);
            $job->say("[$symbol] ID=$price_id, BUY=$price_buy, SELL=$price_sell", false, true, 'green');
            // NEXT
            nextSymbol:
            sleep(3); // prevent too many requests
        }
    }
}

$job->end();
