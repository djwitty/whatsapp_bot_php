<?php

class api_messenger {

    private static $token = 'IWQVoPsgdfc9a96fa4cc359edca05940470f0b9f83';
    private static $lang = 'en';

    function lg() {
        return
                array(
                    'ru' => array(
                        'Город: {CITY}',
                        'Тек. темп.: {CURRENT} C°',
                        'Прогноз на {DATE}:',
                        '{1}C° — {2}C°',
                        'Бот предоставлен https://api-messenger.com',
                        'Город не найден. Возможно Вы допустили ошибку при наборе.'
                    ),
                    'en' => array(
                        'City: {CITY}',
                        'Current temperature: {CURRENT} C°',
                        'Forecast for {DATE}:',
                        '{1}C° — {2}C°',
                        'Bot by https://api-messenger.com',
                        'City not found. Perhaps you made a mistake while typing.'
                    )
        );
    }

    function get_lang($p) {
        if ((int) mb_substr($p, 0, 1, 'utf-8') != 7)
            self::$lang = 'en';
    }

    function send_messages($array) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://app.api-messenger.com/sendmessage?token=' . self::$token);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($array));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json; charset=utf-8'));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        $res = json_decode(curl_exec($curl), true);
    }

    function get_weather($city) {

        if (!empty($city)) {
            $yql_query = 'select * from weather.forecast where woeid in (select woeid from geo.places(1) where text="' . $city . '") and u="c"';
            $yql_query_url = 'http://query.yahooapis.com/v1/public/yql?q=' . urlencode($yql_query) . "&format=json";
            $session = curl_init($yql_query_url);
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
            $phpObj = json_decode(curl_exec($session));
            if (count($phpObj->query->results->channel->item) > 0) {
                $str = array(
                    '{CITY}' => $phpObj->query->results->channel->location->city.', '.$phpObj->query->results->channel->location->country,
                    '{CURRENT}' => $phpObj->query->results->channel->item->condition->temp,
                    '{DATE}' => $phpObj->query->results->channel->item->forecast['0']->date,
                    '{1}' => $phpObj->query->results->channel->item->forecast['0']->low,
                    '{2}' => $phpObj->query->results->channel->item->forecast['0']->high
                );
                $txt = strtr(implode("\r\n", array_slice(self::lg()[self::$lang], 0, count(self::lg()[self::$lang]) - 1)), $str);
            } else
                $txt = array_pop(self::lg()[self::$lang]);
        }

        return
                $txt;
    }

}

$api = new api_messenger;
$body = json_decode(file_get_contents('php://input'));

foreach ($body->messages as $v) {
    if ($v->type == 'chat') {
        $api->get_lang($v->sender);
        $sendArray[] = array('chatId' => $v->sender, 'message' => $api->get_weather($v->body));
    }
}
if (count($sendArray) > 0) {
    $api->send_messages($sendArray);
    unset($sendArray);
}


?>