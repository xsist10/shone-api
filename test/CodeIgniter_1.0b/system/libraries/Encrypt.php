<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/*
|==========================================================
| Code Igniter - by pMachine
|----------------------------------------------------------
| www.codeignitor.com
|----------------------------------------------------------
| Copyright (c) 2006, pMachine, Inc.
|----------------------------------------------------------
| This library is licensed under an open source agreement:
| www.codeignitor.com/docs/license.html
|----------------------------------------------------------
| File: libraries/Encrypt.php
|----------------------------------------------------------
| Purpose: Keyed two way encryption library
|==========================================================
*/


/*
|==========================================================
| Encryption Class
|==========================================================
|
| Provides two-way keyed encoding using XOR Hashing and Mcrypt
|
*/
class _Encrypt {
	var $_hash_type	= 'sha1';
	var $_mcrypt_exists = FALSE;
	var $_mcrypt_cipher;
	var $_mcrypt_mode;
	
	/*
	|==========================================================
	| Constructor
	|==========================================================
	|
	| Simply determines whether the mcrypt library exists.
	|
	*/
	function _Encrypt()
	{
		$this->_mcrypt_exists = ( ! function_exists('mcrypt_encrypt')) ? FALSE : TRUE;
		log_message('debug', "Encrypt Class Initialized");
	}

	/*
	|==========================================================
	| Fetch the encryption key
	|==========================================================
	|
	| Returns it as MD5 in order to have an exact-length
	| 128 bit key.  Mcrypt is sensitive to keys that are
	| not the correct length
	|
	*/
	function get_key($key = '')
	{
		if ($key == '')
		{	
			$key = call('config', 'item', 'encryption_key');

			if ($key === FALSE)
			{
				show_error('In order to use the encryption class requires that you set an encryption key in your config file.');
			}
		}
		
		return md5($key);
	}
	
	/*
	|==========================================================
	| Encode
	|==========================================================
	|
	| Encodes the message string using bitwise XOR encoding.  
	| The key is combined with a random hash, and then it 
	| too gets converted using XOR. The whole thing is then run 
	| through mcrypt (if supported) using the randomized key.  
	| The end result is a double-encrypted message string 
	| that is randomized with each call to this function, 
	| even if the supplied message and key are the same.
	|
	*/
	function encode($string, $key = '')
	{
		$key = $this->get_key($key);
		$enc = $this->xor_encode($string, $key);
		
		if ($this->_mcrypt_exists === TRUE)
		{
			$enc = $this->mcrypt_encode($enc, $key);
		}
		return base64_encode($enc);		
	}

	/*
	|==========================================================
	| Decode
	|==========================================================
	|
	| Reverses the above process
	|
	*/
	function decode($string, $key = '')
	{
		$key = $this->get_key($key);
		$dec = base64_decode($string);
		
		 if ($dec === FALSE)
		 {
		 	return FALSE;
		 }
		
		if ($this->_mcrypt_exists === TRUE)
		{
			$dec = $this->mcrypt_decode($dec, $key);
		}
		
		return $this->xor_decode($dec, $key);
	}

	/*
	|==========================================================
	| XOR Encode
	|==========================================================
	|
	| Takes a plain-text string and key as input and 
	| generates an encoded bit-string using XOR
	|
	*/	
	function xor_encode($string, $key)
	{
		$rand = '';
		while (strlen($rand) < 32) 
		{    
			$rand .= mt_rand(0, mt_getrandmax());
		}
	
		$rand = $this->hash($rand);
		
		$enc = '';
		for ($i = 0; $i < strlen($string); $i++)
		{			
			$enc .= substr($rand, ($i % strlen($rand)), 1).(substr($rand, ($i % strlen($rand)), 1) ^ substr($string, $i, 1));
		}
				
		return $this->xor_merge($enc, $key);
	}
	
	/*
	|==========================================================
	| XOR Decode
	|==========================================================
	|
	| Takes an encoded string and key as input and 
	| generates the plain-text original message
	|
	*/	
	function xor_decode($string, $key)
	{
		$string = $this->xor_merge($string, $key);
		
		$dec = '';
		for ($i = 0; $i < strlen($string); $i++)
		{
			$dec .= (substr($string, $i++, 1) ^ substr($string, $i, 1));
		}
	
		return $dec;
	}

