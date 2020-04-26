<?php

$url = 'https://coronavirus-tracker-api.herokuapp.com/v2/locations';
$json = file_get_contents($url);
$data = json_decode($json, true);

if (empty($data['locations'])) {
    exit('Error: locations not found!' . PHP_EOL);
}

$countries_array = [];
foreach ($data['locations'] as $key => $value) {
    $countries_array[$value['country']][] = $value['id'];
}

ksort($countries_array);

$i = 0;
$result_array = [];
foreach ($countries_array as $key => $value) {
    $result_array[] = [
        'id' => $i,
        'name' => $key,
    ];
    $i++;
}

file_put_contents(__DIR__ . '/data/countries.json', json_encode($result_array, JSON_PRETTY_PRINT));
