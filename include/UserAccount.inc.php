<?php

/**
 * UserAccount.inc.php
 *
 * DO NOT MODIFY THIS FILE.
 * This file remains part of the Account Services public API and is subject to 
 * change. If you require features built into this class, please contact us by 
 * email at <accounts@answers4families.org>.
 *
 * (C) 2007 Answers4Families [http://www.answers4families.org/]
 *
 * Serial #: 20071201000919
 */

include_once($_SERVER['DOCUMENT_ROOT'] . '/CAS/CAS.php');

/**
 * LDAP user directory server
 */
$config['ldap']['uri']           = "ldap://ldap.mydomain.edu:389/";
$config['ldap']['base']          = "dc=mydomain,dc=edu";
$config['ldap']['suffix']        = "ou=People,{$config['ldap']['base']}";
$config['ldap']['anonymous']     = true; // bind anonymously
$config['ldap']['bind_dn']       = 'mybinddn';
$config['ldap']['bind_password'] = 'mybindpassword';

/**
 * Uncomment these to set your LDAP options.
 */
//ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7);
//$ldap = ldap_connect($ldapServer);
//ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);


/**
 * CAS: Central Authentication Service
 */
$config['CAS']['host'] = 'login.mydomain.edu';
$config['CAS']['port'] = 443;
$config['CAS']['path'] = 'cas';





/**
 * UserAccount.inc.php
 *
 * This is the CAS UserAccount class.
 * This class takes care of user authentication using CAS and obtains the user
 * account information via LDAP.
 *
 * This class does not handle changes to the user account information. All account
 * information changes are handled at https://accounts.answers4families.org/
 * 
 */
class UserAccount
{
	/**
	 * $isAuth is used as a flag to see if the user is authenticated or not.
	 */
	var $isAuth = false;

	/**
	 * $uid is the LDAP uid value of the authenticated user.
	 */
	var $uid = '';



	/**
	 * __construct()
	 * The class constructor used to initialize the phpCAS class settings.
	 */
	function __construct()
	{
		global $config;
		phpCAS::setDebug(FALSE);

		phpCAS::client(CAS_VERSION_2_0,
			$config['CAS']['host'], $config['CAS']['port'], $config['CAS']['path']);
		phpCAS::setNoCasServerValidation();

		// We check for authentication everytime.
		// Only check once.
		phpCAS::setCacheTimesForAuthRecheck(-1);

		$this->isAuth = phpCAS::checkAuthentication();
	}
	
	/**
	 * login()
	 * Log in the user.
	 */
	function login()
	{
		phpCAS::forceAuthentication();
		$this->isAuth = true;
		$this->uid = phpCAS::getUser();
	}

	/**
	 * logout()
	 * Log out the user.
	 */
	function logout()
	{
		phpCAS::forceAuthentication();
		$this->isAuth = false;
		if(isset($_SERVER['HTTP_REFERER']))
		{
			phpCAS::logout($_SERVER['HTTP_REFERER']);
		}
		else
		{
			phpCAS::logout();
		}
	}

	/**
	 * isLoggedIn()
	 *
	 * Checks to see if the user is logged in.
	 * @return boolean true if logged in, false otherwise.
	 */
	function isLoggedIn()
	{
		return $this->isAuth;
	}
	
	/**
	 * getAuth()
	 *
	 * Checks to see if the user is logged in.
	 * @return boolean true if logged in, false otherwise.
	 */
	function getAuth()
	{
		return $this->isAuth;
	}

	/**
	 * loginForm($message)
	 *
	 * This is an alias for loginLink().
	 * @param string $message This parameter is ignored.
	 * @return string The login message with the link for the user to login at.
	 */
	function loginForm($message = '')
	{
		return self::loginLink();
	}

	/**
	 * loginLink()
	 *
	 * Provide the user a short message telling him/her that he/she must login.
	 *
	 * @param string $message This parameter is ignored.
	 * @return string The login message with the link for the user to login at.
	 */
	public static function loginLink($message = '')
	{
		$self = htmlentities($_SERVER['PHP_SELF']);
		$output  = '';
		$output .= "<p>The feature you are trying to access requires you ";
		$output .= "to login. If you do not have an account, please ";
		$output .= "register to take advantage of the additional features ";
		$output .= "available.</p>";
		$output .= "<p>You will be directed to the Answers4Families ";
		$output .= "<a href='https://accounts.answers4families.org/'>";
		$output .= "Account Services</a> page. To login, ";
		$output .= "<a href='$self?login'>click here</a>.";

		return $output;
	}

	
	/**
	 * doLogout()
	 *
	 * Log out the user.
	 */
	function doLogout()
	{
		self::logout();
	}

	/**
	 * getUser()
	 *
	 * Get the LDAP-uid.
	 *
	 * @return string The LDAP uid of the logged in user. If the user is not 
	 * logged in, return '-unknown-'.
	 */
	function getUser()
	{
		if($this->isAuth)
		{
			return phpCAS::getUser();
		}
		else
		{
			return '-unknown-';
		}
	}

