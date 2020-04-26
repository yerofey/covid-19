<?php

$countries_codes = [
    '*',
    'RU',
    'US',
];
$prediction_days = 7;

$data_dir = __DIR__ . '/data';

$date_ymd = date('Y-m-d');

$today_file = $data_dir . '/' . $date_ymd . '.json';
$default_json = '{}';
$json = $default_json;

$from_cache = false;

if ($from_cache && is_file($today_file)) {
    $json = file_get_contents($today_file);
} else {
    $url = 'https://coronavirus-tracker-api.herokuapp.com/all';
    $api_json = file_get_contents($url) ?? $default_json;
    if ($api_json != $default_json) {
        file_put_contents($today_file, $api_json);
        $json = $api_json;
    }
}

$api_data = json_decode($json, true);
if (empty($api_data)) {
    exit();
}


function mdksort(array &$array = [], int $sort_flag = SORT_ASC)
{  
    if (empty($array)) {
        return false;
    }

    foreach ($array as $key => $value) {
        if (!is_array($value)) {
            continue;
        }

        ksort($value, $sort_flag);
        $array[$key] = $value;
    }
}

/*
Array
(
    [0] => confirmed
        Array
        (
            [0] => last_updated
            [1] => latest
            [2] => locations
            [3] => source
        )
    [1] => deaths
        Array
        (
            [0] => last_updated
            [1] => latest
            [2] => locations
            [3] => source
        )
    [2] => latest
        Array
        (
            [confirmed] => 119303
            [deaths] => 4290
            [recovered] => 64411
        )
    [3] => recovered
        Array
        (
            [0] => last_updated
            [1] => latest
            [2] => locations
            [3] => source
        )
)
*/


$countries_array = [];
$global_array = [];


foreach ($api_data as $status_key => $status_data) {
    if (!empty($status_data['locations'])) {
        foreach ($status_data['locations'] as $location) {
            $history_array = [];
            if (!empty($location['history'])) {
                foreach ($location['history'] as $key => $value) {
                    $history_row_timestamp = \DateTime::createFromFormat('m/d/y', $key)->getTimestamp();
                    $history_array[$history_row_timestamp] = $value;

                    if (!isset($global_array[$status_key][$history_row_timestamp])) {
                        $global_array[$status_key][$history_row_timestamp] = 0;
                    }

                    $global_array[$status_key][$history_row_timestamp] += $value;
                }

                ksort($history_array, SORT_NUMERIC);
            }

            if (!isset($countries_array[$location['country_code']]['country'])) {
                $countries_array[$location['country_code']]['country'] = $location['country'];
            }

            $countries_array[$location['country_code']]['data'][$status_key] = [
                'count'     => $location['latest'],
                'history'   => $history_array,
                'updated'   => date('Y-m-d H:i:s', strtotime($status_data['last_updated'])),
            ];
        }
    }
}


mdksort($global_array, SORT_NUMERIC);


$selected_countries_array = [];
foreach ($countries_codes as $code) {
    echo $code . PHP_EOL;

    $history_results = [];

    if ($code == '*') {
        $country_name = 'the World';
        $history_results = $global_array['confirmed'];
    }

    if (isset($countries_array[$code])) {
        $country_array = $countries_array[$code];
        $country_name = $country_array['country'];
        $result_array = $country_array['data'];
        $history_results = $result_array['confirmed']['history'] ?? [];
    }

    if (!empty($history_results)) {
        $history_diff = [];
        $last_result = [];
        $previous_value = 0;

        foreach ($history_results as $key => $value) {
            $diff = 0;
            if (!empty($value) && !empty($previous_value)) {
                $diff = $value / $previous_value;
            }

            $history_diff[$key] = [
                'value' => $value,
                'diff'  => $diff,
            ];
            $last_result = [
                'timestamp' => $key,
                'value'     => $value,
            ];
            $previous_value = $value;
        }

        $new_diff = 0;
        $latest_diffs_array = array_slice($history_diff, -3);
        $future_days_values = [];

        $temp_array = [];
        $prev_diff = array_slice($history_diff, -4, 1)[0]['diff'];

        foreach ($latest_diffs_array as $key => $value) {
            $future_days_values[] = number_format($prev_diff - $value['diff'], 10);
            $prev_diff = $value['diff'];
        }
        
        $last_day_trand = $future_days_values[count($future_days_values) - 1];
        $last_day_diff = $prev_diff;

        $debug_calculations_array = [];
        $predicted_array = [];
        $last_timestamp = $last_result['timestamp'];
        $last_value = $last_result['value'];

        $calc = $last_day_diff + $last_day_trand;

        foreach (range(0, $prediction_days) as $value) {
            $last_timestamp += 86400;            
            $last_value = $last_value * $calc;
            $predicted_array[$last_timestamp] = $last_value;
            $calc += $last_day_trand;
        }

        echo 'Prediction: ' . date('j M', $last_timestamp) . ' - ' . number_format($last_value, 0, '.', ',') . ' cases in ' .  $country_name . PHP_EOL;
    }
    
    echo PHP_EOL;
}
