#!/usr/bin/php
<?php

require_once '../lib/ShoneSecurity.php';
require_once 'config.php';

$oSecurity = new ShoneSecurity($sKey);
$aResult = $oSecurity->file_fingerprint('path/to/code');
print_r($aResult);