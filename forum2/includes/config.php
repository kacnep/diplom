<?php
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin 3.0.7
|| # ---------------------------------------------------------------- # ||
|| # Copyright �2000�2005 Jelsoft Enterprises Ltd. All Rights Reserved. ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| ############################[DGT-TEAM]############################## ||
\*======================================================================*/

/*-------------------------------------------------------*\
| ****** NOTE REGARDING THE VARIABLES IN THIS FILE ****** |
+---------------------------------------------------------+
| If you get any errors while attempting to connect to    |
| MySQL, you will need to email your webhost because we   |
| cannot tell you the correct values for the variables    |
| in this file.                                           |
\*-------------------------------------------------------*/

// ****** DATABASE SERVER NAME ******
// This is the hostname or IP address of the database server.
// It is in the format HOST:PORT. If no PORT is specified, 3306 is used.
// If you are unsure of what to put here, leave it at the default value.
$servername = 'localhost';

// ****** DATABASE USERNAME & PASSWORD ******
// This is the username and password you use to access MySQL.
// These must be obtained through your webhost.
$dbusername = 'kasper';
$dbpassword = 'kasper636';

// ****** DATABASE NAME ******
// This is the name of the database where your vBulletin will be located.
// This must be created by your webhost.
$dbname = 'forum';

// ****** TECHNICAL EMAIL ADDRESS ******
// If any database errors occur, they will be emailed to the address specified here.
// Leave this blank to not send any emails when there is a database error.
$technicalemail = 'kacnepbI4@gmail.com';

// ****** PERSISTENT CONNECTIONS ******
// This option allows you to turn persistent connections to MySQL on or off.
// The difference in performance is negligible for all but the largest boards.
// If you are unsure what this should be, leave it off.
// 0 = Off; 1 = On
$usepconnect = 0;

// ****** PATH TO ADMIN & MODERATOR CONTROL PANELS ******
// This setting allows you to change the name of the folders that the admin and
// moderator control panels reside in. You may wish to do this for security purposes.
// Please note that if you change the name of the directory here, you will still need
// to manually change the name of the directory on the server.
$admincpdir = 'admincp';
$modcpdir = 'modcp';

// ****** USERS WITH ADMIN LOG VIEWING PERMISSIONS ******
// The users specified here will be allowed to view the admin log in the control panel.
// Users must be specified by *ID number* here. To obtain a user's ID number,
// view their profile via the control panel. If this is a new installation, leave
// the first user created will have a user ID of 1. Seperate each userid with a comma.
$canviewadminlog = '1';

// ****** USERS WITH ADMIN LOG PRUNING PERMISSIONS ******
// The users specified here will be allowed to remove ("prune") entries from the admin
// log. See the above entry for more information on the format.
$canpruneadminlog = '1';

// ****** USERS WITH QUERY RUNNING PERMISSIONS ******
// The users specified here will be allowed to run queries from the control panel.
// See the above entries for more information on the format.
// Please note that the ability to run queries is quite powerful. You may wish
// to remove all user IDs from this list for security reasons.
$canrunqueries = '';

// ****** UNDELETABLE / UNALTERABLE USERS ******
// The users specified here will not be deletable or alterable from the control panel by any users.
// To specify more than one user, separate userids with commas.
$undeletableusers = '';

// ****** SUPER ADMINISTRATORS ******
// The users specified below will have permission to access the administrator permissions
// page, which controls the permissions of other administrators
$superadministrators = '';

// Prefix that your vBulletin tables have in the database.
// For example: $tableprefix = 'vb3_';
$tableprefix = '';

// Prefix that all vBulletin cookies will have
// For example
$cookieprefix = 'bb';

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 11:11, Sat Feb 19th 2005
|| # CVS: $RCSfile: config.php.new,v $ - $Revision: 1.19 $
|| ####################################################################
\*======================================================================*/
?>