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

error_reporting(E_ALL & ~E_NOTICE);

if (VB_AREA !== 'Upgrade' AND VB_AREA !== 'Install')
{
	exit;
}

if ($_POST['do'] == 'login')
{

	// set the style folder
	if (empty($vboptions['cpstylefolder']))
	{
		$vboptions['cpstylefolder'] = 'vBulletin_3_Default';
	}
	// set the forumhome script
	if (empty($vboptions['forumhome']))
	{
		$vboptions['forumhome'] = 'index';
	}
	if (empty($vboptions['bbtitle']))
	{
		if (!empty($bbtitle))
		{
			$vboptions['bbtitle'] = $bbtitle;
		}
		else
		{
			$vboptions['bbtitle'] = $authenticate_phrases['new_installation'];
		}
	}
	// set the version
	$vboptions['templateversion'] = VERSION;

	define('NO_PAGE_TITLE', true);
	print_cp_header($pagetitle);

	?>
	<form action="<?php echo THIS_SCRIPT; ?>" method="post">
	<input type="hidden" name="redirect" value="<?php echo htmlspecialchars_uni($scriptpath); ?>" />
	<input type="hidden" name="do" value="login" />
	<p>&nbsp;</p><p>&nbsp;</p>
	<table class="tborder" cellpadding="0" cellspacing="0" border="0" width="450" align="center"><tr><td>

		<!-- header -->
		<div class="tcat" style="padding:4px; text-align:center"><b><?php echo $authenticate_phrases['enter_cust_num']; ?></b></div>
		<!-- /header -->

		<!-- logo and version -->
		<table cellpadding="4" cellspacing="0" border="0" width="100%" class="navbody">
		<tr valign="bottom">
			<td><img src="../cpstyles/<?php echo $vboptions['cpstylefolder']; ?>/cp_logo.gif" alt="" border="0" /></td>
			<td>
				<b><a href="../<?php echo $vboptions['forumhome']; ?>.php"><?php echo $vboptions['bbtitle']; ?></a></b><br />
				<?php echo "vBulletin $vboptions[templateversion] $pagetitle"; ?><br />
				&nbsp;
			</td>
		</tr>
		</table>
		<!-- /logo and version -->

		<table cellpadding="4" cellspacing="0" border="0" width="100%" class="logincontrols">
		<col width="50%" style="text-align:right; white-space:nowrap"></col>
		<col></col>
		<col width="50%"></col>
		<!-- login fields -->
		<tr valign="top">
			<td>&nbsp;<br /><?php echo $authenticate_phrases['customer_number']; ?><br />&nbsp;</td>
			<td class="smallfont"><input type="text" style="padding-left:5px; font-weight:bold; width:250px" name="customerid" value="" tabindex="1" /><br /><?php echo $authenticate_phrases['cust_num_explanation']; ?></td>
			<td>&nbsp;</td>
		</tr>
		<!-- /login fields -->
		<!-- submit row -->
		<tr>
			<td colspan="3" align="center">
				<input type="submit" class="button" value="<?php echo $authenticate_phrases['enter_system']; ?>" accesskey="s" tabindex="3" />
			</td>
		</tr>
		<!-- /submit row -->
		</table>
	</td></tr></table>
	</form>
	<?php

	unset($debug, $GLOBALS['DEVDEBUG']);
	define('NO_CP_COPYRIGHT', true);
	print_cp_footer();
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 11:11, Sat Feb 19th 2005
|| # CVS: $RCSfile: authenticate.php,v $ - $Revision: 1.20.2.1 $
|| ####################################################################
\*======================================================================*/
?>