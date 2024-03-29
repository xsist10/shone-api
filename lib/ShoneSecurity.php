<?php

/**
 * Library to connect to the Shone API
 *
 * @author Thomas Shone
 */
class ShoneSecurity
{
    #-> Constants
    const USER_AGENT   = 'Shone PHP Client';
    const VERSION      = '1.0 PHP';
    const API_ENDPOINT = 'https://www.shone.co.za/';

    const RESULT_SUCCESS = 'Success';
    const RESULT_FAILED  = 'Failed';

    #-> API Key
    private $sKey = '';

    #-> Common Checksums
    private static $aCommonChecksums = array();

    #-> Scanning
    private $sRootFolder = '';

    #---------------------------------------------------------------------------
    #-> Magic Functions
    public function __construct($sKey)
    {
        $this->sKey = $sKey;
    }

    #---------------------------------------------------------------------------
    #-> Private Functions

    /**
     * Call the remote API with a GET request
     *
     * @param string $sPage
     * @param array $aArguments
     * @return array
     * @throws ShoneSecurityException
     */
    private function _get($sPage, $aArguments = array())
    {
        $aParam = array();
        if (count($aArguments))
        {
            foreach ($aArguments as $sKey => $sValue)
            {
                $aParam[] = urlencode($sKey) . '=' . urlencode($sValue);
            }
        }
        $sUrl = self::API_ENDPOINT . $sPage
              . '?key=' . $this->sKey
              . '&encode=json'
              . (!empty($aParam) ? '&' . implode('&', $aParam) : '');
        $sResult = file_get_contents($sUrl);
        echo $sUrl . "\n";

        if (!$sResult)
        {
            throw new ShoneSecurityException('Empty Result');
        }

        $aResult = json_decode($sResult);
        if (empty($aResult))
        {
            throw new ShoneSecurityException('Malformed or empty JSON');
        }

        return $aResult;
    }

    /**
     * Call the remote API with a POST request
     *
     * @param string $sPage
     * @param array $aArguments
     * @return array
     * @throws ShoneSecurityException
     */
    private function _post($sPage, $aArguments = array())
    {

        // Generate XML file
        //http://www.shone.co.za/index.php?page=job.submit&key=&job=[job]&encode=json
        $aParam = array();
        if (count($aArguments))
        {
            foreach ($aArguments as $sKey => $sValue)
            {
                $aParam[] = urlencode($sKey) . '=' . urlencode($sValue);
            }
        }
        $sUrl = self::API_ENDPOINT . $sPage
              . '?key=' . $this->sKey
              . '&encode=json';

        $oCurl = curl_init();
        curl_setopt($oCurl, CURLOPT_HEADER, 0);
        curl_setopt($oCurl, CURLOPT_VERBOSE, 0);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($oCurl, CURLOPT_USERAGENT, self::USER_AGENT . ' - ' . self::VERSION);
        curl_setopt($oCurl, CURLOPT_URL, $sUrl);
        curl_setopt($oCurl, CURLOPT_POST, true);
        curl_setopt($oCurl, CURLOPT_POSTFIELDS, $aArguments);
        $sResult = curl_exec($oCurl);

        if (!$sResult)
        {
            throw new ShoneSecurityException('Empty Result');
        }

        $aResult = json_decode($sResult);
        if (empty($aResult))
        {
            throw new ShoneSecurityException('Malformed JSON');
        }

        return $aResult;
    }

    /**
     * Scan folder for XML hash result
     *
     * @param   string $sFolder
     * @return  string
     * @throws  ShoneSecurityException
     */
    private function _scan_folder($sFolder)
    {
        if (!is_dir($sFolder))
        {
            throw new ShoneSecurityException('Invalid Folder');
        }

        $aCommonChecksums = $this->get_common_checksums();

        $sResult = '';
        $oHandle = opendir($sFolder);
        if ($oHandle)
        {
            while ($sItem = readdir($oHandle))
            {
                if (!in_array($sItem, array('.', '..')))
                {
                    $sEntity = $sFolder . '/' . $sItem;
                    if (is_dir($sEntity))
                    {
                        $sResult .= $this->_scan_folder($sEntity);
                    }
                    else if (is_file($sEntity))
                    {
                        // Generate Hashes
                        $sFileName = str_replace($this->sRootFolder . '/', '', $sEntity);
                        $sSha1 = sha1_file($sEntity);
                        $sMd5 = md5_file($sEntity);

                        if ((empty($aCommonChecksums[$sSha1]) || $aCommonChecksums[$sSha1] != 'sha1')
                            && (empty($aCommonChecksums[$sMd5]) || $aCommonChecksums[$sMd5] != 'md5'))
                        {
                            $sResult .= '<file><name>' . $sFileName . '</name><sha1>' . $sSha1 . '</sha1><md5>' . $sMd5 . "</md5></file>\n";
                        }
                    }
                }
            }

            closedir($oHandle);
        }

        return $sResult;
    }

