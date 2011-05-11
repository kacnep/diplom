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

// ######################## SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);

// ##################### DEFINE IMPORTANT CONSTANTS #######################
define('CVS_REVISION', '$RCSfile: index.php,v $ - $Revision: 1.62.2.1 $');
define('NO_REGISTER_GLOBALS', 1);

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('cphome');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');

// ############################# LOG ACTION ###############################
if (empty($_REQUEST['do']))
{
	log_admin_action();
}

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

if (!empty($_REQUEST['redirect']))
{
	define('CP_REDIRECT', $redirect);
	print_stop_message('redirecting_please_wait');
}

// #############################################################################
// ############################### LOG OUT OF CP ###############################
// #############################################################################

if ($_REQUEST['do'] == 'cplogout')
{
	vbsetcookie('cpsession', '', 0);
	$DB_site->query("DELETE FROM " . TABLE_PREFIX . "cpsession WHERE userid = $bbuserinfo[userid] AND hash = '" . addslashes($_COOKIE[COOKIE_PREFIX . 'cpsession']) . "'");
	exec_header_redirect("index.php?$session[sessionurl_js]");
}

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'frames';
}

if ($_REQUEST['do'] == 'frames')
{
	globalize($_REQUEST, array('loc'));

	$navframe = "<frame src=\"index.php?$session[sessionurl]do=nav" . iif($cpnavjs, '&amp;cpnavjs=1') . "\" name=\"nav\" scrolling=\"yes\" frameborder=\"0\" marginwidth=\"0\" marginheight=\"0\" border=\"no\" />\n";
	$headframe = "<frame src=\"index.php?$session[sessionurl]do=head\" name=\"head\" scrolling=\"no\" noresize=\"noresize\" frameborder=\"0\" marginwidth=\"10\" marginheight=\"0\" border=\"no\" />\n";
	$mainframe = "<frame src=\"" . iif(!empty($loc), $loc, "index.php?$session[sessionurl]do=home") . "\" name=\"main\" scrolling=\"yes\" frameborder=\"0\" marginwidth=\"10\" marginheight=\"10\" border=\"no\" />\n";

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html dir="<?php echo $stylevar['textdirection']; ?>" lang="<?php echo $stylevar['languagecode']; ?>">
<head>
<script type="text/javascript">
<!-- // get out of any containing frameset
if (self.parent.frames.length != 0)
{
	document.write('<span style="font: bold 10pt verdana,sans-serif">Get me out of this frame set!</span>');
	self.parent.location.replace(document.location.href);
}
// -->
</script>
<title><?php echo $vboptions['bbtitle']; ?> <?php echo $vbphrase['moderator_control_panel']; ?></title>
</head>

<?php

if ($stylevar['textdirection'] == 'ltr')
{
// left-to-right frameset
?>
<frameset cols="195,*"  framespacing="0" border="0" frameborder="0" frameborder="no" border="0">
	<?php echo $navframe; ?>
	<frameset rows="20,*"  framespacing="0" border="0" frameborder="0" frameborder="no" border="0">
		<?php echo $headframe; ?>
		<?php echo $mainframe; ?>
	</frameset>
</frameset>
<?php
}
else
{
// right-to-left frameset
?>
<frameset cols="*,195"  framespacing="0" border="0" frameborder="0" frameborder="no" border="0">
	<frameset rows="20,*"  framespacing="0" border="0" frameborder="0" frameborder="no" border="0">
		<?php echo $headframe; ?>
		<?php echo $mainframe; ?>
	</frameset>
	<?php echo $navframe; ?>
</frameset>
<?php
}

?>

<noframes>
	<body>
		<p><?php echo $vbphrase['no_frames_support']; ?></p>
	</body>
</noframes>

</html>
<?php
}

if ($_REQUEST['do'] == 'head')
{
	define('IS_NAV_PANEL', true);
	print_cp_header();
?>
<table border="0" width="100%" height="100%">
<tr valign="middle">
	<td><b><?php echo $vbphrase['moderator_control_panel']; ?></b> (vBulletin <?php echo $versionnumber; ?>)</a></td>
	<td style="white-space:nowrap; text-align:<?php echo $stylevar['right']; ?>; font-weight:bold">
			<a href="../<?php echo $vboptions['forumhome']; ?>.php?<?php echo $session['sessionurl']; ?>" target="_blank"><?php echo $vbphrase['forum_home_page']; ?></a>
			|
			<a href="index.php?<?php echo $session['sessionurl']; ?>do=cplogout" onclick="return confirm('<?php echo $vbphrase['sure_you_want_to_log_out_of_cp']; ?>');"  target="_top"><?php echo $vbphrase['log_out']; ?></a>
</td>
</tr>
</table>
<?php
	print_cp_footer();
}