	/*
	|==========================================================
	| XOR key + string Combiner
	|==========================================================
	|
	| Takes a string and key as input and computes the
	| difference using XOR
	|
	*/	
	function xor_merge($string, $key)
	{
		$hash = $this->hash($key);
		$str = '';
		for ($i = 0; $i < strlen($string); $i++)
		{
			$str .= substr($string, $i, 1) ^ substr($hash, ($i % strlen($hash)), 1);
		}
		
		return $str;
	}

	/*
	|==========================================================
	| Encrypt using Mcrypt
	|==========================================================
	|
	*/
	function mcrypt_encode($data, $key) 
	{	
		$this->set_mcrypt();
		$init_size = mcrypt_get_iv_size($this->_mcrypt_cipher, $this->_mcrypt_mode);
		$init_vect = mcrypt_create_iv($init_size, MCRYPT_RAND);
		return mcrypt_encrypt($this->_mcrypt_cipher, $key, $data, $this->_mcrypt_mode, $init_vect);
	}

	/*
	|==========================================================
	| Decrypt using Mcrypt
	|==========================================================
	|
	*/	
	function mcrypt_decode($data, $key) 
	{
		$this->set_mcrypt();
		$init_size = mcrypt_get_iv_size($this->_mcrypt_cipher, $this->_mcrypt_mode);
		$init_vect = mcrypt_create_iv($init_size, MCRYPT_RAND);
		return rtrim(mcrypt_decrypt($this->_mcrypt_cipher, $key, $data, $this->_mcrypt_mode, $init_vect), "\0");
	}

	/*
	|==========================================================
	| Set the Mcrypt Cypher
	|==========================================================
	}
	*/
	function set_cypher($cypher)
	{
		$this->_mcrypt_cipher = $cypher;
	}

	/*
	|==========================================================
	| Set the Mcrypt Mode
	|==========================================================
	}
	*/
	function set_mode($mode)
	{
		$this->_mcrypt_mode = $mode;
	}

	/*
	|==========================================================
	| Set Mcrypt default values
	|==========================================================
	|
	*/	
	function set_mcrypt()
	{
		if ($this->_mcrypt_cipher == '') 
		{
			$this->_mcrypt_cipher = MCRYPT_RIJNDAEL_256;
		}
		if ($this->_mcrypt_mode == '') 
		{
			$this->_mcrypt_mode = MCRYPT_MODE_ECB;
		}
	}

	/*
	|==========================================================
	| Set the Hash type
	|==========================================================
	|
	*/		
	function set_hash($type = 'sha1')
	{
		$this->_hash_type = ($type != 'sha1' OR $type != 'md5') ? 'sha1' : $type;
	}

	/*
	|==========================================================
	| Hash encode a string
	|==========================================================
	|
	*/		
	function hash($str)
	{
		return ($this->_hash_type == 'sha1') ? $this->sha1($str) : md5($str);
	}

	/*
	|==========================================================
	| Generate an SHA1 Hash
	|==========================================================
	|
	*/	
	function sha1($str)
	{
		if ( ! function_exists('sha1'))
		{
			if ( ! function_exists('mhash'))
			{		
				$SH = new SHA;
				return $SH->generate($str);            
			}
			else
			{
				return bin2hex(mhash(MHASH_SHA1, $str));
			}
		}
		else
		{
			return sha1($str);
		}	
	}    
}



/*
=====================================================
 SHA1 Encoding
-----------------------------------------------------
 Purpose: Provides 160 bit password encryption using 
 The Secure Hash Algorithm developed at the
 National Institute of Standards and Technology. The 
 40 character SHA1 message hash is computationally 
 infeasible to crack.
 
 This class is a fallback for servers that are not running 
 PHP version 4.3, or do not have the MHASH library.
 
 This class is based on two scripts:
  
 Marcus Campbell's PHP implementation (GNU license) 
 http://www.tecknik.net/sha-1/
 
 ...which is based on Paul Johnston's JavaScript version 
 (BSD license). http://pajhome.org.uk/
 
 I encapsulated the functions and wrote one 
 additional method to fix a hex conversion bug. 
 - Rick Ellis
=====================================================
*/
class SHA {

