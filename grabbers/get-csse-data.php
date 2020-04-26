<?php

function buildResult($csv)
{
    $lines = explode(PHP_EOL, $csv);

    $header = array_slice(explode(',', array_slice($lines, 0, 1)[0]), 4);
    $content = array_filter(array_slice($lines, 1));

    // example with no province
    $first_item = array_filter(explode(',', array_slice($content, 0, 1)[0]), 'filterEmptyValues');
    $location_without_province_items = count($first_item);
    $location_with_province_items = $location_without_province_items + 1;

    // fix dates (Y-m-d)
    foreach ($header as $key => $value) {
        $temp = explode('/', $value);
        $date = implode('-', [
            '20' . $temp[2],
            str_pad($temp[0], 2, 0, STR_PAD_LEFT),
            str_pad($temp[1], 2, 0, STR_PAD_LEFT)
        ]);
        $header[$key] = $date;
    }

    // locations grouped by country
    $prepared_array = [];
    foreach ($content as $line) {
        if (substr($line, 0, 1) == ',') {
            $line = substr($line, 1);
        }

        $line_data = [];
        $start = '';
        $string = '';

        // fix comma in country name
        $temp = explode('",', $line);
        $was_fixed = false;

        if (count($temp) === 2) {
            $start = str_replace('"', '', $temp[0]);
            $string = $temp[1];
            $was_fixed = true;
        } else {
            $string = $temp[0];
        }

        $line_data = array_filter(explode(',', $string), 'filterEmptyValues');
        if (!empty($start)) {
            array_unshift($line_data, $start);
        }
        $line_items = count($line_data);

        $row_country = '';
        $row_province = '';
        $row_latitude = '';
        $row_longitude = '';
        $row_values = [];

        if ($line_items == $location_with_province_items) {
            $row_country = $line_data[1];
            $row_province = $line_data[0];
            $row_latitude = $line_data[2];
            $row_longitude = $line_data[3];
            $row_values = array_slice($line_data, 4);
        } elseif ($line_items == $location_without_province_items) {
            $row_country = $line_data[0];
            $row_latitude = $line_data[1];
            $row_longitude = $line_data[2];
            $row_values = array_slice($line_data, 3);
        } else {
            //
        }

        $prepared_array[$row_country][] = [
            'province'  => $row_province,
            // 'lat'       => $row_latitude,
            // 'lon'       => $row_longitude,
            'values'    => $row_values,
        ];
    }

    $result_array = [];
    foreach ($prepared_array as $country_name => $locations_array) {
        $temp_array = [];
        
        foreach ($locations_array as $location) {
            $i = 0;

            foreach ($location['values'] as $value) {
                $date = $header[$i];
                if (!isset($temp_array[$date])) {
                    $temp_array[$date] = 0;
                }
                $temp_array[$date] += $value;
                $i++;
            }
        }

        $latest_value = empty($temp_array) ? 0 : end($temp_array);

        $result_array[$country_name] = [
            'latest'    => $latest_value,
            'timeline'  => $temp_array,
        ];
    }

    return $result_array;
}

function filterEmptyValues($var)
{
    return ($var !== null && $var !== false && $var !== '');
}


$data_dir = __DIR__ . '/data';

$countries = json_decode(file_get_contents($data_dir . '/countries.json'), true);

$url_template = 'https://raw.githubusercontent.com/CSSEGISandData/COVID-19/master/csse_covid_19_data/csse_covid_19_time_series/time_series_covid19_{{type}}_global.csv';
$types = [
    'confirmed',
    'deaths',
    'recovered',
];
$countries_array = [];
$global_array = [];
$latest_array = [];

foreach ($types as $type) {
    $type_url = str_replace('{{type}}', $type, $url_template);
    // TODO: curl get
    $csv = file_get_contents($type_url);
    $type_array = buildResult($csv);

    $latest_array['latest'][$type] = 0;

    foreach ($type_array as $country_name => $country_data) {
        $country = [];

        foreach ($countries as $country) {
            if ($country['name'] == $country_name) {
                break;
            }
        }

        $country_id = $country['id'];

        $countries_array[$country_id]['latest'][$type] = $country_data['latest'];
        $countries_array[$country_id]['timelines'][$type] = $country_data['timeline'];

        $latest_array['latest'][$type] += $country_data['latest'];

        if (!isset($latest_array['countries'][$country_id])) {
            $latest_array['countries'][$country_id] = [
                'name' => $country_name,
                'data' => [],
            ];
        }

        $latest_array['countries'][$country_id]['data'][$type] = $country_data['latest'];
    }

    $global_array[$type] = $type_array;
}


// countries
foreach ($countries_array as $country_id => $country_data) {
    file_put_contents($data_dir . '/country-' . $country_id . '.json', json_encode($country_data));
}

// global
file_put_contents($data_dir . '/global.json', json_encode($global_array));

// latest
file_put_contents($data_dir . '/latest.json', json_encode($latest_array));
