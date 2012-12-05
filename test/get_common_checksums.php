#!/usr/bin/php
<?php

require_once '../lib/ShoneSecurity.php';
require_once 'config.php';

try
{
    $oSecurity = new ShoneSecurity($sKey);
    $aResult = $oSecurity->get_common_checksums();
    print_r($aResult);
}
catch (ShoneSecurityException $oException)
{
    echo "Failed: " . $oException->getMessage() . "\n";
}