if ($_REQUEST['do'] == 'home')
{

print_cp_header($vbphrase['welcome_to_the_vbulletin_moderator_control_panel']);

print_form_header('', '');
print_table_header($vbphrase['welcome_to_the_vbulletin_moderator_control_panel']);
print_table_footer();

// *************************************
// QUICK ADMIN LINKS

//$reminders = fetch_reminders_array();

print_table_start();
print_table_header($vbphrase['quick_moderator_links']);
if ($stats = @exec('uptime 2>&1') AND trim($stats) != '')
{
	if (preg_match("#: ([\d.,]+),\s+([\d.,]+),\s+([\d.,]+)$#", $stats, $regs))
	{

		$datecut = TIMENOW - $vboptions['cookietimeout'];
		$guestsarry = $DB_site->query_first("SELECT COUNT(host) AS sessions FROM " . TABLE_PREFIX . "session WHERE userid = 0 AND lastactivity > $datecut");
		$membersarry = $DB_site->query("SELECT DISTINCT userid FROM " . TABLE_PREFIX . "session WHERE userid <> 0 AND lastactivity > $datecut");

		$guests = intval($guestsarry['sessions']);
		$members = intval($DB_site->num_rows($membersarry));

		$regs[1] = vb_number_format($regs[1], 2);
		$regs[2] = vb_number_format($regs[2], 2);
		$regs[3] = vb_number_format($regs[3], 2);

		print_label_row($vbphrase['server_load_averages'], "$regs[1]&nbsp;&nbsp;$regs[2]&nbsp;&nbsp;$regs[3] | " . construct_phrase($vbphrase['users_online_x_members_y_guests'], vb_number_format($guests + $members), vb_number_format($members), vb_number_format($guests)), '', 'top', NULL, false);
	}
}
print_label_row($vbphrase['quick_user_finder'], '
	<form action="user.php" method="post" style="display:inline">
		<input type="hidden" name="s" value="' . $session['sessionhash'] . '" />
		<input type="hidden" name="do" value="findnames" />
	<input type="text" class="bginput" name="findname" size="30" tabindex="1" />
	<input type="submit" class="button" value=" ' . $vbphrase['find'] . ' " tabindex="1" />
	<input type="submit" class="button" value="' . $vbphrase['exact_match'] . '" tabindex="1" name="exact" />
	</form>
	', '', 'top', NULL, false
);
print_label_row($vbphrase['php_function_lookup'], '
	<form action="http://www.php.net/manual-lookup.php" method="get" style="display:inline">
	<input type="text" class="bginput" name="function" size="30" tabindex="1" />
	<input type="submit" value=" ' . $vbphrase['find'] . ' " class="button" tabindex="1" />
	</form>
	', '', 'top', NULL, false
);
print_label_row($vbphrase['mysql_language_lookup'], '
	<form action="http://www.mysql.com/doc/manual.php" method="get" style="display:inline">
	<input type="hidden" name="depth" value="2" />
	<input type="text" class="bginput" name="search_query" size="30" tabindex="1" />
	<input type="submit" value=" ' . $vbphrase['find'] . ' " class="button" tabindex="1" />
	</form>
	', '', 'top', NULL, false
);
print_label_row($vbphrase['useful_links'], '
	<form style="display:inline">
	<select onchange="window.open(this.options[this.selectedIndex].value); return false;" tabindex="1" class="bginput">
		<option value="">-- ' . $vbphrase['useful_links'] . ' --</option>' . construct_select_options(array(
			'PHP' => array(
				'http://www.php.net/' => $vbphrase['home_page'] . ' (PHP.net)',
				'http://www.php.net/manual/' => $vbphrase['reference_manual'],
				'http://www.php.net/downloads.php' => $vbphrase['download_latest_version']
			),
			'MySQL' => array(
				'http://www.mysql.com/' => $vbphrase['home_page'] . ' (MySQL.com)',
				'http://www.mysql.com/documentation/' => $vbphrase['reference_manual'],
				'http://www.mysql.com/downloads/' => $vbphrase['download_latest_version'],
			)
	)) . '</select>
	</form>
	', '', 'top', NULL, false
);
print_table_footer(2, '', '', false);

