#!/usr/bin/php
<?php

require_once '../lib/ShoneSecurity.php';
require_once 'config.php';

$oSecurity = new ShoneSecurity($sKey);
$sHash = $oSecurity->submit('path/to/code');
echo "Hash: " . $sHash . "\n";

$aResult = $oSecurity->get_job($sHash);
print_r($aResult);