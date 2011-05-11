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
define('CVS_REVISION', '$RCSfile: user.php,v $ - $Revision: 1.256.2.8 $');
define('NO_REGISTER_GLOBALS', 1);

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('cpuser', 'forum', 'timezone', 'user');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once('./includes/adminfunctions_profilefield.php');
require_once('./includes/adminfunctions_user.php');
require_once('./includes/functions_register.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminusers'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
log_admin_action(iif($_REQUEST['userid'] != 0, 'user id = ' . $_REQUEST['userid']));

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

// #############################################################################
// put this before print_cp_header() so we can use an HTTP header
if ($_REQUEST['do'] == 'find')
{
	if (isset($_POST['serializeduser']))
	{
		$_REQUEST['user'] = @unserialize($_POST['serializeduser']);
		$_REQUEST['profile'] = @unserialize($_POST['serializedprofile']);
	}
	else
	{
		$_REQUEST['user'] = $_POST['user'] ? $_POST['user'] : $_GET['user'];
		$_REQUEST['profile'] = $_POST['profile'] ? $_POST['profile'] : $_GET['profile'];
	}

	if (isset($_POST['serializeddisplay']))
	{
		$_REQUEST['display'] = @unserialize($_POST['serializeddisplay']);
	}

	if (@array_sum($_REQUEST['display']) == 0)
	{
		$display = array('username' => 1, 'options' => 1, 'email' => 1, 'joindate' => 1, 'lastactivity' => 1, 'posts' => 1);
	}
	else
	{
		$display = &$_REQUEST['display'];
	}

	$condition = fetch_user_search_sql($_REQUEST['user'], $_REQUEST['profile']);

	globalize($_REQUEST, array('orderby' => STR, 'limitstart' => STR, 'limitnumber' => STR , 'direction' => STR));

	switch($orderby)
	{
		case 'username':
		case 'email':
		case 'joindate':
		case 'lastactivity':
		case 'lastpost':
		case 'posts':
		case 'birthday_search':
		case 'reputation':
			break;
		default:
			$orderby = 'username';
	}

	if ($direction != 'DESC')
	{
		$direction = 'ASC';
	}

	if (empty($limitstart))
	{
		$limitstart = 0;
	}
	else
	{
		$limitstart--;
	}
	if (empty($limitnumber))
	{
		$limitnumber = 25;
	}

	$searchquery = "
		SELECT
		user.userid, reputation, username, usergroupid, birthday_search, email,
		parentemail,(options & $_USEROPTIONS[coppauser]) AS coppauser, homepage, icq, aim, yahoo, msn, signature,
		usertitle, joindate, lastpost, posts, ipaddress, lastactivity, userfield.*
		FROM " . TABLE_PREFIX . "user AS user
		LEFT JOIN " . TABLE_PREFIX . "userfield AS userfield ON(userfield.userid = user.userid)
		LEFT JOIN " . TABLE_PREFIX . "usertextfield AS usertextfield ON(usertextfield.userid = user.userid)
		WHERE $condition
		ORDER BY $orderby $direction
		LIMIT $limitstart, $limitnumber
	";

	$users = $DB_site->query($searchquery);

	$countusers = $DB_site->query_first("
		SELECT COUNT(*) AS users
		FROM " . TABLE_PREFIX . "user AS user
		LEFT JOIN " . TABLE_PREFIX . "userfield AS userfield ON(userfield.userid = user.userid)
		LEFT JOIN " . TABLE_PREFIX . "usertextfield AS usertextfield ON(usertextfield.userid = user.userid)
		WHERE $condition
	");

	if ($countusers['users'] == 1)
	{
		// show a user if there is just one found
		$user = $DB_site->fetch_array($users);
		// instant redirect
		exec_header_redirect("user.php?$session[sessionurl]do=edit&userid=$user[userid]");
	}
	else if ($countusers['users'] == 0)
	{
		// no users found!
		print_stop_message('no_users_matched_your_query');
	}

	define('DONEFIND', true);
	$_REQUEST['do'] = 'find2';
}

// #############################################################################

print_cp_header($vbphrase['user_manager']);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

// ###################### Start email password #######################
if ($_REQUEST['do'] == 'emailpassword')
{
	globalize($_REQUEST, array('email' => STR));

	print_form_header('../login', 'emailpassword');
	construct_hidden_code('email', $email);
	construct_hidden_code('url', "$admincpdir/user.php?do=find&user[email]=$email");
	print_table_header($vbphrase['email_password_reminder_to_user']);
	print_description_row(construct_phrase($vbphrase['click_the_button_to_send_password_reminder_to_x'], "<i>$email</i>"));
	print_submit_row($vbphrase['send'], 0);

}

// ###################### Start Remove #######################
if ($_REQUEST['do'] == 'remove')
{
	globalize($_REQUEST, array('userid' => INT));

	print_delete_confirmation('user', $userid, 'user', 'kill', 'user', '', $vbphrase['all_posts_will_be_set_to_guest']);
	echo '<p align="center">' . construct_phrase($vbphrase['if_you_want_to_prune_user_posts_first'], "thread.php?$session[sessionurl]do=pruneuser&amp;forumid=-1&amp;userid=$userid&amp;confirm=1") . '</p>';

}

// ###################### Start Kill #######################
if ($_POST['do'] == 'kill')
{
	globalize($_REQUEST, array('userid' => INT));

	// check user is not set in the $undeletable users string
	$nodelete = explode(',', $undeletableusers);
	if (in_array($userid, $nodelete))
	{
		print_stop_message('user_is_protected_from_alteration_by_undeletableusers_var');
	}
	else
	{
		delete_user($userid);

		define('CP_REDIRECT', 'user.php?do=modify');
		print_stop_message('deleted_user_successfully');
	}
}

// ###################### Start edit #######################
if ($_REQUEST['do'] == 'edit' OR $_REQUEST['do'] == 'add')
{
	$OUTERTABLEWIDTH = '95%';
	$INNERTABLEWIDTH = '100%';

	// must include this in order to use bitwise()
	require_once('./includes/functions_misc.php');

	if ($_REQUEST['do'] == 'edit')
	{
		globalize($_REQUEST, array('userid' => INT));
	}
	else
	{
		$userid = 0;
	}

	if ($userid)
	{

		$user = $DB_site->query_first("
			SELECT user.*, avatar.avatarpath, customavatar.dateline AS avatardateline,
			NOT ISNULL(customavatar.avatardata) AS hascustomavatar, usertextfield.signature,
			customprofilepic.userid AS profilepic, customprofilepic.dateline AS profilepicdateline, usergroup.adminpermissions
			FROM " . TABLE_PREFIX . "user AS user
			LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON(avatar.avatarid = user.avatarid)
			LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON(customavatar.userid = user.userid)
			LEFT JOIN " . TABLE_PREFIX . "customprofilepic AS customprofilepic ON(customprofilepic.userid = user.userid)
			LEFT JOIN " . TABLE_PREFIX . "usertextfield AS usertextfield ON(usertextfield.userid = user.userid)
			LEFT JOIN " . TABLE_PREFIX . "usergroup AS usergroup ON(usergroup.usergroupid = user.usergroupid)
			WHERE user.userid = $userid
		");

		$getoptions = convert_bits_to_array($user['options'], $_USEROPTIONS);
		$user = array_merge($user, $getoptions);

		if ($user['coppauser'] == 1)
		{
			echo "<p align=\"center\"><b>$vbphrase[this_is_a_coppa_user_do_not_change_to_registered]</b></p>\n";
		}

		if ($user['usergroupid'] == 3)
		{
			print_form_header('../register', 'emailcode', 0, 0);
			construct_hidden_code('email', $user['email']);
			print_submit_row($vbphrase['email_activation_codes'], 0);
		}

		// make array for quick links menu
		$quicklinks = array(
			"user.php?$session[sessionurl]do=editaccess&userid=$userid"
				=> $vbphrase['edit_forum_permissions_access_masks'],
			"resources.php?$session[sessionurl]do=viewuser&userid=$userid"
				=> $vbphrase['view_forum_permissions'],
			"mailto:$user[email]"
				=> $vbphrase['send_email_to_user']
		);

		if ($user['usergroupid'] == 3)
		{
			$quicklinks[
				"../register.php?$session[sessionurl]do=requestemail&email=" . urlencode(unhtmlspecialchars($user['email']))
			] = $vbphrase['email_activation_codes'];
		}

		$quicklinks = array_merge(
			$quicklinks,
			array(
			"user.php?$session[sessionurl]do=emailpassword&email=" . urlencode(unhtmlspecialchars($user['email']))
				=> $vbphrase['email_password_reminder_to_user'],
			"../private.php?$session[sessionurl]do=newpm&userid=$userid"
				=> $vbphrase['send_private_message_to_user'],
			"usertools.php?$session[sessionurl]do=pmfolderstats&userid=$userid"
				=> $vbphrase['private_message_statistics'],
			"usertools.php?$session[sessionurl]do=removepms&amp;userid=$userid"
				=> $vbphrase['delete_all_users_private_messages'],
			"usertools.php?$session[sessionurl]do=removesentpms&amp;userid=$userid"
				=> $vbphrase['delete_private_messages_sent_by_user'],
			"usertools.php?$session[sessionurl]do=removesubs&amp;userid=$userid"
				=> $vbphrase['delete_subscriptions'],
			"usertools.php?$session[sessionurl]do=doips&userid=$userid"
				=> $vbphrase['view_ip_addresses'],
			"../member.php?$session[sessionurl]do=getinfo&userid=$userid"
				=> $vbphrase['view_profile'],
			"../search.php?$session[sessionurl]do=finduser&userid=$userid"
				=> $vbphrase['find_posts_by_user'],
			"../$modcpdir/banning.php?$session[sessionurl]do=banuser&amp;userid=$userid"
				=> $vbphrase['ban_user'],
			"user.php?$session[sessionurl]do=remove&userid=$userid"
				=> $vbphrase['delete_user'],
			)
		);

		if (intval($user['adminpermissions']) & CANCONTROLPANEL AND in_array($bbuserinfo['userid'], preg_split('#\s*,\s*#s', $superadministrators, -1, PREG_SPLIT_NO_EMPTY)))
		{
			$quicklinks["adminpermissions.php?$session[sessionurl]do=edit&userid=$userid"] = $vbphrase['edit_administrator_permissions'];
		}

		$userfield = $DB_site->query_first("SELECT * FROM " . TABLE_PREFIX . "userfield WHERE userid = $userid");

	}
	else
	{
		$regoption = array();
		if (bitwise(REGOPTION_SUBSCRIBE_NONE, $vboptions['defaultregoptions']))
		{
			$regoption['autosubscribe'] = -1;
		}
		else if (bitwise(REGOPTION_SUBSCRIBE_NONOTIFY, $vboptions['defaultregoptions']))
		{
			$regoption['autosubscribe'] = 0;
		}
		else if (bitwise(REGOPTION_SUBSCRIBE_INSTANT, $vboptions['defaultregoptions']))
		{
			$regoption['autosubscribe'] = 1;
		}
		else if (bitwise(REGOPTION_SUBSCRIBE_DAILY, $vboptions['defaultregoptions']))
		{
			$regoption['autosubscribe'] = 2;
		}
		else
		{
			$regoption['autosubscribe'] = 3;
		}

		if (bitwise(REGOPTION_VBCODE_NONE, $vboptions['defaultregoptions']))
		{
			$regoption['showvbcode'] = 0;
		}
		else if (bitwise(REGOPTION_VBCODE_STANDARD, $vboptions['defaultregoptions']))
		{
			$regoption['showvbcode'] = 1;
		}
		else
		{
			$regoption['showvbcode'] = 2;
		}

		if (bitwise(REGOPTION_THREAD_LINEAR_OLDEST, $vboptions['defaultregoptions']))
		{
			$regoption['threadedmode'] = 0;
			$regoption['postorder'] = 0;
		}
		else if (bitwise(REGOPTION_THREAD_LINEAR_NEWEST, $vboptions['defaultregoptions']))
		{
			$regoption['threadedmode'] = 0;
			$regoption['postorder'] = 1;
		}
		else if (bitwise(REGOPTION_THREAD_THREADED, $vboptions['defaultregoptions']))
		{
			$regoption['threadedmode'] = 1;
			$regoption['postorder'] = 0;
		}
		else if (bitwise(REGOPTION_THREAD_HYBRID, $vboptions['defaultregoptions']))
		{
			$regoption['threadedmode'] = 2;
			$regoption['postorder'] = 0;
		}
		else
		{
			$regoption['threadedmode'] = 0;
			$regoption['postorder'] = 0;
		}

		$userfield = '';
		$user = array(
			'invisible' => iif(bitwise(REGOPTION_INVISIBLEMODE, $vboptions['defaultregoptions']), 1, 0),
			'daysprune' => -1,
			'joindate' => TIMENOW,
			'lastactivity' => TIMENOW,
			'lastpost' => 0,
			'adminemail' => iif(bitwise(REGOPTION_ADMINEMAIL, $vboptions['defaultregoptions']), 1, 0),
			'showemail' => iif(bitwise(REGOPTION_RECEIVEEMAIL, $vboptions['defaultregoptions']), 1, 0),
			'receivepm' => iif(bitwise(REGOPTION_ENABLEPM, $vboptions['defaultregoptions']), 1, 0),
			'emailonpm' => iif(bitwise(REGOPTION_EMAILONPM, $vboptions['defaultregoptions']), 1, 0),
			'pmpopup' => iif(bitwise(REGOPTION_PMPOPUP, $vboptions['defaultregoptions']), 1, 0),
			'showvcard' => iif(bitwise(REGOPTION_VCARD, $vboptions['defaultregoptions']), 1, 0),
			'autosubscribe' => $regoption['autosubscribe'],
			'showreputation' => iif(bitwise(REGOPTION_SHOWREPUTATION, $vboptions['defaultregoptions']), 1, 0),
			'reputation' => $vboptions['reputationdefault'],
			'showsignatures' => iif(bitwise(REGOPTION_SIGNATURE, $vboptions['defaultregoptions']), 1, 0),
			'showavatars' => iif(bitwise(REGOPTION_AVATAR, $vboptions['defaultregoptions']), 1, 0),
			'showimages' => iif(bitwise(REGOPTION_IMAGE, $vboptions['defaultregoptions']), 1, 0),
			'postorder' => $regoption['postorder'],
			'threadedmode' => $regoption['threadedmode'],
			'showvbcode' => $regoption['showvbcode'],
			'usergroupid' => 2,
			'dstauto' => 1
		);
	}

	// get threaded mode options
	if ($user['threadedmode'] == 1 OR $user['threadedmode'] == 2)
	{
		$threaddisplaymode = $user['threadedmode'];
	}
	else
	{
		if ($user['postorder'] == 0)
		{
			$threaddisplaymode = 0;
		}
		else
		{
			$threaddisplaymode = 3;
		}
	}

	// make array for daysprune menu
	$pruneoptions = array(
		'0' => '- ' . $vbphrase['use_forum_default'] . ' -',
		'1' => $vbphrase['show_threads_from_last_day'],
		'2' => construct_phrase($vbphrase['show_threads_from_last_x_days'], 2),
		'7' => $vbphrase['show_threads_from_last_week'],
		'10' => construct_phrase($vbphrase['show_threads_from_last_x_days'], 10),
		'14' => construct_phrase($vbphrase['show_threads_from_last_x_weeks'], 2),
		'30' => $vbphrase['show_threads_from_last_month'],
		'45' => construct_phrase($vbphrase['show_threads_from_last_x_days'], 45),
		'60' => construct_phrase($vbphrase['show_threads_from_last_x_months'], 2),
		'75' => construct_phrase($vbphrase['show_threads_from_last_x_days'], 75),
		'100' => construct_phrase($vbphrase['show_threads_from_last_x_days'], 100),
		'365' => $vbphrase['show_threads_from_last_year'],
		'-1' => $vbphrase['show_all_threads']
	);
	if ($pruneoptions["$user[daysprune]"] == '')
	{
		$pruneoptions["$user[daysprune]"] = construct_phrase($vbphrase['show_threads_from_last_x_days'], $user['daysprune']);
	}

	// start main table

	?>
	<table cellpadding="0" cellspacing="0" border="0" width="<?php echo $OUTERTABLEWIDTH; ?>" align="center"><tr valign="top"><td>
	<table cellpadding="4" cellspacing="0" border="0" align="center" width="100%" class="tborder">
	<?php

	print_form_header('user', 'update', 0, 0);
	construct_hidden_code('userid', $userid);
	construct_hidden_code('ousergroupid', $user['usergroupid']);
	construct_hidden_code('odisplaygroupid', $user['displaygroupid']);

	if ($userid)
	{
		// QUICK LINKS SECTION
		print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['user'], $user['username'], $userid));
		print_label_row($vbphrase['quick_user_links'], '<select name="quicklinks" onchange="window.location=this.options[this.selectedIndex].value;" tabindex="1" class="bginput">' . construct_select_options($quicklinks) . '</select><input type="button" class="button" value="' . $vbphrase['go'] . '" onclick="window.location=this.form.quicklinks.options[this.form.quicklinks.selectedIndex].value;" tabindex="2" />');
		print_table_break('', $INNERTABLEWIDTH);
	}

	// PROFILE SECTION
	print_table_header($vbphrase['profile']);
	print_input_row($vbphrase['username'], 'user[username]', $user['username'], 0);
	print_input_row($vbphrase['password'], 'password');
	print_input_row($vbphrase['email'], 'user[email]', $user['email'], 0);
	print_select_row($vbphrase['language'] , 'user[languageid]', array('0' => $vbphrase['use_forum_default']) + fetch_language_titles_array('', 0), $user['languageid']);
	print_input_row($vbphrase['user_title'], 'user[usertitle]', $user['usertitle']);
	print_select_row($vbphrase['custom_user_title'], 'user[customtitle]', array(0 => $vbphrase['no'], 1 => $vbphrase['yes'], 2 => $vbphrase['yes_but_not_parsing_html']), $user['customtitle']);
	print_input_row($vbphrase['personal_home_page'], 'user[homepage]', $user['homepage'], 0);

	print_time_row($vbphrase['birthday'], 'birthday', $user['birthday'], 0, 1);
	print_textarea_row($vbphrase['signature'], 'signature', $user['signature'], 8, 45);
	print_input_row($vbphrase['icq_uin'], 'user[icq]', $user['icq'], 0);
	print_input_row($vbphrase['aim_screen_name'], 'user[aim]', $user['aim'], 0);
	print_input_row($vbphrase['yahoo_id'], 'user[yahoo]', $user['yahoo'], 0);
	print_input_row($vbphrase['msn_id'], 'user[msn]', $user['msn'], 0);
	print_yes_no_row($vbphrase['coppa_user'], 'options[coppauser]', $user['coppauser']);
	print_input_row($vbphrase['parent_email_address'], 'user[parentemail]', $user['parentemail'], 0);
	print_input_row($vbphrase['post_count'], 'user[posts]', $user['posts']);
	if ($user['referrerid'])
	{
		$referrername = $DB_site->query_first("SELECT username FROM " . TABLE_PREFIX . "user WHERE userid = $user[referrerid]");
		$user['referrer'] = $referrername['username'];
	}
	print_input_row($vbphrase['referrer'], 'referrer', $user['referrer'], 0);
	print_input_row($vbphrase['ip_address'], 'user[ipaddress]', $user['ipaddress']);
	print_table_break('', $INNERTABLEWIDTH);

	// USER IMAGE SECTION
	print_table_header($vbphrase['image_options']);
	if ($user['avatarid'])
	{
		$avatarurl = iif(substr($user['avatarpath'], 0, 7) != 'http://' AND substr($user['avatarpath'], 0, 1) != '/', '../') . $user['avatarpath'];
	}
	else
	{
		if ($user['hascustomavatar'])
		{
			if ($vboptions['usefileavatar'])
			{
				$avatarurl = iif(substr($vboptions['avatarurl'] , 0, 7) == 'http://', '', '../') . "$vboptions[avatarurl]/avatar$user[userid]_$user[avatarrevision].gif";
			}
			else
			{
				$avatarurl = "../image.php?$session[sessionurl]userid=$user[userid]&amp;dateline=$user[avatardateline]";
			}
		}
		else
		{
			$avatarurl = "../$vboptions[cleargifurl]";
		}
	}
	if ($user['profilepic'])
	{
		$profilepicurl = "../image.php?$session[sessionurl]userid=$user[userid]&amp;type=profile&amp;dateline=$user[profilepicdateline]";
	}
	else
	{
		$profilepicurl = "../$vboptions[cleargifurl]";
	}
	print_label_row($vbphrase['avatar'] . '<input type="image" src="../' . $vboptions['cleargifurl'] . '" alt="" />', '<img src="' . $avatarurl . '" alt="" align="top" /> &nbsp; <input type="submit" class="button" tabindex="1" name="modifyavatar" value="' . $vbphrase['change_avatar'] . '" />');
	print_label_row($vbphrase['profile_picture'] . '<input type="image" src="../' . $vboptions['cleargifurl'] . '" alt="" />', '<img src="' . $profilepicurl . '" alt="" align="top" /> &nbsp; <input type="submit" class="button" tabindex="1" name="modifyprofilepic" value="' . $vbphrase['change_profile_picture'] . '" />');
	print_table_break('', $INNERTABLEWIDTH);

	// PROFILE FIELDS SECTION
	print_table_header($vbphrase['user_profile_fields']);
	$profilefields = $DB_site->query("SELECT * FROM " . TABLE_PREFIX . "profilefield ORDER by displayorder");
	while ($profilefield = $DB_site->fetch_array($profilefields))
	{
		print_profilefield_row($profilefield, $userfield);
	}

	if ($vboptions['cp_usereditcolumns'] == 2)
	{
		?>
		</table>
		</td><td>&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
		<table cellpadding="4" cellspacing="0" border="0" align="center" width="100%" class="tborder">
		<?php
	}
	else
	{
		print_table_break('', $INNERTABLEWIDTH);
	}

	// USERGROUP SECTION
	print_table_header($vbphrase['usergroup_options']);
	print_chooser_row($vbphrase['primary_usergroup'], 'user[usergroupid]', 'usergroup', $user['usergroupid']);
	if (!empty($user['membergroupids']))
	{
		$usergroupids = $user['usergroupid'] . (!empty($user['membergroupids']) ? ',' . $user['membergroupids'] : '');
		print_chooser_row($vbphrase['display_usergroup'], 'user[displaygroupid]', 'usergroup', iif($user['displaygroupid'] == 0, -1, $user['displaygroupid']), $vbphrase['default'], 0, "WHERE usergroupid IN ($usergroupids)");
	}
	$tempgroup = $user['usergroupid'];
	$user['usergroupid'] = 0;
	print_membergroup_row($vbphrase['additional_usergroups'], 'membergroup', 0, $user);
	print_table_break('', $INNERTABLEWIDTH);
	$user['usergroupid'] = $tempgroup;

	// reputation SECTION
	require_once('./includes/functions_reputation.php');

	if ($user['userid'])
	{
		$perms = fetch_permissions(0, $user['userid'], $user);
	}
	else
	{
		$perms = array();
	}
	$score = fetch_reppower($user, $perms);

	print_table_header($vbphrase['reputation']);
	print_yes_no_row($vbphrase['display_reputation'], 'options[showreputation]', $user['showreputation']);
	print_input_row($vbphrase['reputation_level'], 'user[reputation]', $user['reputation']);
	print_label_row($vbphrase['current_reputation_power'], $score, '', 'top', 'reputationpower');
	print_table_break('',$INNERTABLEWIDTH);

	// BROWSING OPTIONS SECTION
	print_table_header($vbphrase['browsing_options']);
	print_yes_no_row($vbphrase['receive_admin_emails'], 'options[adminemail]', $user['adminemail']);
	print_yes_no_row($vbphrase['display_email'], 'options[showemail]', $user['showemail']);
	print_yes_no_row($vbphrase['invisible_mode'], 'options[invisible]', $user['invisible']);
	print_yes_no_row($vbphrase['allow_vcard_download'], 'options[showvcard]', $user['showvcard']);
	print_yes_no_row($vbphrase['receive_private_messages'], 'options[receivepm]', $user['receivepm']);
	print_yes_no_row($vbphrase['send_notification_email_when_a_private_message_is_received'], 'options[emailonpm]', $user['emailonpm']);
	print_yes_no_row($vbphrase['pop_up_notification_box_when_a_private_message_is_received'], 'user[pmpopup]', $user['pmpopup']);
	print_yes_no_row($vbphrase['display_signatures'], 'options[showsignatures]', $user['showsignatures']);
	print_yes_no_row($vbphrase['display_avatars'], 'options[showavatars]', $user['showavatars']);
	print_yes_no_row($vbphrase['display_images'], 'options[showimages]', $user['showimages']);
	//print_yes_no_row($vbphrase['use_email_notification_by_default'], 'options[emailnotification]', $user['emailnotification']);
	print_radio_row($vbphrase['auto_subscription_mode'], 'user[autosubscribe]', array(
		-1 => $vbphrase['subscribe_choice_none'],
		0  => $vbphrase['subscribe_choice_0'],
		1  => $vbphrase['subscribe_choice_1'],
		2  => $vbphrase['subscribe_choice_2'],
		3  => $vbphrase['subscribe_choice_3'],
	), $user['autosubscribe'], 'smallfont');


	print_radio_row($vbphrase['thread_display_mode'], 'threaddisplaymode', array(
		0 => "$vbphrase[linear] - $vbphrase[oldest_first]",
		3 => "$vbphrase[linear] - $vbphrase[newest_first]",
		2 => $vbphrase['hybrid'],
		1 => $vbphrase['threaded']
	), $threaddisplaymode, 'smallfont');

	print_radio_row($vbphrase['message_editor_interface'], 'user[showvbcode]', array(
		0 => $vbphrase['do_not_show_editor_toolbar'],
		1 => $vbphrase['show_standard_editor_toolbar'],
		2 => $vbphrase['show_enhanced_editor_toolbar']
	), $user['showvbcode'], 'smallfont');

	construct_style_chooser($vbphrase['style'], 'user[styleid]', $user['styleid']);
	print_table_break('', $INNERTABLEWIDTH);

	// TIME FIELDS SECTION
	print_table_header($vbphrase['time_options']);
	print_select_row($vbphrase['timezone'], 'user[timezoneoffset]', fetch_timezones_array(), $user['timezoneoffset']);
	print_yes_no_row($vbphrase['automatically_detect_dst_settings'], 'options[dstauto]', $user['dstauto']);
	print_yes_no_row($vbphrase['dst_currently_in_effect'], 'options[dstonoff]', $user['dstonoff']);
	print_select_row($vbphrase['default_view_age'], 'user[daysprune]', $pruneoptions, $user['daysprune']);
	print_time_row($vbphrase['join_date'], 'joindate', $user['joindate']);
	print_time_row($vbphrase['last_activity'], 'lastactivity', $user['lastactivity']);
	print_time_row($vbphrase['last_post'], 'lastpost', $user['lastpost']);

	?>
	</table>
	</tr>
	<?php

	print_table_break('', $OUTERTABLEWIDTH);
	$tableadded = 1;
	print_submit_row($vbphrase['save']);

}

// ###################### Start do update #######################
if ($_POST['do'] == 'update')
{
	globalize($_POST, array(
		'password',
		'user',
		'membergroup',
		'modifyavatar',
		'birthday',
		'signature',
		'modifyprofilepic',
		'joindate',
		'lastactivity',
		'lastpost',
		'options',
		'referrer',
		'threaddisplaymode' => INT,
		'profile'
	));
	if (!is_array($options))
	{
		$options = array();
	}

	// check for semi-colons
	if (preg_match('/(?<!&#[0-9]{3}|&#[0-9]{4}|&#[0-9]{5});/', $user['username']))
	{
		print_stop_message('username_contains_semi_colons');
	}

	if (is_array($membergroup) AND in_array($user['usergroupid'], $membergroup))
	{
		print_stop_message('usergroup_equals_secondary');
	}

	$userid = intval($_POST['userid']);

	$noalter = explode(',', $undeletableusers);
	if (!empty($noalter[0]) AND in_array($userid, $noalter))
	{
		print_stop_message('user_is_protected_from_alteration_by_undeletableusers_var');
	}

	$olduserinfo = fetch_userinfo($userid);
	if (!is_array($olduserinfo))
	{
		$olduserinfo = array();
	}

	// get correct options for thread display mode
	switch($threaddisplaymode)
	{
		// threaded mode
		case 1:
			$options['postorder'] = 0;
			$user['threadedmode'] = 1;
			break;

		// hybrid mode
		case 2:
			$options['postorder'] = 0;
			$user['threadedmode'] = 2;
			break;

		// linear, newest first
		case 3:
			$options['postorder'] = 1;
			$user['threadedmode'] = 0;
			break;

		// linear, oldest first
		default:
			$options['postorder'] = 0;
			$user['threadedmode'] = 0;
	}

	$user['username'] = strip_blank_ascii($user['username'], ' ');
	$user['reputation'] = intval($user['reputation']);
	$user['posts'] = intval($user['posts']);
	$user['username'] = htmlspecialchars_uni($user['username']);
	$user['email'] = htmlspecialchars_uni($user['email']);
	$user['parentemail'] = htmlspecialchars_uni($user['parentemail']);
	$user['homepage'] = htmlspecialchars_uni($user['homepage']);
	if ($user['homepage'] AND preg_match('#^www\.#si', $user['homepage']))
	{
		$user['homepage'] = 'http://' . $user['homepage'];
	}
	$user['icq'] = htmlspecialchars_uni($user['icq']);
	$user['aim'] = htmlspecialchars_uni($user['aim']);
	$user['yahoo'] = htmlspecialchars_uni($user['yahoo']);
	$user['msn'] = htmlspecialchars_uni($user['msn']);
	if ($user['displaygroupid'] == -1)
	{
		$user['displaygroupid'] = 0;
	}
	if (!empty($password))
	{
		if ($userid)
		{
			$salt = $olduserinfo['salt'];
		}
		else
		{
			require_once('./includes/functions_user.php');
			$salt = fetch_user_salt(3);
			$user['salt'] = $salt;
		}
		$user['password'] = md5(md5($password) . $salt);
	}

	if (empty($user['username']))
	{
		print_stop_message('invalid_username_specified');
	}

	if ($exists = $DB_site->query_first("
		SELECT userid
		FROM " . TABLE_PREFIX . "user
		WHERE username = '" . addslashes(htmlspecialchars_uni($user['username'])) . "'
			AND userid <> $userid
	"))
	{
		print_stop_message(
			'name_exists',
			$vbphrase['user'],
			"<a href=\"user.php?$session[sessionurl]do=edit&userid=$exists[userid]\" target=\"_blank\">" . htmlspecialchars_uni($user['username']) . '</a>'
		);
	}

	if (!empty($referrer))
	{
		if (strtolower($user['username']) == strtolower($referrer))
		{
			print_stop_message('a_user_may_not_refer_themself');
		}
		if ($referrerid = $DB_site->query_first("SELECT userid FROM " . TABLE_PREFIX . "user WHERE username = '" . addslashes(htmlspecialchars_uni($referrer)) . "'"))
		{
			$user['referrerid'] = $referrerid['userid'];
		}
		else
		{
			print_stop_message('invalid_referrer_specified');
		}
	}
	else
	{
		$user['referrerid'] = 0;
	}

	if (!$userid)
	{ // this is an add!
		if (empty($user['password']))
		{
			print_stop_message('invalid_password_specified');
		}
	}
	else
	{

		// check that not removing last admin
		$countadmin = $DB_site->query_first("
			SELECT COUNT(*) AS users
			FROM " . TABLE_PREFIX . "user AS user, " . TABLE_PREFIX . "usergroup AS usergroup
			WHERE user.usergroupid = usergroup.usergroupid
				AND usergroup.adminpermissions & " . CANCONTROLPANEL . "
				AND user.userid <> $userid
		");
		$getperms = $DB_site->query_first("
			SELECT adminpermissions
			FROM " . TABLE_PREFIX . "usergroup
			WHERE usergroupid = $user[usergroupid]
		");
		if ($countadmin['users'] == 0 AND !(intval($getperms['adminpermissions']) & CANCONTROLPANEL))
		{
			print_stop_message('cant_de_admin_last_admin');
		}
	}

	require_once('./includes/functions_misc.php');
	$user['joindate'] = vbmktime(12, 0, 0, intval($joindate['month']), intval($joindate['day']), intval($joindate['year']));
	$user['lastactivity'] = vbmktime(intval($lastactivity['hour']), intval($lastactivity['minute']), 0, intval($lastactivity['month']), intval($lastactivity['day']), intval($lastactivity['year']));
	$user['lastpost'] = vbmktime(intval($lastpost['hour']), intval($lastpost['minute']), 0, intval($lastpost['month']), intval($lastpost['day']), intval($lastpost['year']));

	if ($birthday['month'] > 0 AND $birthday['day'] > 0)
	{
		if ($birthday['year'] < 1901 OR $birthday['year'] > date('Y'))
		{
			$birthday['year'] = '0000';
		}
		if ($birthday['month'] < 10)
		{
			$birthday['month'] = '0' . $birthday['month'];
		}
		if ($birthday['day'] < 10)
		{
			$birthday['day'] = '0' . $birthday['day'];
		}
		$user['birthday'] = $birthday['month'] . '-' . $birthday['day'] . '-' . $birthday['year'];
		$user['birthday_search'] = $birthday['year'] . '-' . $birthday['month'] . '-' . $birthday['day'];
	}
	else
	{
		$user['birthday'] = '';
		$user['birthday_search'] = '';
	}

	require_once('./includes/functions_misc.php');
	$user['options'] = convert_array_to_bits(array_merge($olduserinfo , $options), $_USEROPTIONS);

	// Determine this user's reputationlevelid.
	$reputationlevel = $DB_site->query_first("
		SELECT reputationlevelid
		FROM " . TABLE_PREFIX . "reputationlevel
		WHERE $user[reputation] >= minimumreputation
		ORDER BY minimumreputation DESC
	");
	$user['reputationlevelid'] = intval($reputationlevel['reputationlevelid']);

	if ($user['customtitle'] == 0)
	{
		$usergroup = $DB_site->query_first("
			SELECT usertitle
			FROM " . TABLE_PREFIX . "usergroup
			WHERE usergroupid = $user[usergroupid]
		");
		if (empty($usergroup['usertitle']))
		{
			$gettitle = $DB_site->query_first("
				SELECT title
				FROM " . TABLE_PREFIX . "usertitle
				WHERE minposts <= $user[posts] + 1
				ORDER BY minposts DESC
			");
			$user['usertitle'] = $gettitle['title'];
		}
		else
		{
			$user['usertitle'] = $usergroup['usertitle'];
		}
	}

	if (is_array($membergroup))
	{
		foreach($membergroup AS $index => $value)
		{
			if ($value == $user['usergroupid'])
			{
				unset($membergroup["$index"]);
			}
		}
		$user['membergroupids'] = implode(',', $membergroup);
		if (!in_array($user['displaygroupid'], $membergroup))
		{
			$user['displaygroupid'] = 0;
		}
	}
	else
	{
		$user['membergroupids'] = '';
		$user['displaygroupid'] = 0;
	}

	require_once('./includes/functions_databuild.php');

	if ($userid)
	{
		// editing user

		// if we're changing the user's usergroups, update subscriptions to reflect
		if ($user['usergroupid'] != $olduserinfo['usergroupid'] OR
			$user['membergroupids'] != $olduserinfo['membergroupids'])
		{
			unset($olduserinfo['forumpermissions']);
			cache_permissions($olduserinfo);
			$old_canview = array();
			foreach ($olduserinfo['forumpermissions'] AS $forumid => $perms)
			{
				if ($perms & CANVIEW)
				{
					$old_canview[] = $forumid;
				}
			}

			$user_perms = $user;
			unset($user_perms['forumpermissions']);
			cache_permissions($user_perms);
			$remove_subs = array();
			foreach ($old_canview AS $forumid)
			{
				if (!($user_perms['forumpermissions']["$forumid"] & CANVIEW))
				{
					$remove_subs[] = $forumid;
				}
			}
			foreach ($user_perms['forumpermissions'] AS $forumid => $perms)
			{
				if ($perms & CANVIEW)
				{
					$new_canview[] = $forumid;
				}
			}

			if (sizeof($remove_subs) > 0)
			{
				$forum_list = implode(',', $remove_subs);
				$DB_site->query("
					DELETE FROM " . TABLE_PREFIX . "subscribeforum
					WHERE userid = $userid
						AND forumid IN ($forum_list)
				");

				$threads = $DB_site->query("
					SELECT subscribethread.threadid
					FROM " . TABLE_PREFIX . "subscribethread AS subscribethread
					INNER JOIN " . TABLE_PREFIX . "thread AS thread
						ON (thread.threadid = subscribethread.threadid)
					WHERE subscribethread.userid = $userid
						AND thread.forumid IN ($forum_list)
				");
				$remove_thread = array();
				while ($thread = $DB_site->fetch_array($threads))
				{
					$remove_thread[] = $thread['threadid'];
				}
				if (sizeof($remove_thread) > 0)
				{
					$DB_site->query("
						DELETE FROM " . TABLE_PREFIX . "subscribethread
						WHERE userid = $userid
							AND threadid IN (" . implode(',', $remove_thread) . ")
					");
				}
			}
		}
		// end subscription updates from perm changes

		if (empty($user['password']))
		{
			unset($user['password']);
		}
		$DB_site->query(fetch_query_sql($user, 'user', "WHERE userid=$userid"));

		build_usertextfields('signature', $signature, $userid);
	}
	else
	{
		$user['passworddate'] = date('Y-m-d');
		$DB_site->query(fetch_query_sql($user, 'user', ''));
		$userid = $DB_site->insert_id();
		$new = 1;
		$DB_site->query("
			INSERT INTO " . TABLE_PREFIX . "usertextfield
				(userid, signature)
			VALUES
				($userid, '" . addslashes($signature) . "')
		");
	}
	
	// insert record into password history
	if (!empty($user['password']))
	{
		$DB_site->query("
			INSERT INTO " . TABLE_PREFIX . "passwordhistory (userid, password, passworddate)
			VALUES ($userid, '" . addslashes($user['password']) . "', NOW())
		");
	}

	build_birthdays();

	// check if they are now part of a group with admin permissions
	$adminperm = $DB_site->query_first("
		SELECT usergroupid
		FROM " . TABLE_PREFIX . "usergroup
		WHERE adminpermissions & " . CANCONTROLPANEL . "
			AND usergroupid IN($user[usergroupid]" . iif(!empty($user['membergroupids']), ',' . $user['membergroupids']) . ")
	");

	$insertedadmin = false;
	if ($adminperm['usergroupid'])
	{ // they are a member of a usergroup with admin permission
		$insertedadmin = true;
		$DB_site->query("INSERT IGNORE INTO " . TABLE_PREFIX . "administrator (userid) VALUES ($userid)");
	}
	else
	{ // not member of a group zap admin permissions
		$DB_site->query("DELETE FROM " . TABLE_PREFIX . "administrator WHERE userid = " . $userid);
	}

	build_user_statistics();
	// check if we have just banned a user
	if ($usergroupcache["$user[usergroupid]"]['genericoptions'] & ISBANNEDGROUP)
	{
		// check to see if there is already a ban record for this user...
		if (!($check = $DB_site->query_first("SELECT userid FROM " . TABLE_PREFIX . "userban WHERE userid = $userid")))
		{
			// ... there isn't, so create one
			globalize($_POST, array('ousergroupid' => INT, 'odisplaygroupid' => INT));

			// make sure the ban lifting record doesn't loop back to a banned group
			if ($usergroupcache["$ousergroupid"]['genericoptions'] & ISBANNEDGROUP)
			{
				$ousergroupid = 2;
			}
			if ($usergroupcache["$odisplaygroupid"]['genericoptions'] & ISBANNEDGROUP)
			{
				$odisplaygroupid = 0;
			}
			// insert the record
			$DB_site->query("
				INSERT INTO " . TABLE_PREFIX . "userban
					(userid, usergroupid, displaygroupid, customtitle, usertitle, adminid, bandate, liftdate)
				VALUES
					($userid, $ousergroupid, $odisplaygroupid, $user[customtitle], '" . addslashes($user['usertitle']) . "', $bbuserinfo[userid], " . TIMENOW . ", 0)
			");
		}
	}

	$profilefields = $DB_site->query("SELECT * FROM " . TABLE_PREFIX . "profilefield");
	$userfieldsnames = '(userid';

	while ($profilefield = $DB_site->fetch_array($profilefields))
	{
		$varname = "field$profilefield[profilefieldid]";
		$$varname = $profile["$varname"];
		$optionalvar = "field$profilefield[profilefieldid]" . '_opt';
		$$optionalvar = $profile["$optionalvar"];

		$bitwise = 0;
		if ($profilefield['type'] == 'input' OR $profilefield['type'] == 'textarea')
		{
			$$varname = substr(fetch_censored_text($$varname), 0, $profilefield['maxlength']);
		}
		if ($profilefield['type'] == 'radio' OR $profilefield['type'] == 'select')
		{
			if ($$varname == 0)
			{
				$$varname = '';
			}
			else
			{
				$data = unserialize($profilefield['data']);
				foreach ($data AS $key => $val)
				{
					$key++;
					if ($key == $$varname)
					{
						$$varname = trim($val);
						break;
					}
				}
			}
			if ($profilefield['optional'] AND	 $$optionalvar)
			{
				$$varname = substr(fetch_censored_text($$optionalvar), 0, $profilefield['maxlength']);
			}
		}
		if (($profilefield['type'] == 'checkbox' OR $profilefield['type'] == 'select_multiple') AND is_array($$varname))
		{
			if (($profilefield['size'] == 0) OR (sizeof($$varname) <= $profilefield['size']))
			{
				while (list($key, $val) = each($$varname))
				{
					$bitwise += pow(2, $val - 1);
				}
				$$varname = $bitwise;
			}
			else
			{
				print_stop_message('field_option_maximum_invalid', $profilefield['size'], $profilefield['title']);
			}
		}

		$userfieldsnames .= ',field' . $profilefield['profilefieldid'];
		if ($new)
		{
			$userfields .= ",'" . addslashes(htmlspecialchars_uni($$varname)) . "'";
		}
		else
		{
			$userfields.=",$varname = '" . addslashes(htmlspecialchars_uni($$varname)) . "'";
		}
	}

	if ($new)
	{
		$DB_site->query("
			INSERT INTO " . TABLE_PREFIX . "userfield
				$userfieldsnames)
			VALUES
				($userid$userfields)
		");
	}
	else
	{
		$DB_site->query("UPDATE " . TABLE_PREFIX . "userfield SET userid = $userid$userfields WHERE userid = $userid");
	}

	if ($user['username'] != $olduserinfo['username'] AND !$new)
	{ // update tables
		$DB_site->query("
			UPDATE " . TABLE_PREFIX . "pmreceipt
			SET tousername = '" . addslashes($user['username']) . "'
			WHERE touserid = $userid
		");

		$DB_site->query("
			UPDATE " . TABLE_PREFIX . "pmtext
			SET fromusername = '" . addslashes($user['username']) . "'
			WHERE fromuserid = $userid
		");

		$olduser = strlen($olduserinfo['username']);
		$newuser = strlen($user['username']);
		$DB_site->query("
			UPDATE " . TABLE_PREFIX . "pmtext
			SET touserarray = REPLACE(touserarray, 'i:$userid;s:$olduser:\"" . addslashes($olduserinfo['username']) . "\";','i:$userid;s:$newuser:\"" . addslashes($user['username']) . "\";')
		");

		$DB_site->query("
			UPDATE " . TABLE_PREFIX . "forum
			SET lastposter = '" . addslashes($user['username']) . "'
			WHERE  lastposter = '" . addslashes($olduserinfo['username']) . "'
		");

		$DB_site->query("
			UPDATE " . TABLE_PREFIX . "thread
			SET postusername = '" . addslashes($user['username']) . "'
			WHERE postuserid = $userid
		");

		$DB_site->query("
			UPDATE " . TABLE_PREFIX . "thread
			SET lastposter = '" . addslashes($user['username']) . "'
			WHERE lastposter = '" . addslashes($olduserinfo['username']) . "'
		");

		$DB_site->query("
			UPDATE " . TABLE_PREFIX . "post
			SET username = '" . addslashes($user['username']) . "'
			WHERE userid = $userid
		");

	}

	if ($modifyavatar)
	{
		define('CP_REDIRECT', "usertools.php?do=avatar&amp;userid=$userid");
	}
	else if ($modifyprofilepic)
	{
		define('CP_REDIRECT', "usertools.php?do=profilepic&amp;userid=$userid");
	}
	else
	{
		define('CP_REDIRECT', "user.php?do=modify&amp;userid=$userid" . iif($insertedadmin, '&insertedadmin=1'));
	}

	print_stop_message('saved_user_x_successfully', $user['username']);
}

// ###################### Start Edit Access #######################
if ($_REQUEST['do'] == 'editaccess')
{
	if (!can_administer('canadminpermissions'))
	{
		print_cp_no_permission();
	}

	globalize($_REQUEST, array('userid' => INT));

	$user = $DB_site->query_first("SELECT username FROM " . TABLE_PREFIX . "user WHERE userid = $userid");

	$accesslist = $DB_site->query("SELECT * FROM " . TABLE_PREFIX . "access WHERE userid = $userid");
	while ($access = $DB_site->fetch_array($accesslist))
	{
		$accessarray[$access['forumid']] = $access;
	}

	print_form_header('user', 'updateaccess');
	construct_hidden_code('userid', $userid);

	print_table_header($vbphrase['edit_access_masks'] . ": <span class=\"normal\">$user[username]</span>", 2, 0);
	print_description_row($vbphrase['here_you_may_edit_forum_access_on_a_user_by_user_basis']);
	print_cells_row(array($vbphrase['forum'], $vbphrase['allow_access_to_forum']), 0, 'thead', -2);
	print_label_row('&nbsp;', '
		<input type="button" value="' . $vbphrase['all_yes'] . '" onclick="js_check_all_option(this.form, 1);" class="button" />
		<input type="button" value=" ' . $vbphrase['all_no'] . ' " onclick="js_check_all_option(this.form, 0);" class="button" />
		<input type="button" value="' . $vbphrase['all_default'] .'" onclick="js_check_all_option(this.form, -1);" class="button" />
	');

	require_once('./includes/functions_databuild.php');
	cache_forums();
	foreach ($forumcache AS $forumid => $forum)
	{
		if (is_array($accessarray["$forum[forumid]"]))
		{
			if ($accessarray["$forum[forumid]"]['accessmask'] == 0)
			{
				$sel = 0;
			}
			else if ($accessarray["$forum[forumid]"]['accessmask'] == 1)
			{
				$sel = 1;
			}
			else
			{
				$sel = -1;
			}
		}
		else
		{
			$sel = -1;
		}
		construct_hidden_code("oldcache[$forum[forumid]]", $sel);
		print_yes_no_other_row(construct_depth_mark($forum['depth'], '- - ') . " $forum[title]", "accessupdate[$forum[forumid]]", $vbphrase['default'], $sel);
	}
	print_submit_row();
}

// ###################### Start Update Access #######################
if ($_POST['do'] == 'updateaccess')
{
	if (!can_administer('canadminpermissions'))
	{
		print_cp_no_permission();
	}

	globalize($_POST, array('oldcache', 'userid' => INT));

	foreach ($_POST['accessupdate'] AS $forumid => $val)
	{
		if ($oldcache["$forumid"] == $val)
		{
			continue;
		}
		if ($oldcache["$forumid"] != '-1' AND $val == '-1')
		{ // remove access mask
			$removemask[] = $forumid;
		}
		else
		{ // add access mask or updating it
			$newmask[] = "($userid, $forumid, $val)";
		}

	}

	if (is_array($removemask))
	{
		$DB_site->query("
			DELETE FROM " . TABLE_PREFIX . "access
			WHERE userid = $userid
				AND forumid IN(" . implode(',', $removemask) . ")
		");
	}

	if (is_array($newmask))
	{
		$DB_site->query("
			REPLACE INTO " . TABLE_PREFIX . "access
				(userid,forumid,accessmask)
			VALUES
				" . implode(",\n\t", $newmask)
		);
	}

	$countaccess = $DB_site->query_first("
		SELECT COUNT(*) AS masks
		FROM " . TABLE_PREFIX . "access
		WHERE userid = $userid
	");
	$DB_site->query("
		UPDATE " . TABLE_PREFIX . "user
		SET options = (options" . iif($countaccess['masks'], ' | ' , ' & ~' ) . "$_USEROPTIONS[hasaccessmask])
		WHERE userid = $userid
	");

	define('CP_REDIRECT', "user.php?do=edit&amp;userid=$userid");
	print_stop_message('saved_access_masks_successfully');

}

// ###################### Start modify #######################
if ($_REQUEST['do'] == 'modify')
{
	globalize($_REQUEST, array('userid' => INT, 'insertedadmin' => INT));

	if ($userid)
	{
		$userinfo = fetch_userinfo($userid);

		print_form_header('user', 'edit', 0, 1, 'reviewform');
		print_table_header($userinfo['username'], 2, 0, '', 'center', 0);
		construct_hidden_code('userid', $userid);
		print_description_row(
			construct_link_code($vbphrase['view_profile'], "user.php?$session[sessionurl]do=edit&amp;userid=$userid") .
			iif($insertedadmin, '<br />' . construct_link_code('<span style="color:red;"><strong>' . $vbphrase['update_or_add_administration_permissions'] . '</strong></span>', "adminpermissions.php?$session[sessionurl]do=edit&amp;userid=$userid"))
		);
		print_table_footer();
	}

	print_form_header('', '');
	print_table_header($vbphrase['quick_search']);
	print_description_row("
		<ul>
			<li><a href=\"user.php?$session[sessionurl]do=find\">" . $vbphrase['show_all_users'] . "</a></li>
			<li><a href=\"user.php?$session[sessionurl]do=find&amp;orderby=posts&amp;direction=DESC&amp;limitnumber=30\">" . $vbphrase['list_top_posters'] . "</a></li>
			<li><a href=\"user.php?$session[sessionurl]do=find&amp;user[lastactivityafter]=" . (TIMENOW - 86400) . "&amp;orderby=lastactivity&amp;direction=DESC\">" . $vbphrase['list_visitors_in_the_last_24_hours'] . "</a></li>
			<li><a href=\"user.php?$session[sessionurl]do=find&amp;orderby=joindate&direction=DESC&amp;limitnumber=30\">" . $vbphrase['list_new_registrations'] . "</a></li>
			<li><a href=\"user.php?$session[sessionurl]do=moderate\">" . $vbphrase['list_users_awaiting_moderation'] . "</a></li>
			<li><a href=\"user.php?$session[sessionurl]do=find&amp;user[coppauser]=1\">" . $vbphrase['show_all_coppa_users'] . "</a></li>
		</ul>
	");
	print_table_footer();

	print_form_header('user', 'find');
	print_table_header($vbphrase['advanced_search']);
	print_description_row($vbphrase['if_you_leave_a_field_blank_it_will_be_ignored']);
	print_description_row('<img src="../' . $vboptions['cleargifurl'] . '" alt="" width="1" height="2" />', 0, 2, 'thead');
	print_user_search_rows();
	print_table_break();

	print_table_header($vbphrase['display_options']);
	print_yes_no_row($vbphrase['display_username'], 'display[username]', 1);
	print_yes_no_row($vbphrase['display_options'], 'display[options]', 1);
	print_yes_no_row($vbphrase['display_usergroup'], 'display[usergroup]', 0);
	print_yes_no_row($vbphrase['display_email'], 'display[email]', 1);
	print_yes_no_row($vbphrase['display_parent_email_address'], 'display[parentemail]', 0);
	print_yes_no_row($vbphrase['display_coppa_user'],'display[coppauser]', 0);
	print_yes_no_row($vbphrase['display_home_page'], 'display[homepage]', 0);
	print_yes_no_row($vbphrase['display_icq_uin'], 'display[icq]', 0);
	print_yes_no_row($vbphrase['display_aim_screen_name'], 'display[aim]', 0);
	print_yes_no_row($vbphrase['display_yahoo_id'], 'display[yahoo]', 0);
	print_yes_no_row($vbphrase['display_msn_id'], 'display[msn]', 0);
	print_yes_no_row($vbphrase['display_signature'], 'display[signature]', 0);
	print_yes_no_row($vbphrase['display_user_title'], 'display[usertitle]', 0);
	print_yes_no_row($vbphrase['display_join_date'], 'display[joindate]', 1);
	print_yes_no_row($vbphrase['display_last_activity'], 'display[lastactivity]', 1);
	print_yes_no_row($vbphrase['display_last_post'], 'display[lastpost]', 0);
	print_yes_no_row($vbphrase['display_post_count'], 'display[posts]', 1);
	print_yes_no_row($vbphrase['display_reputation'], 'display[reputation]', 0);
	print_yes_no_row($vbphrase['display_ip_address'], 'display[ipaddress]', 0);
	print_yes_no_row($vbphrase['display_birthday'], 'display[birthday]', 0);
	print_description_row('<div align="' . $stylevar['right'] .'"><input type="submit" class="button" value=" ' . $vbphrase['find'] . ' " tabindex="1" /></div>');

	print_table_header($vbphrase['user_profile_field_options']);
	$profilefields = $DB_site->query("SELECT profilefieldid, title FROM " . TABLE_PREFIX . "profilefield");
	while ($profilefield = $DB_site->fetch_array($profilefields))
	{
		print_yes_no_row(construct_phrase($vbphrase['display_x'], $profilefield['title']), "display[field$profilefield[profilefieldid]]", 0);
	}
	print_description_row('<div align="' . $stylevar['right'] .'"><input type="submit" class="button" value=" ' . $vbphrase['find'] . ' " tabindex="1" /></div>');
	print_table_break();

	print_table_header($vbphrase['sorting_options']);
	print_label_row($vbphrase['order_by'], '
		<select name="orderby" tabindex="1" class="bginput">
		<option value="username" selected=\"selected\">' . $vbphrase['username'] . '</option>
		<option value="email">' . $vbphrase['email'] . '</option>
		<option value="joindate">' . $vbphrase['join_date'] . '</option>
		<option value="lastactivity">' . $vbphrase['last_activity'] . '</option>
		<option value="lastpost">' . $vbphrase['last_post'] . '</option>
		<option value="posts">' . $vbphrase['post_count'] . '</option>
		<option value="birthday_search">' . $vbphrase['birthday'] . '</option>
		<option value="reputation">' . $vbphrase['reputation'] . '</option>
		</select>
		<select name="direction" tabindex="1" class="bginput">
		<option value="">' . $vbphrase['ascending'] . '</option>
		<option value="DESC">' . $vbphrase['descending'] . '</option>
		</select>
	', '', 'top', 'orderby');
	print_input_row($vbphrase['starting_at_result'], 'limitstart', 1);
	print_input_row($vbphrase['maximum_results'], 'limitnumber', 50);

	print_submit_row($vbphrase['find'], $vbphrase['reset'], 2, '', '<input type="submit" class="button" value="' . $vbphrase['exact_match'] . '" tabindex="1" name="user[exact]" />');

}

// ###################### Start find #######################
if ($_REQUEST['do'] == 'find2' AND defined('DONEFIND'))
{
	// carries on from do == find at top of script

	$limitfinish = $limitstart + $limitnumber;

	// display the column headings
	$header = array();
	if ($display['username'])
	{
		$header[] = $vbphrase['username'];
	}
	if ($display['usergroup'])
	{
		$header[] = $vbphrase['usergroup'];
	}
	if ($display['email'])
	{
		$header[] = $vbphrase['email'];
	}
	if ($display['parentemail'])
	{
		$header[] = $vbphrase['parent_email_address'];
	}
	if ($display['coppauser'])
	{
		$header[] = $vbphrase['coppa_user'];
	}
	if ($display['homepage'])
	{
		$header[] = $vbphrase['personal_home_page'];
	}
	if ($display['icq'])
	{
		$header[] = $vbphrase['icq_uin'];
	}
	if ($display['aim'])
	{
		$header[] = $vbphrase['aim_screen_name'];
	}
	if ($display['yahoo'])
	{
		$header[] = $vbphrase['yahoo_id'];
	}
	if ($display['msn'])
	{
		$header[] = $vbphrase['msn_id'];
	}
	if ($display['signature'])
	{
		$header[] = $vbphrase['signature'];
	}
	if ($display['usertitle'])
	{
		$header[] = $vbphrase['user_title'];
	}
	if ($display['joindate'])
	{
		$header[] = $vbphrase['join_date'];
	}
	if ($display['lastactivity'])
	{
		$header[] = $vbphrase['last_activity'];
	}
	if ($display['lastpost'])
	{
		$header[] = $vbphrase['last_post'];
	}
	if ($display['posts'])
	{
		$header[] = $vbphrase['post_count'];
	}
	if ($display['reputation'])
	{
		$header[] = $vbphrase['reputation'];
	}
	if ($display['ipaddress'])
	{
		$header[] = $vbphrase['ip_address'];
	}
	if ($display['birthday'])
	{
		$header[] = $vbphrase['birthday'];
	}

	$profilefields = $DB_site->query("SELECT profilefieldid,title,type,data FROM " . TABLE_PREFIX . "profilefield");
	while ($profilefield = $DB_site->fetch_array($profilefields))
	{
		if ($display["field$profilefield[profilefieldid]"])
		{
			$header[] = $profilefield['title'];
		}
	}

	if ($display['options'])
	{
		$header[] = $vbphrase['options'];
	}

	// get number of cells for use in 'colspan=' attributes
	$colspan = sizeof($header);
	// a little javascript for the options menus
	?>
	<script type="text/javascript">
	function js_usergroup_jump(userinfo)
	{
		var value = eval("document.cpform.u" + userinfo + ".options[document.cpform.u" + userinfo + ".selectedIndex].value");
		if (value != "")
		{
			switch (value)
			{
				case 'edit': page = "edit&userid=" + userinfo; break;
				case 'kill': page = "remove&userid=" + userinfo; break;
				case 'access': page = "editaccess&userid=" + userinfo; break;
				default: page = "emailpassword&email=" + value; break;
			}
			window.location = "user.php?s=<?php echo $session['sessionhash']; ?>&do=" + page;
		}
	}
	</script>
	<?php

	print_form_header('user', 'find');
	print_table_header(construct_phrase($vbphrase['showing_users_x_to_y_of_z'], ($limitstart + 1), iif($limitfinish > $countusers['users'], $countusers['users'], $limitfinish), $countusers['users']), $colspan);
	print_cells_row($header, 1);

	// cache usergroups if required to save querying every single one...
	if ($display['usergroup'] AND !is_array($groupcache))
	{
		$groupcache = array();
		$groups = $DB_site->query("SELECT usergroupid, title FROM " . TABLE_PREFIX . "usergroup");
		while ($group = $DB_site->fetch_array($groups))
		{
			$groupcache[$group['usergroupid']] = $group['title'];
		}
		$DB_site->free_result($groups);
	}

	// now display the results
	while ($user = $DB_site->fetch_array($users))
	{

		$cell = array();
		if ($display['username'])
		{
			$cell[] = "<a href=\"user.php?$session[sessionurl]do=edit&userid=$user[userid]\"><b>$user[username]</b></a>&nbsp;";
		}
		if ($display['usergroup'])
		{
			$cell[] = $groupcache[$user['usergroupid']];
		}
		if ($display['email'])
		{
			$cell[] = "<a href=\"mailto:$user[email]\">$user[email]</a>";
		}
		if ($display['parentemail'])
		{
			$cell[] = "<a href=\"mailto:$user[parentemail]\">$user[parentemail]</a>";
		}
		if ($display['coppauser'])
		{
			$cell[] = iif($user['coppauser'] == 1, $vbphrase['yes'], $vbphrase['no']);
		}
		if ($display['homepage'])
		{
			$cell[] = iif($user['homepage'], "<a href=\"$user[homepage]\" target=\"_blank\">$user[homepage]</a>");
		}
		if ($display['icq'])
		{
			$cell[] = $user['icq'];
		}
		if ($display['aim'])
		{
			$cell[] = $user['aim'];
		}
		if ($display['yahoo'])
		{
			$cell[] = $user['yahoo'];
		}
		if ($display['msn'])
		{
			$cell[] = $user['msn'];
		}
		if ($display['signature'])
		{
			$cell[] = nl2br(htmlspecialchars_uni($user['signature']));
		}
		if ($display['usertitle'])
		{
			$cell[] = $user['usertitle'];
		}
		if ($display['joindate'])
		{
			$cell[] = '<span class="smallfont">' . vbdate($vboptions['dateformat'], $user['joindate']) . '</span>';
		}
		if ($display['lastactivity'])
		{
			$cell[] = '<span class="smallfont">' . vbdate($vboptions['dateformat'], $user['lastactivity']) . '</span>';
		}
		if ($display['lastpost'])
		{
			$cell[] = '<span class="smallfont">' . iif($user['lastpost'], vbdate($vboptions['dateformat'], $user['lastpost']), '<i>' . $vbphrase['never'] . '</i>') . '</span>';
		}
		if ($display['posts'])
		{
			$cell[] = vb_number_format($user['posts']);
		}
		if ($display['reputation'])
		{
			$cell[] = vb_number_format($user['reputation']);
		}
		if ($display['ipaddress'])
		{
			$cell[] = iif(!empty($user['ipaddress']), "$user[ipaddress] (" . @gethostbyaddr($user['ipaddress']) . ')', '&nbsp;');
		}
		if ($display['birthday'])
		{
			$cell[] = $user['birthday_search'];
		}
		$DB_site->data_seek(0, $profilefields);
		while ($profilefield = $DB_site->fetch_array($profilefields))
		{
			$profilefieldname = 'field' . $profilefield['profilefieldid'];
			if ($display["field$profilefield[profilefieldid]"])
			{
				$varname = 'field' . $profilefield['profilefieldid'];
				if ($profilefield['type'] == 'checkbox' OR $profilefield['type'] == 'select_multiple')
				{
					$output = '';
					$data = unserialize($profilefield['data']);
					foreach ($data AS $index => $value)
					{
						if ($user["$profilefieldname"] & pow(2, $index))
						{
							if (!empty($output))
							{
								$output .= '<b>,</b> ';
							}
							$output .= $value;
						}
					}
					$cell[] = $output;
				}
				else
				{
					$cell[] = $user["$varname"];
				}
			}
		}
		if ($display['options'])
		{
			$cell[] = "\n\t<select name=\"u$user[userid]\" onchange=\"js_usergroup_jump($user[userid]);\" class=\"bginput\">
			<option value=\"edit\">$vbphrase[view] / " . $vbphrase['edit_user'] . "</option>"
			. iif(!empty($user['email']), "<option value=\"" . unhtmlspecialchars($user[email]) . "\">" . $vbphrase['send_password_to_user'] . "</option>") . "
			<option value=\"access\">" . $vbphrase['edit_access_masks'] . "</option>
			<option value=\"kill\">" . $vbphrase['delete_user'] . "</option>\n\t</select><input type=\"button\" class=\"button\" value=\"" . $vbphrase['go'] . "\" onclick=\"js_usergroup_jump($user[userid]);\" />\n\t";
		}
		print_cells_row($cell);
	}

	construct_hidden_code('serializeduser', serialize($_REQUEST['user']));
	construct_hidden_code('serializedprofile', serialize($_REQUEST['profile']));
	construct_hidden_code('serializeddisplay', serialize($_REQUEST['display']));
	construct_hidden_code('limitnumber', $limitnumber);
	construct_hidden_code('orderby', $orderby);
	construct_hidden_code('direction', $direction);

	if ($limitstart == 0 AND $countusers['users'] > $limitnumber)
	{
		construct_hidden_code('limitstart', $limitstart + $limitnumber + 1);
		print_submit_row($vbphrase['next_page'], 0, $colspan);
	}
	else if ($limitfinish < $countusers['users'])
	{
		construct_hidden_code('limitstart', $limitstart + $limitnumber + 1);
		print_submit_row($vbphrase['next_page'], 0, $colspan, $vbphrase['prev_page'], '', true);
	}
	else if ($limitfinish >= $countusers['users'])
	{
		print_submit_row($vbphrase['first_page'], 0, $colspan, $vbphrase['prev_page'], '', true);
	}
	else
	{
		print_table_footer();
	}
}

// ###################### Start moderate + coppa #######################
if ($_REQUEST['do'] == 'moderate')
{

	$users = $DB_site->query("
		SELECT userid, username, email, ipaddress
		FROM " . TABLE_PREFIX . "user
		WHERE usergroupid = 4
		ORDER BY username
	");
	if ($DB_site->num_rows($users) == 0)
	{
		print_stop_message('no_matches_found');
	}
	else
	{
		?>
		<script type="text/javascript">
		function js_check_radio(value)
		{
			for (var i = 0; i < document.cpform.elements.length; i++)
			{
				var e = document.cpform.elements[i];
				if (e.type == 'radio' && e.name.substring(0, 8) == 'validate')
				{
					if (e.value == value)
					{
						e.checked = true;
					}
					else
					{
						e.checked = false;
					}
				}
			}
		}
		</script>
		<?php
		print_form_header('user', 'domoderate');
		print_table_header($vbphrase['users_awaiting_moderation'], 4);
		print_cells_row(array(
			$vbphrase['username'],
			$vbphrase['email'],
			$vbphrase['ip_address'],
			"<input type=\"button\" class=\"button\" value=\"" . $vbphrase['accept_all'] . "\" onclick=\"js_check_radio(1)\" />
			<input type=\"button\" class=\"button\" value=\"" . $vbphrase['delete_all'] . "\" onclick=\"js_check_radio(-1)\" />
			<input type=\"button\" class=\"button\" value=\"" . $vbphrase['ignore_all'] . "\" onclick=\"js_check_radio(0)\" />"
		), 0, 'thead', -3);
		while ($user = $DB_site->fetch_array($users))
		{
			$cell = array();
			$cell[] = "<a href=\"user.php?$session[sessionurl]do=edit&amp;userid=$user[userid]\" target=\"_user\"><b>$user[username]</b></a>";
			$cell[] = "<a href=\"mailto:$user[email]\">$user[email]</a>";
			$cell[] = "<a href=\"usertools.php?$session[sessionurl]do=doips&amp;depth=2&amp;ipaddress=$user[ipaddress]\" target=\"_user\">$user[ipaddress]</a>";
			$cell[] = "
				<label for=\"v_$user[userid]\"><input type=\"radio\" name=\"validate[$user[userid]]\" value=\"1\" id=\"v_$user[userid]\" tabindex=\"1\" checked=\"checked\" />$vbphrase[accept]</label>
				<label for=\"d_$user[userid]\"><input type=\"radio\" name=\"validate[$user[userid]]\" value=\"-1\" id=\"d_$user[userid]\" tabindex=\"1\" />$vbphrase[delete]</label>
				<label for=\"i_$user[userid]\"><input type=\"radio\" name=\"validate[$user[userid]]\" value=\"0\" id=\"i_$user[userid]\" tabindex=\"1\" />$vbphrase[ignore]</label>
			";
			print_cells_row($cell, 0, '', -4);
		}

		$template = fetch_phrase('validated', PHRASETYPEID_MAILMSG, 'email_');

		print_table_break();
		print_table_header($vbphrase['email_options']);
		print_yes_no_row($vbphrase['send_email_to_accepted_users'], 'send_validated', 1);
		print_yes_no_row($vbphrase['send_email_to_deleted_users'], 'send_deleted', 1);
		print_description_row($vbphrase['email_will_be_sent_in_user_specified_language']);

		print_table_break();
		print_submit_row($vbphrase['continue']);
	}
}

// ###################### Start do moderate and coppa #######################
if ($_POST['do'] == 'domoderate')
{

	if (!is_array($_POST['validate']))
	{
		print_stop_message('please_complete_required_fields');
	}
	else
	{
		globalize($_POST, array('send_validated' => INT, 'send_deleted' => INT));

		$evalemail_validated = array();
		$evalemail_deleted = array();

		foreach($_POST['validate'] AS $userid => $status)
		{
			$userid = intval($userid);
			$user = $DB_site->query_first("
				SELECT username, email, languageid
				FROM " . TABLE_PREFIX . "user
				WHERE userid = $userid
			");
			$username = unhtmlspecialchars($user['username']);

			$chosenlanguage = iif($user['languageid'] < 1, intval($vboptions['languageid']), intval($user['languageid']));

			if ($status == 1)
			{ // validated
				if ($send_validated)
				{
					if (!isset($evalemail_validated["$user[languageid]"]))
					{
						$email_text = $DB_site->query_first("
							SELECT text FROM " . TABLE_PREFIX . "phrase
							WHERE phrasetypeid = " . PHRASETYPEID_MAILMSG . "
								AND varname = 'moderation_validated'
								AND languageid IN(-1,0,$chosenlanguage)
							ORDER BY languageid DESC
						");
						$email_subject = $DB_site->query_first("
							SELECT text FROM " . TABLE_PREFIX . "phrase
							WHERE phrasetypeid = " . PHRASETYPEID_MAILSUB . "
								AND varname = 'moderation_validated'
								AND languageid IN(-1,0,$chosenlanguage)
							ORDER BY languageid DESC
						");

						$evalemail_validated["$user[languageid]"] = '$message = "' . str_replace("\\'", "'", addslashes($email_text['text'])) . '";'.
							'$subject = "' . str_replace("\\'", "'", addslashes($email_subject['text'])) . '";';
					}
					eval($evalemail_validated["$user[languageid]"]);
					vbmail($user['email'], $subject, $message, true);
				}

				$DB_site->query("UPDATE " . TABLE_PREFIX . "user SET usergroupid = 2 WHERE userid = $userid");
			}
			else if ($status == -1)
			{ // deleted
				if ($send_deleted)
				{
					if (!isset($evalemail_deleted["$user[languageid]"]))
					{
						$email_text = $DB_site->query_first("
							SELECT text FROM " . TABLE_PREFIX . "phrase
							WHERE phrasetypeid = " . PHRASETYPEID_MAILMSG . "
								AND varname = 'moderation_deleted'
								AND languageid IN(-1,0,$chosenlanguage)
							ORDER BY languageid DESC
						");
						$email_subject = $DB_site->query_first("
							SELECT text FROM " . TABLE_PREFIX . "phrase
							WHERE phrasetypeid = " . PHRASETYPEID_MAILSUB . "
								AND varname = 'moderation_deleted'
								AND languageid IN(-1,0,$chosenlanguage)
							ORDER BY languageid DESC
						");

						$evalemail_deleted["$user[languageid]"] = '$message = "' . str_replace("\\'", "'", addslashes($email_text['text'])) . '";'.
							'$subject = "' . str_replace("\\'", "'", addslashes($email_subject['text'])) . '";';
					}
					eval($evalemail_deleted["$user[languageid]"]);
					vbmail($user['email'], $subject, $message, true);
				}

				delete_user($userid);
			} // else, do nothing
		}

		define('CP_REDIRECT', 'index.php?do=home');
		print_stop_message('user_accounts_validated');
	}
}

// ############################# do prune users (step 2) #########################
if ($_REQUEST['do'] == 'prune_updateposts')
{

	globalize($_REQUEST, array('startat' => INT));

	$userids = fetch_adminutil_text('ids');
	if (!$userids)
	{
		$userids = '0';
	}

	$users = $DB_site->query("
		SELECT userid, username
		FROM " . TABLE_PREFIX . "user
		WHERE userid IN($userids)
		LIMIT $startat, 50
	");
	if ($DB_site->num_rows($users))
	{

		while ($user = $DB_site->fetch_array($users))
		{
			echo '<p>' . construct_phrase($vbphrase['updating_threads_posts_for_x'], $user['username']) . "\n";
			flush();
			$DB_site->query("
				UPDATE " . TABLE_PREFIX . "thread
				SET postuserid = 0,
				postusername = '" . addslashes($user['username']) . "'
				WHERE postuserid = $user[userid]
			");
			$DB_site->query("
				UPDATE " . TABLE_PREFIX . "post
				SET userid = 0,
				username = '" . addslashes($user['username']) . "'
				WHERE userid = $user[userid]
			");
			echo '<b>' . $vbphrase['done'] . "</b></p>\n";
			flush();
		}
		$startat += 50;

		print_cp_redirect("user.php?$session[sessionurl]do=prune_updateposts&startat=$startat", 0);
		exit;
	}
	else
	{
		echo '<p>' . $vbphrase['deleting_users'] . '</p>';
		$DB_site->query("DELETE FROM " . TABLE_PREFIX . "usertextfield WHERE userid IN($userids)");
		$DB_site->query("DELETE FROM " . TABLE_PREFIX . "userfield WHERE userid IN($userids)");
		$DB_site->query("DELETE FROM " . TABLE_PREFIX . "user WHERE userid IN($userids)");

		require_once('./includes/functions_databuild.php');
		build_user_statistics();

		define('CP_REDIRECT', "user.php?do=prune");
		print_stop_message('updated_threads_posts_successfully');
	}
}

// ############################# do prune/move users (step 1) #########################
if ($_POST['do'] == 'dopruneusers')
{
	globalize($_REQUEST, array('dowhat', 'userid', 'movegroup' => INT));
	if (is_array($userid))
	{
		$userids = array();
		foreach ($userid AS $key => $val)
		{
			$key = intval($key);
			if ($val == 1 AND $key != $bbuserinfo['userid'])
			{
				$userids[] = $key;
			}
		}

		$userids = implode(',', $userids);

		if ($dowhat == 'delete')
		{
			echo "<p>" . $vbphrase['deleting_subscriptions'] . "\n";
			flush();
			$DB_site->query("DELETE FROM " . TABLE_PREFIX . "subscribeforum WHERE userid IN($userids)");
			echo $vbphrase['okay'] . '</p><p>' . $vbphrase['deleting_subscriptions'] . "\n";
			flush();
			$DB_site->query("DELETE FROM " . TABLE_PREFIX . "subscribethread WHERE userid IN($userids)");
			echo $vbphrase['okay'] . '</p><p>' . $vbphrase['deleting_events'] . "\n";
			flush();
			$DB_site->query("DELETE FROM " . TABLE_PREFIX . "event WHERE userid IN($userids)");
			echo $vbphrase['okay'] . '</p><p>' . $vbphrase['deleting_custom_avatars'] . "\n";
			flush();
			$DB_site->query("DELETE FROM " . TABLE_PREFIX . "customavatar WHERE userid IN($userids)");

			if ($vboptions['usefileavatar'])
			{
				$customavatars = $DB_site->query("
					SELECT userid, avatarrevision
					FROM " . TABLE_PREFIX . "user WHERE
					userid IN(" . $userids . ")
				");
				while ($customavatar = $DB_site->fetch_array($customavatars))
				{
					@unlink("$vboptions[avatarpath]/avatar$customavatar[userid]_$customavatar[avatarrevision].gif");
				}
			}

			echo $vbphrase['okay'] . '</p><p>' . $vbphrase['deleting_user_forum_access'] . "\n";
			flush();
			$DB_site->query("DELETE FROM " . TABLE_PREFIX . "access WHERE userid IN($userids)");
			echo $vbphrase['okay'] . '</p><p>' . $vbphrase['deleting_moderators'] . "\n";
			flush();
			$DB_site->query("DELETE FROM " . TABLE_PREFIX . "moderator WHERE userid IN($userids)");
			echo $vbphrase['okay'] . '</p><p>' . $vbphrase['deleting_private_messages'] . "\n";
			flush();
			$DB_site->query("DELETE FROM " . TABLE_PREFIX . "pm WHERE userid IN($userids)");
			$DB_site->query("DELETE FROM " . TABLE_PREFIX . "pmreceipt WHERE userid IN($userids)");
			flush();
			$DB_site->query("DELETE FROM " . TABLE_PREFIX . "session WHERE userid IN($userids)");
			echo $vbphrase['okay'] . '</p><p>' . $vbphrase['updating_threads_posts'] . "</p>\n";
			flush();
			build_adminutil_text('ids', $userids);

			require_once('./includes/functions_databuild.php');
			build_user_statistics();

			print_cp_redirect("user.php?$session[sessionurl]do=prune_updateposts&startat=0",1);
			exit;

		}
		else if ($dowhat == 'move')
		{
			$group = $DB_site->query_first("
				SELECT title
				FROM " . TABLE_PREFIX . "usergroup
				WHERE usergroupid = $movegroup
			");
			echo '<p>' . $vbphrase['updating_users'] . "\n";
			flush();
			$DB_site->query("
				UPDATE " . TABLE_PREFIX . "user
				SET usergroupid = $movegroup
				WHERE userid IN($userids)
			");
			echo $vbphrase['okay'] . '</p><p><b>' . $vbphrase['moved_users_successfully'] . '</b></p>';
			print_cp_redirect("user.php?$session[sessionurl]do=prune", 1);
		}
		else
		{
			globalize($_REQUEST, array('usergroupid' => INT, 'daysprune' => INT, 'minposts' => INT, 'joindate', 'order'));

			define('CP_REDIRECT', "user.php?do=pruneusers&usergroupid=$usergroupid&daysprune=$daysprune&minposts=$minposts&joindate=$joindate&order=$order");
			print_stop_message('invalid_action_specified');
		}

		if (is_array($query))
		{
			foreach ($query AS $val)
			{
				echo "<pre>$val</pre>\n";
			}
		}
	}
	else
	{
		print_stop_message('please_complete_required_fields');
	}

}

// ############################# start list users for pruning #########################
if ($_REQUEST['do'] == 'pruneusers')
{
	globalize($_REQUEST, array('usergroupid' => INT, 'daysprune' => INT, 'minposts' => INT, 'joindate', 'order'));
	unset($sqlconds);

	if ($usergroupid != -1)
	{
		$sqlconds = "WHERE user.usergroupid = $usergroupid ";
	}
	if ($daysprune)
	{
		$sqlconds .= iif(empty($sqlconds), 'WHERE', 'AND') . ' lastactivity < ' . (TIMENOW - $daysprune * 86400) . ' ';
	}
	if ($joindate['month'] AND $joindate['year'])
	{
		$joindateunix = mktime(0, 0, 0, intval($joindate['month']), intval($joindate['day']), intval($joindate['year']));
		$sqlconds .= iif(empty($sqlconds), 'WHERE', 'AND') . " joindate < $joindateunix ";
	}
	if ($minposts)
	{
		$sqlconds .= iif(empty($sqlconds), 'WHERE', 'AND') . " posts < $minposts ";
	}

	switch($order)
	{
		case 'username':
			$orderby = 'ORDER BY username ASC';
			break;
		case 'email':
			$orderby = 'ORDER BY email ASC';
			break;
		case 'usergroup':
			$orderby = 'ORDER BY usergroup.title ASC';
			break;
		case 'posts':
			$orderby = 'ORDER BY posts DESC';
			break;
		case 'lastactivity':
			$orderby = 'ORDER BY lastactivity DESC';
			break;
		case 'joindate':
			$orderby = 'ORDER BY joindate DESC';
			break;
		default:
			$orderby = 'ORDER BY username ASC';
	}

	if (!empty($sqlconds))
	{

		$query = "
			SELECT DISTINCT user.userid, username, email, posts, lastactivity, joindate,
			user.usergroupid, moderator.moderatorid, usergroup.title
			FROM " . TABLE_PREFIX . "user AS user
			LEFT JOIN " . TABLE_PREFIX . "moderator AS moderator ON(moderator.userid = user.userid)
			LEFT JOIN " . TABLE_PREFIX . "usergroup AS usergroup ON(usergroup.usergroupid = user.usergroupid)
			$sqlconds
			GROUP BY user.userid $orderby
		";
		$users = $DB_site->query($query);
		if ($numusers = $DB_site->num_rows($users))
		{
			?>
			<script type="text/javascript">
			function js_alert_no_permission()
			{
				alert("<?php echo $vbphrase['you_may_not_delete_move_this_user']; ?>");
			}
			</script>
			<?php

			$groups = $DB_site->query("
				SELECT usergroupid, title
				FROM " . TABLE_PREFIX . "usergroup
				WHERE usergroupid NOT IN(1,3,4,5,6)
				ORDER BY title
			");
			$groupslist = '';
			while ($group = $DB_site->fetch_array($groups))
			{
				$groupslist .= "\t<option value=\"$group[usergroupid]\">$group[title]</option>\n";
			}

			print_form_header('user', 'dopruneusers');
			construct_hidden_code('usergroupid', $usergroupid);
			construct_hidden_code('daysprune', $daysprune);
			construct_hidden_code('minposts', $minposts);
			construct_hidden_code('joindate[day]', intval($joindate['day']));
			construct_hidden_code('joindate[month]', intval($joindate['month']));
			construct_hidden_code('joindate[year]', intval($joindate['year']));
			construct_hidden_code('order', $order);
			print_table_header(construct_phrase($vbphrase['showing_users_x_to_y_of_z'], 1, $numusers, $numusers), 7);
			print_cells_row(array(
				'Userid',
				$vbphrase['username'],
				$vbphrase['email'],
				$vbphrase['post_count'],
				$vbphrase['last_activity'],
				$vbphrase['join_date'],
				'<input type="checkbox" name="allbox" onclick="js_check_all(this.form)" title="' . $vbphrase['check_all'] . '" checked="checked">'
			), 1);
			while ($user = $DB_site->fetch_array($users))
			{
				$cell = array();
				$cell[] = $user['userid'];
				$cell[] = "<a href=\"user.php?$session[sessionurl]do=edit&userid=$user[userid]\" target=\"_blank\">$user[username]</a><br /><span class=\"smallfont\">$user[title]" . iif($user['moderatorid'], ', Moderator', '') . "</span>";
				$cell[] = "<a href=\"mailto:$user[email]\">$user[email]</a>";
				$cell[] = vb_number_format($user['posts']);
				$cell[] = vbdate($vboptions['dateformat'], $user['lastactivity']);
				$cell[] = vbdate($vboptions['dateformat'], $user['joindate']);
				if ($user['userid'] == $bbuserinfo['userid'] OR $user['usergroupid'] == 6 OR $user['usergroupid'] == 5 OR $user['moderatorid'])
				{
					$cell[] = '<input type="button" class="button" value=" ! " onclick="js_alert_no_permission()" />';
				}
				else
				{
					$cell[] = "<input type=\"checkbox\" name=\"userid[$user[userid]]\" value=\"1\" checked=\"checked\" tabindex=\"1\" />";
				}
				print_cells_row($cell);
			}
			print_description_row('<center><span class="smallfont">
				<b>' . $vbphrase['action'] . ':
				<label for="dw_delete"><input type="radio" name="dowhat" value="delete" id="dw_delete" tabindex="1" />' . $vbphrase['delete'] . '</label>
				<label for="dw_move"><input type="radio" name="dowhat" value="move" id="dw_move" tabindex="1" />' . $vbphrase['move'] . '</label>
				<select name="movegroup" tabindex="1" class="bginput">' . $groupslist . '</select></b>
				</span></center>', 0, 7);
			print_submit_row($vbphrase['go'], $vbphrase['check_all'], 7);

			echo '<p>' . $vbphrase['this_action_is_not_reversible'] . '</p>';
		}
		else
		{
			define('CP_REDIRECT', "user.php?do=prune&usergroupid=$usergroupid&daysprune=$daysprune&joindateunix=$joindateunix&minposts=$minposts");
			print_stop_message('no_users_matched_your_query');
		}
	}
	else
	{
		print_stop_message('please_complete_required_fields');
	}
}


// ############################# start prune users #########################
if ($_REQUEST['do'] == 'prune')
{
	globalize($_REQUEST, array('usergroupid' => INT, 'daysprune', 'joindateunix' => INT, 'minposts' => INT));

	print_form_header('user', 'pruneusers');
	print_table_header($vbphrase['user_moving_pruning_system']);
	print_description_row('<blockquote>' . $vbphrase['this_system_allows_you_to_mass_move_delete_users'] . '</blockquote>');
	print_chooser_row($vbphrase['usergroup'], 'usergroupid', 'usergroup', iif($usergroupid, $usergroupid, -1), $vbphrase['all_usergroups']);
	print_input_row($vbphrase['has_not_logged_on_for_xx_days'], 'daysprune', iif($daysprune, $daysprune, 365));
	print_time_row($vbphrase['join_date_is_before'], 'joindate', $joindateunix, 0, 1, 'middle');
	print_input_row($vbphrase['posts_is_less_than'], 'minposts', iif($minposts, $minposts, '0'));
	print_label_row($vbphrase['order_by'], '<select name="order" tabindex="1" class="bginput">
		<option value="username">' . $vbphrase['username'] . '</option>
		<option value="email">' . $vbphrase['email'] . '</option>
		<option value="usergroup">' . $vbphrase['usergroup'] . '</option>
		<option value="posts">' . $vbphrase['post_count'] . '</option>
		<option value="lastactivity">' . $vbphrase['last_activity'] . '</option>
		<option value="joindate">' . $vbphrase['join_date'] . '</option>
	</select>', '', 'top', 'order');
	print_submit_row($vbphrase['find']);

}

print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 11:11, Sat Feb 19th 2005
|| # CVS: $RCSfile: user.php,v $ - $Revision: 1.256.2.8 $
|| ####################################################################
\*======================================================================*/
?>