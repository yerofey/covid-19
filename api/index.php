<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');


$data_dir = __DIR__ . '/../grabbers/data';
$result_array = [];

$_GET = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
$get_id = (isset($_GET['id']) && is_numeric($_GET['id'])) ? $_GET['id'] : false;
$get_method = $_GET['method'] ?? '';
//$get_pretty = (isset($_GET['pretty']) && $_GET['pretty'] === 1) ? 1 : 0;
$found = false;

if (!empty($get_method)) {
    switch ($get_method) {
        case 'country':
            if ($get_id !== false) {
                $file = $data_dir . '/country-' . $get_id . '.json';
                if (is_numeric($get_id) && is_file($file)) {
                    $found = true;
                    $data = file_get_contents($file);
                    $result_array = json_decode($data, true);
                }
            }
            break;

        case 'global':
            $file = $data_dir . '/global.json';
            if (is_file($file)) {
                $found = true;
                $data = file_get_contents($file);
                $result_array = json_decode($data, true);
            }
            break;

        case 'latest':
            $file = $data_dir . '/latest.json';
            if (is_file($file)) {
                $found = true;
                $data = file_get_contents($file);
                $result_array = json_decode($data, true);
            }
            break;
    }
}

if (!$found) {
    $array['error'] = [
        'code' => 404,
        'message' => 'Not found',
    ];
}

echo json_encode($result_array, JSON_UNESCAPED_SLASHES); // | JSON_PRETTY_PRINT
