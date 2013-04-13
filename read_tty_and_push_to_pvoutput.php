<?php

$pvoutput_key = ""; // Add your API key here
$pvoutput_sid = ""; // Add your system id here

// Init output variable
$output = array();

// Get the data from the serial port
exec( "stty -F /dev/ttyUSB0 9600 evenp cooked inlcr; head /dev/ttyUSB0 -n 20", $output );

$data = array();
$next_line_is_gas_data = false;
foreach( $output as $line ) {
  $line = trim( $line );
  if ( $next_line_is_gas_data ) {
    $data["gas_usage"] = floatval( preg_replace('/[^0-9.]+/', '', $line ) );
    $next_line_is_gas_data = false;
    continue;
  } 
  preg_match( "/[01]-[01]:([0-9\.]+)\((.+)\)/", $line, $matches );
  if ( !isset( $matches[1] ) ) continue;
  $matches[2] = preg_replace('/[^0-9.]+/', '', $matches[2] );
  switch( $matches[1] ) {
    case "1.8.1": $data["usage_night"] = floatval( $matches[2] );  break;
    case "1.8.2": $data["usage_day"] = floatval( $matches[2] ); break;
    case "2.8.1": $data["return_night"] = floatval( $matches[2] ); break;
    case "2.8.2": $data["return_day"] = floatval( $matches[2] ); break;
    case "1.7.0": $data["current_usage"] = floatval( $matches[2] ); break;
    case "2.7.0": $data["current_return"] = floatval( $matches[2] ); break;
    case "24.3.0": $next_line_is_gas_data = true; break;
  }
}

// Some stats
$data["total_return"] = $data["return_night"] + $data["return_day"];
$data["total_usage"] = $data["usage_night"] + $data["usage_day"];
$data["profit"] = $data["total_return"] - $data["total_usage"];
$data["profit_ratio"] =  $data["total_return"] / $data["total_usage"];

$get_variables = array(
  "key" => $pvoutput_key,
  "sid" => $pvoutput_sid,
  "d" => date("Ymd"),
  "t" => date("H:i"),
  "c1" => 1,
  "v1" => $data["total_return"]*1000,
  "v2" => $data["current_return"]*1000,
  "v3" => $data["total_usage"]*1000,
  "v4" => $data["current_usage"]*1000,
  "v7" => $data["gas_usage"]
);

print file_get_contents( "http://pvoutput.org/service/r2/addstatus.jsp?" . http_build_query( $get_variables ) );
print PHP_EOL;

?>
