#!/usr/bin/php
<?php

require_once '../lib/ShoneSecurity.php';
require_once 'config.php';

try
{
    $oSecurity = new ShoneSecurity($sKey);
    $aResult = $oSecurity->file_fingerprint('path/to/code');
    print_r($aResult);
}
catch (ShoneSecurityException $oException)
{
    echo "Failed: " . $oException->getMessage . "\n";
}