	/**
	 * getUser_()
	 *
	 * Get the LDAP-uid. This is the static method of getUser().
	 *
	 * @return string The LDAP uid of the logged in user. If the user is not 
	 * logged in, return '-unknown-'.
	 */
	public static function getUser_()
	{
		return phpCAS::getUser();
	}

	/**
	 * getUid()
	 *
	 * Stores the LDAP-uid internally in this instance of the class.
	 *
	 * @return string The LDAP uid of the logged in user. If the user is not 
	 * logged in, return '-unknown-'.
	 */
	function getUid()
	{
		$this->uid = $this->getUser();
		return $this->uid;
	}

	/**
	 * getLastname()
	 *
	 * Returns the 'sn' LDAP attribute of the user.
	 * @return string The user's lastname.
	 */
	function getLastname()
	{
		$this->uid = $this->getUser();
		$ret = self::getAttribute($this->uid, 'sn');
		return $ret[0];
	}

	/**
	 * getLastname_($uid)
	 *
	 * Returns the 'sn' LDAP attribute of the user. This is the static method 
	 * of getLastname().
	 *
	 * @param string $uid The LDAP-uid of the user.
	 * @return string The user's lastname.
	 */
	public static function getLastname_($uid)
	{
		$ret = self::getAttribute($uid, 'sn');
		return $ret[0];
	}

	/**
	 * getCommonname()
	 *
	 * Returns the 'cn' LDAP attribute of the user.
	 * @return string The user's common name (typically givenname + cn).
	 */
	function getCommonname()
	{
		$this->uid = $this->getUser();
		$ret = self::getAttribute($this->uid, 'cn');
		return $ret[0];
	}

	/**
	 * getCommonname_($uid)
	 *
	 * Returns the 'cn' LDAP attribute of the user. This is the static method 
	 * of getCommonname().
	 * @param string $uid The LDAP-uid of the user.
	 * @return string The user's common name (typically givenname + cn).
	 */
	public static function getCommonname_($uid)
	{
		$ret = self::getAttribute($uid, 'cn');
		return $ret[0];
	}

	/**
	 * getFirstname()
	 *
	 * Returns the 'givenname' LDAP attribute of the user.
	 * @return string The user's first name.
	 */
	function getFirstname()
	{
		$this->uid = $this->getUser();
		$ret = self::getAttribute($this->uid, 'givenname');
		return $ret[0];
	}

	/**
	 * getFirstname_($uid)
	 *
	 * Returns the 'givenname' LDAP attribute of the user. This is the static method of getFirstname().
	 * @param string $uid The LDAP-uid of the user.
	 * @return string The user's first name.
	 */
	public static function getFirstname_($uid)
	{
		$ret = self::getAttribute($uid, 'givenname');
		return $ret[0];
	}

	/**
	 * getTelephone()
	 *
	 * Returns the 'telephonenumber' LDAP attribute of the user.
	 * @return string The user's telephone number.
	 */
	function getTelephone()
	{
		$this->uid = $this->getUser();
		$ret = self::getAttribute($this->uid, 'telephonenumber');
		return $ret[0];
	}

	/**
	 * getTelephone_($uid)
	 *
	 * Returns the 'telephonenumber' LDAP attribute of the user. This is the 
	 * static method of getFirstname().
	 * @param string $uid The LDAP-uid of the user.
	 * @return string The user's telephone number.
	 */
	public static function getTelephone_($uid)
	{
		$ret = self::getAttribute($uid, 'telephonenumber');
		return $ret[0];
	}

	/**
	 * getFax()
	 *
	 * Returns the 'facsimiletelephonenumber' LDAP attribute of the user.
	 * @return string The user's fax number.
	 */
	function getFax()
	{
		$this->uid = $this->getUser();
		$ret = self::getAttribute($this->uid, 'facsimiletelephonenumber');
		return $ret[0];
	}

	/**
	 * getFax_($uid)
	 *
	 * Returns the 'facsimiletelephonenumber' LDAP attribute of the user. This 
	 * is the static method of getFax().
	 * @param string $uid The LDAP-uid of the user.
	 * @return string The user's fax number.
	 */
	public static function getFax_($uid)
	{
		$ret = self::getAttribute($uid, 'facsimiletelephonenumber');
		return $ret[0];
	}

	/**
	 * getStreet()
	 *
	 * Returns the 'street' LDAP attribute of the user.
	 * @return string The user's street address.
	 */
	function getStreet()
	{
		$this->uid = $this->getUser();
		$ret = self::getAttribute($this->uid, 'street');
		return $ret[0];
	}

