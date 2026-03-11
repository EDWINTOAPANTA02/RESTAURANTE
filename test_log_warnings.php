<?php
$lines = file('C:\xampp\apache\logs\error.log');
$lastLines = array_slice($lines, -30);
foreach ($lastLines as $line) {
    if (strpos($line, 'php:warn') !== false || strpos($line, 'php:error') !== false) {
        echo trim($line) . "\n";
    }
}
