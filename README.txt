Add this to the bottom of LocalSettings.php

require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/UserAccount.inc.php');
$cas = new UserAccount();
require_once( 'includes/AuthPlugin_CAS.php' );
$wgAuth = new AuthPlugin_CAS();
$wgHooks['AutoAuthenticate'][] = 'CAS_login';



Add these files to the base of your mediawiki directory.
- include/AuthPlugin_CAS.php
- include/UserAccount.inc.php
- CAS/


In your mediawiki installation, use the 'htaccess' file here as your .htaccess
file.






Pray it works.


- Ryan Lim <rlim3@unl.edu>
  Thu Apr 03 13:46:35 2008