	/*
	|------------------------------------------------
	| Generate the Hash
	|------------------------------------------------
	*/	
    function generate($str) 
    {
        $n = ((strlen($str) + 8) >> 6) + 1;
        
        for ($i = 0; $i < $n * 16; $i++)
        {
            $x[$i] = 0;
        }
        
        for ($i = 0; $i < strlen($str); $i++)
        {
            $x[$i >> 2] |= ord(substr($str, $i, 1)) << (24 - ($i % 4) * 8);
        }
        
        $x[$i >> 2] |= 0x80 << (24 - ($i % 4) * 8);
        
        $x[$n * 16 - 1] = strlen($str) * 8;
        
        $a =  1732584193;
        $b = -271733879;
        $c = -1732584194;
        $d =  271733878;
        $e = -1009589776;
        
        for ($i = 0; $i < sizeof($x); $i += 16) 
        {
            $olda = $a;
            $oldb = $b;
            $oldc = $c;
            $oldd = $d;
            $olde = $e;
            
            for($j = 0; $j < 80; $j++) 
            {
                if ($j < 16)
                {
                    $w[$j] = $x[$i + $j];
                }
                else
                {
                    $w[$j] = $this->rol($w[$j - 3] ^ $w[$j - 8] ^ $w[$j - 14] ^ $w[$j - 16], 1);
                }
                
                $t = $this->safe_add($this->safe_add($this->rol($a, 5), $this->ft($j, $b, $c, $d)), $this->safe_add($this->safe_add($e, $w[$j]), $this->kt($j)));
                
                $e = $d;
                $d = $c;
                $c = $this->rol($b, 30);
                $b = $a;
                $a = $t;
            }

            $a = $this->safe_add($a, $olda);
            $b = $this->safe_add($b, $oldb);
            $c = $this->safe_add($c, $oldc);
            $d = $this->safe_add($d, $oldd);
            $e = $this->safe_add($e, $olde);
        }
        
        return $this->hex($a).$this->hex($b).$this->hex($c).$this->hex($d).$this->hex($e);
    }
    
    
	/*
	|------------------------------------------------
	| Convert a decimal to hex
	|------------------------------------------------
	*/	
    function hex($str)
    {
        $str = dechex($str);
        
        if (strlen($str) == 7)
        {
            $str = '0'.$str;
        }
            
        return $str;
    }    
    
	/*
	|------------------------------------------------
	|  Return result based on iteration
	|------------------------------------------------
	*/	
    function ft($t, $b, $c, $d) 
    {
        if ($t < 20) 
            return ($b & $c) | ((~$b) & $d);
        if ($t < 40) 
            return $b ^ $c ^ $d;
        if ($t < 60) 
            return ($b & $c) | ($b & $d) | ($c & $d);
        
        return $b ^ $c ^ $d;
    }    

	/*
	|------------------------------------------------
	| Determine the additive constant
	|------------------------------------------------
	*/	
    function kt($t) 
    {
        if ($t < 20) 
        {
            return 1518500249;
        } 
        else if ($t < 40) 
        {
            return 1859775393;
        } 
        else if ($t < 60) 
        {
            return -1894007588;
        } 
        else 
        {
            return -899497514;
        }
    }

	/*
	|------------------------------------------------
	| Add integers, wrapping at 2^32
	|------------------------------------------------
	*/	
    function safe_add($x, $y)
    {
        $lsw = ($x & 0xFFFF) + ($y & 0xFFFF);
        $msw = ($x >> 16) + ($y >> 16) + ($lsw >> 16);
    
        return ($msw << 16) | ($lsw & 0xFFFF);
    }

	/*
	|------------------------------------------------
	| Bitwise rotate a 32-bit number
	|------------------------------------------------
	*/	
    function rol($num, $cnt)
    {
        return ($num << $cnt) | $this->zero_fill($num, 32 - $cnt);
    }

	/*
	|------------------------------------------------
	| Pad string with zero
	|------------------------------------------------
	*/	
    function zero_fill($a, $b) 
    {
        $bin = decbin($a);
        
        if (strlen($bin) < $b)
        {
            $bin = 0;
        }
        else
        {
            $bin = substr($bin, 0, strlen($bin) - $b);
        }
        
        for ($i=0; $i < $b; $i++) 
        {
            $bin = "0".$bin;
        }
        
        return bindec($bin);
    }
}

?>