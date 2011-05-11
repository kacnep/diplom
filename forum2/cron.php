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

// ####################### SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);
ignore_user_abort(1);
@set_time_limit(0);

// #################### DEFINE IMPORTANT CONSTANTS #######################
define('NO_REGISTER_GLOBALS', 1);
define('SESSION_BYPASS', 1);
define('LOCATION_BYPASS', 1);
define('THIS_SCRIPT', 'cron');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array();

// get special data templates from the datastore
$specialtemplates = array();

// pre-cache templates used by all actions
$globaltemplates = array();

// pre-cache templates used by specific actions
$actiontemplates = array();

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once('./includes/functions_cron.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

header("Location: $vboptions[cleargifurl]");

$workingdir = getcwd(); // workaround for php bug 14251

if (!NOSHUTDOWNFUNC)
{
	register_shutdown_function('exec_cron');
}
else
{
	exec_cron();
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 11:11, Sat Feb 19th 2005
|| # CVS: $RCSfile: cron.php,v $ - $Revision: 1.22.2.1 $
|| ####################################################################
\*======================================================================*/
?>