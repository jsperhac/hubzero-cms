<?php
/**
 * @package		HUBzero CMS
 * @author		Shawn Rice <zooley@purdue.edu>
 * @copyright	Copyright 2005-2009 by Purdue Research Foundation, West Lafayette, IN 47906
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 *
 * Copyright 2005-2009 by Purdue Research Foundation, West Lafayette, IN 47906.
 * All rights reserved.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License,
 * version 2 as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

if (!$this->parser) {
	ximport('wiki.parser');

	$this->parser = new WikiParser( stripslashes($this->question->subject), $this->option, 'answer', $this->question->id, 0, '' );
}

// Set the name of the reviewer
$name = JText::_('COM_ANSWERS_ANONYMOUS');
$ruser = new Hubzero_User_Profile();
$ruser->load( $this->reply->added_by );
if ($this->reply->anonymous != 1) {
	$name = JText::_('COM_ANSWERS_UNKNOWN');
	//$ruser =& JUser::getInstance($this->reply->added_by);
	if (is_object($ruser)) {
		$name = $ruser->get('name');
	}
}
?>
<p class="comment-member-photo">
	<span class="comment-anchor"><a name="c<?php echo $this->reply->id; ?>"></a></span>
	<img src="<?php echo AnswersHelperMember::getMemberPhoto($ruser, $this->reply->anonymous); ?>" alt="" />
</p>
<div class="comment-content">
	<p class="comment-title">
		<strong><?php echo $name; ?></strong> 
		<a class="permalink" href="<?php echo JRoute::_('index.php?option='.$this->option.'&task=question&id='.$this->question->id.'#c'.$this->reply->id); ?>" title="<?php echo JText::_('COM_ANSWERS_PERMALINK'); ?>">@ <span class="time"><?php echo JHTML::_('date',$this->reply->added, '%I:%M %p', 0); ?></span> on <span class="date"><?php echo JHTML::_('date',$this->reply->added, '%d %b, %Y', 0); ?></span></a>
	</p>
<?php if ($this->abuse && $this->reply->reports > 0) { ?>
	<p class="warning"><?php echo JText::_('COM_ANSWERS_NOTICE_POSTING_REPORTED'); ?></p>
<?php } else { ?>
	<?php if ($this->reply->comment) { ?>
		<p><?php echo $this->parser->parse( "\n".stripslashes($this->reply->comment) ); ?></p>
	<?php } else { ?>
		<p><?php echo JText::_('COM_ANSWERS_NO_COMMENT'); ?></p>
	<?php } ?>

	<p class="comment-options">
<?php if ($this->abuse) { ?>
		<a class="abuse" href="<?php echo JRoute::_('index.php?option=com_support&task=reportabuse&category=comment&id='.$this->reply->id.'&parent='.$this->id); ?>"><?php echo JText::_('COM_ANSWERS_REPORT_ABUSE'); ?></a>
<?php } ?>
<?php 
	// Cannot reply at third level
	if ($this->level < 3) {
		echo '<a class="showreplyform" href="'.JRoute::_('index.php?option='.$this->option.'&task=reply&category=answercomment&id='.$this->id.'&refid='.$this->reply->id.'#c'.$this->reply->id).'" id="rep_'.$this->reply->id.'">'.JText::_('COM_ANSWERS_REPLY').'</a>';
	}
?>
	</p>
<?php 
	// Add the reply form if needed
	if ($this->level < 3 && !$this->juser->get('guest')) {
		$view = new JView( array('name'=>'question', 'layout'=>'addcomment') );
		$view->option = $this->option;
		$view->row = $this->reply;
		$view->juser = $this->juser;
		$view->level = $this->level;
		$view->question = $this->question;
		$view->addcomment = $this->addcomment;
		$view->display();
	}
}
?>
</div>