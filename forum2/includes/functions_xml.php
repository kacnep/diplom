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

// ###################### Start xml_escapeCDATA #######################
// prevents nested CDATA tags in XML files
function xml_escape_cdata($xhtml)
{
	// strip invalid characters in XML 1.0:  00-08, 11-12 and 14-31
	// I did not find any character sets which use these characters.
	$xhtml = preg_replace('#[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]#', '', $xhtml);

	$xhtml = str_replace('<![CDATA[', '�![CDATA[', $xhtml);
	$xhtml = str_replace(']]>', ']]�', $xhtml);
	return $xhtml;
}

// ###################### Start xml_unescapeCDATA #######################
// reverses the function above
function xml_unescape_cdata($xhtml)
{
	static $find, $replace;

	if (!is_array($find))
	{
		$find = array('�![CDATA[', ']]�', "\r\n", "\n");
		$replace = array('<![CDATA[', ']]>', "\n", "\r\n");
	}

	return str_replace($find, $replace, $xhtml);
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 11:11, Sat Feb 19th 2005
|| # CVS: $RCSfile: functions_xml.php,v $ - $Revision: 1.2.2.1 $
|| ####################################################################
\*======================================================================*/
?>