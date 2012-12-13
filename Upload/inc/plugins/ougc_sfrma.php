<?php

/***************************************************************************
 *
 *   OUGC Show Forum Rules plugin
 *	 Author: Omar Gonzalez
 *   Copyright: © 2012 Omar Gonzalez
 *   
 *   Website: http://www.udezain.com.ar
 *
 *   Show forum rules in the thread and edit post pages.
 *
 ***************************************************************************/
 
/****************************************************************************
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/

// Die if IN_MYBB is not defined, for security reasons.
defined('IN_MYBB') or die('Direct initialization of this file is not allowed.');

// Tell MyBB when to run the hook
if(!defined('IN_ADMINCP') && defined('THIS_SCRIPT'))
{
	global $plugins;

	$templcache = false;
	switch(THIS_SCRIPT)
	{
		case 'showthread.php':
			$templcache = true;
			$plugins->add_hook('showthread_end', 'ougc_sfrma');
		case 'editpost.php':
			$templcache = true;
			$plugins->add_hook('editpost_end', 'ougc_sfrma');
			break;
	}

	if($templcache)
	{
		global $templatelist;

		if(isset($templatelist))
		{
			$templatelist .= ',';
		}
		$templatelist .= 'forumdisplay_rules,forumdisplay_rules_link';
	}
}

// Plugin API
function ougc_sfrma_info()
{
	return array(
		'name'			=> 'OUGC Show Forum Rules',
		'description'	=> 'Show forum rules in the thread and edit post pages.',
		'website'		=> 'http://udezain.com.ar/',
		'author'		=> 'Omar Gonzalez',
		'authorsite'	=> 'http://udezain.com.ar/',
		'version'		=> '1.0',
		'guid' 			=> '5419c02974929364bd98f6389fe0fb94',
		'compatibility' => '16*'
	);
}

// Activate plugin
function ougc_sfrma_activate()
{
	ougc_sfrma_deactivate();

	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets('showthread', '#'.preg_quote('{$pollbox}').'#', '{$rules}{$pollbox}');
	find_replace_templatesets('editpost', '#'.preg_quote('{$attachbox}').'#', '{$attachbox}{$rules}');
}

// Deactivate plugin
function ougc_sfrma_deactivate()
{
	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets('showthread', '#'.preg_quote('{$rules}').'#', '', 0);
	find_replace_templatesets('editpost', '#'.preg_quote('{$rules}').'#', '', 0);
}

// Our plugin hook
function ougc_sfrma()
{
	// If there is no quick reply (showthread.php), then there is no need for forum rules
	if(THIS_SCRIPT == 'showthread.php')
	{
		global $quickreply;

		if(!trim($quickreply))
		{
			return;
		}
	}

	global $forum;

	$foruminfo = &$forum;

	// No rules or rules type, stop
	$foruminfo['rulestype'] = (int)$foruminfo['rulestype'];
	if(!(in_array($foruminfo['rulestype'], array(1, 2, 3)) && trim($foruminfo['rules'])))
	{
		return;
	}

	global $rules, $lang, $templates, $theme;
	isset($lang->forum_rules) or $lang->load('newreply');
	$foruminfo['rulestitle'] or ($foruminfo['rulestitle'] = $lang->sprintf($lang->forum_rules, $foruminfo['name']));

	$fid = (int)$foruminfo['fid'];
	switch($foruminfo['rulestype'])
	{
		case 1:
		case 3:
			global $parser;
		
			if(!(is_object($parser) && $parser instanceof postParser))
			{
				require_once MYBB_ROOT.'inc/class_parser.php';
				$parser = new postParser;
			}

			$parser_options = array(
				'allow_html'	=>	1,
				'allow_mycode'	=>	1,
				'allow_smilies'	=>	1,
				'allow_imgcode'	=>	1
			);

			$foruminfo['rules'] = $parser->parse_message($foruminfo['rules'], $parser_options);

			eval('$rules = "'.$templates->get('forumdisplay_rules').'";');
			break;
		case 2:
			eval('$rules = "'.$templates->get('forumdisplay_rules_link').'";');
			break;
	}
}