    #---------------------------------------------------------------------------
    #-> Public Functions

    /**
     * Get a list of common checksums that can be ignored. This helps reduce the
     * amount of data passed to the remote server
     *
     * @return array
     */
    public function get_common_checksums()
    {
        if (empty(self::$aCommonChecksums))
        {
             $aResult = $this->_get('job/common_checksums');
             if ($aResult->Status == self::RESULT_SUCCESS)
             {
                self::$aCommonChecksums = (array)$aResult->Hashes;
             }
        }

        return self::$aCommonChecksums;
    }

    /**
     * Submit a new job request to the remote server.
     * Returns a hash value referencing the job
     *
     * @param   string  $sFolder
     * @param   string  $sLabel   (optional)
     * @return  string
     */
    public function submit($sFolder, $sLabel = '')
    {
        // Scan the folder
        $this->sRootFolder = $sFolder;
        $sScan = $this->_scan_folder($sFolder);

        if (!$sScan)
        {
            throw new ShoneSecurityException('No files found in folder');
        }

        gzfile_set_contents("data.gz", "<?xml version='1.0'?>\n"
              . "<job>\n"
              . "<version>\n"
              . "<value>" . self::VERSION . "</value>\n"
              . "<md5>" . md5_file(__FILE__) . "</md5>\n"
              . "<sha1>" . sha1_file(__FILE__) . "</sha1>\n"
              . "</version>\n"
              . "<control>\n"
              . "<md5>" . md5('control') . "</md5>\n"
              . "<sha1>" . sha1('control') . "</sha1>\n"
              . "</control>\n"
              . "<files>\n" . $sScan ."</files>\n"
              . "</job>\n");

        $aResult = $this->_post('job/submit', array('job' => '@data.gz', 'label' => $sLabel));

        if ($aResult->Status != self::RESULT_SUCCESS)
        {
            throw new ShoneSecurityException('Submit failed. ' . $aResult->Detail);
        }

        return $aResult->Hash;
    }

    /**
     * Get the result of a submitted job
     *
     * @param   string  $sHash
     * @return  array
     */
    public function get_job($sHash)
    {
        return $this->_get('job/get', array('hash' => $sHash));
    }

    /**
     * Fingerprint a file and identify what application/version it belongs to
     *
     * @param   string  $sFileName
     * @return  array
     */
    public function file_fingerprint($sFileName)
    {
        $sMd5 = md5_file($sFileName);
        $sSha1 = sha1_file($sFileName);
        return $this->_get('library/file_fingerprint', array('md5' => $sMd5, 'sha1' => $sSha1));
    }

    /**
     * Get a list of jobs from the remote server
     *
     * @param   string  $sLabel           (optional, default='')
     * @param   boolean $bOnlyDeprecated  (optional, default=false)
     * @param   boolean $bOnlyVulnerable  (optional, default=false)
     * @param   integer $iDateStart       (optional, default=0)
     * @param   integer $iDateEnd         (optional, default=0)
     * @param   mixed   $mIpAddress       (optional, default=0)
     * @return  array
     */
    public function get_jobs($sLabel = '', $bOnlyDeprecated = false, $bOnlyVulnerable = false, $iDateStart = 0, $iDateEnd = 0, $mIpAddress = 0)
    {
        $iIpAddress = ctype_digit($mIpAddress) ? $mIpAddress : sprintf("%u", ip2long($mIpAddress));

        $filters = array(
            'label'             => $sLabel,
            'is_deprecated'     => $bOnlyDeprecated ? 1 : 0,
            'is_vulnerable'     => $bOnlyVulnerable ? 1 : 0,
            'date_range_start'  => $iDateStart,
            'date_range_end'    => $iDateEnd,
            'server'            => $iIpAddress,
        );
        return $this->_get('job/view', $filters);
    }

    /**
     * Get the status of a job by hash
     *
     * @param   string  $sHash
     * @return  array
     */
    public function get_status_by_hash($sHash)
    {
        return $this->_get('job/status', array('hash' => $sHash));
    }

    /**
     * Get the status of a job by label
     *
     * @param   string  $sLabel
     * @return  array
     */
    public function get_status_by_label($sLabel)
    {
        return $this->_get('job/status', array('label' => $sLabel));
    }
}

class ShoneSecurityException extends Exception {}

if (!function_exists('gzfile_set_contents'))
{
    function gzfile_set_contents($filename, $data, $use_include_path = 0)
    {
        $bits_written = 0;
        $file = @gzopen($filename, 'wb', $use_include_path);
        if ($file)
        {
            $bits_written = gzwrite($file, $data);
            gzclose($file);
		}
        return $bits_written > 0;
    }
}
