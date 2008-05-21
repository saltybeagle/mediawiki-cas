<?php
require_once('AuthPlugin.php');

include_once($_SERVER['DOCUMENT_ROOT'] . '/CAS/CAS.php');
include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/UserAccount.inc.php');


function CAS_login(&$user)
{
	global $wgServer, $wgScriptPath, $wgCookiePrefix;
	global $cas;

	//we go to great lengths to make sure the login code isn't called when not necessary
	if ( $cas->isLoggedIn() &&
		(!isset($_COOKIE[$wgCookiePrefix.'UserID'])) &&
		(!isset($_COOKIE[$wgCookiePrefix.'LoggedOut'])) &&
		(strstr($_SERVER['REQUEST_URI'], $wgScriptPath.'/login/') === false)
	) {

		if (!$cas->isLoggedIn()) {
			header('Location: '.$wgServer.$wgScriptPath.'/login/');
			exit();
		}
	}
	return true;
}




class AuthPlugin_CAS extends AuthPlugin {

	function AuthPlugin_CAS()
	{
		global $cas;
		$cas_user = $cas->getUser();
		if ( $cas_user != '-unknown-')
		{
			global $wgExtensionFunctions;
			if (!isset($wgExtensionFunctions)) {
				$wgExtensionFunctions = array();
			}
			else if (!is_array($wgExtensionFunctions)) {
				$wgExtensionFunctions = array( $wgExtensionFunctions );
			}
			array_push($wgExtensionFunctions, 'Auth_remote_user_hook');
		}
		return;
	}


	function userExists($username) {
		return true;
	}

	function authenticate($username, $password) {
		return true;
	}

	function autoCreate() {
		return true;
	}

	// tell MediaWiki to not look in its database for user authentication and 
	// that our authentication method is all that counts
	function strict() {
		return true;
	}

	function initUser(&$user) {
		global $cas;

		$user->setRealName(
			UserAccount::getFirstname_($cas->getUser()) . " " .
			UserAccount::getLastname_($cas->getUser()) 

		);
		$user->setEmail(
			UserAccount::getEmail_($cas->getUser()) 
		);
		$user->setPassword("password");
	}

	function modifyUITemplate(&$template) {
		$template->set("useemail", false);
		$template->set("remember", false);
		$template->set("create", false);
		$template->set("domain", true);
	}
}




function Auth_remote_user_hook() {
	global $wgUser;
	global $wgRequest;
	global $_REQUEST;
	global $cas;

	// For a few special pages, don't do anything.
	$title = $wgRequest->getVal('title');
	if (($title == Title::makeName(NS_SPECIAL, 'Userlogout')) ||
		($title == Title::makeName(NS_SPECIAL, 'Userlogin'))) {
			return;
		}

	// Do nothing if session is valid
	$user = User::newFromSession();
	if (!$user->isAnon()) {
		return;  // User is already logged in and not anonymous.
	}

	// Copied from includes/SpecialUserlogin.php
	if(!isset($wgCommandLineMode) && !isset($_COOKIE[session_name()])) {
		wfSetupSession();
	}

	// Submit a fake login form to authenticate the user.
	$username = $cas->getUser();
	$params = new FauxRequest(array(
		'wpName' => $username,
		'wpPassword' => '',
		'wpDomain' => '',
		'wpRemember' => '',
		"$wgLoginFormKey" => 'whatever'
	));

	// Authenticate user data will automatically create new users.
	$loginForm = new LoginForm($params);
	$result = $loginForm->authenticateUserData();
	if ($result != LoginForm::SUCCESS) {
		error_log('Unexpected REMOTE_USER authentication failure.');
		return;
	}

	$wgUser->setCookies();
	return;  // User has been logged in.
}




?>
