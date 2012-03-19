#!/usr/bin/php
<?php

require_once '../lib/ShoneSecurity.php';
require_once 'config.php';

try
{
    $oSecurity = new ShoneSecurity($sKey);
    $sHash = $oSecurity->submit('CodeIgniter_1.1b');
    echo "Hash: " . $sHash . "\n";

    $aResult = $oSecurity->get_job($sHash);
    print_r($aResult);
}
catch (ShoneSecurityException $oException)
{
    echo "Failed: " . $oException->getMessage() . "\n";
}