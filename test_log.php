<?php
$lines = file('C:\xampp\apache\logs\error.log');
$lastLines = array_slice($lines, -15);
foreach ($lastLines as $line) {
    echo termwordwrap(trim($line)) . "\n";
}

function termwordwrap($str, $width = 80)
{
    return wordwrap($str, $width, "\n", true);
}
