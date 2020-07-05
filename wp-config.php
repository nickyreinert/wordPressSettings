<?php

// if you disable cron, make sure to call wp-cron.php manually from outside!
define('DISABLE_WP_CRON', true);

// limit the amount of available revisions per post 
// (if you add this to an existing installation
// make sure to clean up existing revisions
define('WP_POST_REVISIONS', 5);

// make sure you uss SSL on backend,
// on modern systems this should be superflous
define('FORCE_SSL_ADMIN', true);