	/**
	 * getStreet_($uid)
	 *
	 * Returns the 'street' LDAP attribute of the user. This is the static 
	 * method of getStreet().
	 * @param string $uid The LDAP-uid of the user.
	 * @return string The user's street address.
	 */
	public static function getStreet_($uid)
	{
		$ret = self::getAttribute($uid, 'street');
		return $ret[0];
	}

	/**
	 * getCity()
	 *
	 * Returns the 'l' (locality) LDAP attribute of the user.
	 * @return string The user's city.
	 */
	function getCity()
	{
		$this->uid = $this->getUser();
		$ret = self::getAttribute($this->uid, 'l');
		return $ret[0];
	}

	/**
	 * getCity_($uid)
	 *
	 * Returns the 'l' (locality) LDAP attribute of the user. This is the 
	 * static method of getCity().
	 * @param string $uid The LDAP-uid of the user.
	 * @return string The user's city.
	 */
	public static function getCity_($uid)
	{
		$ret = self::getAttribute($uid, 'l');
		return $ret[0];
	}

	/**
	 * getState()
	 *
	 * Returns the 'st' LDAP attribute of the user.
	 * @return string The user's state.
	 */
	function getState()
	{
		$this->uid = $this->getUser();
		$ret = self::getAttribute($this->uid, 'st');
		return $ret[0];
	}

	/**
	 * getState_($uid)
	 *
	 * Returns the 'st' LDAP attribute of the user. This is the static method 
	 * of getState().
	 * @param string $uid The LDAP-uid of the user.
	 * @return string The user's state.
	 */
	public static function getState_($uid)
	{
		$ret = self::getAttribute($uid, 'st');
		return $ret[0];
	}

	/**
	 * getZip()
	 *
	 * Returns the 'postalcode' LDAP attribute of the user.
	 * @return string The user's zipcode.
	 */
	function getZip()
	{
		$this->uid = $this->getUser();
		$ret = self::getAttribute($this->uid, 'postalcode');
		return $ret[0];
	}

	/**
	 * getZip_($uid)
	 *
	 * Returns the 'postalcode' LDAP attribute of the user. This is the static method 
	 * of getZip().
	 * @param string $uid The LDAP-uid of the user.
	 * @return string The user's zipcode.
	 */
	public static function getZip_($uid)
	{
		$ret = self::getAttribute($uid, 'postalcode');
		return $ret[0];
	}

	/**
	 * getCountry()
	 *
	 * Returns the 'country' LDAP attribute of the user.
	 * @return string The user's zipcode.
	 */
	function getCountry()
	{
		$this->uid = $this->getUser();
		$ret = self::getAttribute($this->uid, 'c');
		if($ret[0] == '')
		{
			$ret = self::getAttribute($this->uid, 'destinationindicator');
		}
		return $ret[0];
	}

	/**
	 * getCountry_($uid)
	 *
	 * Returns the 'country' LDAP attribute of the user. This is the static method 
	 * of getCountry().
	 * @param string $uid The LDAP-uid of the user.
	 * @return string The user's zipcode.
	 */
	public static function getCountry_($uid)
	{
		$ret = self::getAttribute($uid, 'c');
		if($ret[0] == '')
		{
			$ret = self::getAttribute($uid, 'destinationindicator');
		}
		return $ret[0];
	}

	/**
	 * getEmail($uid)
	 *
	 * Returns the 'mail' LDAP attribute of the user.
	 * @return string The user's email address.
	 */
	function getEmail()
	{
		$this->uid = $this->getUser();
		$ret = self::getAttribute($this->uid, 'mail');
		return $ret[0];
	}

	/**
	 * getEmail_($uid)
	 *
	 * Returns the 'mail' LDAP attribute of the user. This is the static method 
	 * of getEmail().
	 * @param string $uid The LDAP-uid of the user.
	 * @return string The user's email address.
	 */
	public static function getEmail_($uid)
	{
		$ret = self::getAttribute($uid, 'mail');
		return $ret[0];
	}


	/**
	 * getAttribute($uid, $attribute)
	 * @param $uid The user ID (username) of the user we are looking for.
	 * @param $attribute The attribute name we are interested in.
	 * @return array The array of attribute values.
	 */
	public static function getAttribute($uid, $attribute)
	{
		global $config;
		$ldap = ldap_connect($config['ldap']['uri']);

		if($config['ldap']['anonymous'] === false)
		{
			if(!ldap_bind($ldap, $config['ldap']['bind_dn'], 
				$config['ldap']['bind_password']))
			{
				return array('');
			}
		}
		else
		{
			ldap_bind($ldap);
		}

		$result = ldap_search($ldap, 'ou=People,' . $config['ldap']['base'], "uid=$uid");
		$info = ldap_get_entries($ldap, $result);

		ldap_unbind($ldap);

		if(count($info) == 0)
		{
			return array('');
		}
		else
		{
			if(isset($info[0][$attribute][0]))
			{
				return $info[0][$attribute];
			}
			else
			{
				return array('');
			}
		}

	}

}

?>
