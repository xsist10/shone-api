#!/usr/bin/php
<?php

include '../lib/ShoneSecurity.php';

$oSecurity = new ShoneSecurity('a84b00e68223766724ae527dcea6d7e7a8733768');
$sHash = $oSecurity->submit('CodeIgniter_1.0b');
echo "Hash: " . $sHash . "\n";

$aResult = $oSecurity->get_job($sHash);
print_r($aResult);