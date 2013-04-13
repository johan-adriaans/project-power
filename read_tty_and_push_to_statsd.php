<?php

require( dirname( __FILE__ ) . "/lib/statsd.php" );

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

StatsD::gauge( "power.used", $data["current_usage"] * 1000 );
StatsD::gauge( "power.generated", $data["current_return"] * 1000 );
StatsD::gauge( "power.total_return", $data["total_return"] * 1000 );
StatsD::gauge( "power.total_usage", $data["total_usage"] * 1000 );
StatsD::gauge( "power.profit", $data["profit"] * 1000 );
StatsD::gauge( "power.profit_ratio", $data["profit_ratio"] );
StatsD::gauge( "gas.usage", $data["gas_usage"] );

print "Data sent" . PHP_EOL;

?>