// *************************************
// vBULLETIN CREDITS
require_once('./includes/vbulletin_credits.php');

print_cp_footer();

?>

</body>
</html>
<?php
}

if ($_REQUEST['do'] == 'nav')
{
	require_once('./includes/adminfunctions_navpanel.php');
	print_cp_header();
	?>
	<script type="text/javascript">
	<!--
	function nobub()
	{
		window.event.cancelBubble = true;
	}

	function nav_goto(targeturl)
	{
		parent.frames.main.location = targeturl;
	}
	-->
	</script>
<div>
<img src="../cpstyles/<?php echo $vboptions['cpstylefolder']; ?>/cp_logo.gif" alt="" border="0" hspace="4" vspace="4" /><?php
	echo "</div>\n\n<div style=\"width:168px; padding: 4px\">\n";

	construct_nav_spacer();

	// *************************************************
	if (can_moderate(0, 'canannounce'))
	{
		construct_nav_option($vbphrase['add_new_announcement'], 'announcement.php?do=add');
		construct_nav_option($vbphrase['forum_manager'], 'forum.php?do=modify');
		construct_nav_group($vbphrase['announcements']);
		construct_nav_spacer();
	}
	// *************************************************
	$canmoderate = false;
	if (can_moderate(0, 'canmoderateposts'))
	{
		$canmoderate = true;
		construct_nav_option($vbphrase['moderate_threads'], "moderate.php?do=posts", '<br />');
		construct_nav_option($vbphrase['moderate_posts'], "moderate.php?do=posts#posts", '<br />');
	}
	if (can_moderate(0, 'canmoderateattachments'))
	{
		$canmoderate = true;
		construct_nav_option($vbphrase['moderate_attachments'], "moderate.php?do=attachments", '<br />');
	}
	if (can_moderate_calendar())
	{
		$canmoderate = true;
		construct_nav_option($vbphrase['moderate_events'], "moderate.php?do=events");
	}
	if ($canmoderate)
	{
		construct_nav_group($vbphrase['moderation'], '<hr />', "$df");
		construct_nav_spacer();
	}
	// *************************************************
	$canuser = false;
	if (can_moderate(0, 'canunbanusers') OR can_moderate(0, 'canbanusers') OR can_moderate(0, 'canviewprofile') OR can_moderate(0, 'caneditsigs') OR can_moderate(0, 'caneditavatar'))
	{
		$canuser = true;
		construct_nav_option($vbphrase['search_for_users'],'user.php?do=find', '<br />');
	}
	if (can_moderate(0, 'canbanusers'))
	{
		$canuser = true;
		construct_nav_option($vbphrase['ban_user'], 'banning.php?do=banuser', '<br />');
		construct_nav_option($vbphrase['view_banned_users'], 'banning.php?do=modify', '<br />');
	}

	if (can_moderate(0, 'canviewips'))
	{
		$canuser = true;
		construct_nav_option($vbphrase['search_ip_addresses'], 'user.php?do=doips');
	}
	if ($canuser)
	{
		construct_nav_group($vbphrase['users']);
		construct_nav_spacer();
	}
	// *************************************************
	if ($groupleader = $DB_site->query_first("SELECT userid FROM " . TABLE_PREFIX . "usergroupleader WHERE userid = $bbuserinfo[userid]") OR ($permissions['adminpermissions'] & CANCONTROLPANEL))
	{
		construct_nav_option($vbphrase['join_requests'], 'user.php?do=viewjoinrequests');
		construct_nav_group($vbphrase['usergroups']);
		construct_nav_spacer();
	}
	// *************************************************
	$canmass = false;
	if (can_moderate(0, 'canmassmove'))
	{
		$canmass = true;
		construct_nav_option($vbphrase['move'], 'thread.php?do=move', '<br />');
	}
	if (can_moderate(0, 'canmassprune'))
	{
		$canmass = true;
		construct_nav_option($vbphrase['prune'], 'thread.php?do=prune');
	}
	if ($canmass)
	{
		construct_nav_group($vbphrase['thread']);
		construct_nav_spacer();
	}

	print_nav_panel();

	echo "</div>\n";
	// *************************************************

	define('NO_CP_COPYRIGHT', true);
	unset($DEVDEBUG);
	print_cp_footer();
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 11:11, Sat Feb 19th 2005
|| # CVS: $RCSfile: index.php,v $ - $Revision: 1.62.2.1 $
|| ####################################################################
\*======================================================================*/
?>