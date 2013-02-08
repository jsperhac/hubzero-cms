<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2011 Purdue University. All rights reserved.
 *
 * This file is part of: The HUBzero(R) Platform for Scientific Collaboration
 *
 * The HUBzero(R) Platform for Scientific Collaboration (HUBzero) is free
 * software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software
 * Foundation, either version 3 of the License, or (at your option) any
 * later version.
 *
 * HUBzero is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * HUBzero is a registered trademark of Purdue University.
 *
 * @package   hubzero-cms
 * @author    Shawn Rice <zooley@purdue.edu>
 * @copyright Copyright 2005-2011 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

$database = JFactory::getDBO();
$this->juser = JFactory::getUser();

$base = 'index.php?option=' . $this->option . '&id=' . $this->member->get('uidNumber') . '&active=' . $this->name;
?>

<form method="get" action="<?php echo JRoute::_($base . '&task=posts'); ?>" id="collections">

	<fieldset class="filters">
		<ul>
<?php if ($this->params->get('access-manage-collection')) { ?>
			<li>
				<a class="livefeed tooltips" href="<?php echo JRoute::_($base); ?>" title="<?php echo JText::_('Live feed :: View posts from everything you\'re following'); ?>">
					<span><?php echo JText::_('Feed'); ?></span>
				</a>
			</li>
<?php } ?>
			<li>
				<a class="collections count" href="<?php echo JRoute::_($base . '&task=all'); ?>">
					<span><?php echo JText::sprintf('<strong>%s</strong> collections', $this->collections); ?></span>
				</a>
			</li>
			<li>
				<a class="posts active count" href="<?php echo JRoute::_($base . '&task=posts'); ?>">
					<span><?php echo JText::sprintf('<strong>%s</strong> posts', $this->rows->total()); ?></span>
				</a>
			</li>
			<li>
				<a class="followers count" href="<?php echo JRoute::_($base . '&task=followers'); ?>">
					<span><?php echo JText::sprintf('<strong>%s</strong> followers', $this->followers); ?></span>
				</a>
			</li>
			<li>
				<a class="following count" href="<?php echo JRoute::_($base . '&task=following'); ?>">
					<span><?php echo JText::sprintf('<strong>%s</strong> following', $this->following); ?></span>
				</a>
			</li>
		</ul>
		<?php if ($this->params->get('access-create-collection')) { ?>
		<p>
			<a class="add btn tooltips" title="<?php echo JText::_('New post :: Add a new post to this collection'); ?>" href="<?php echo JRoute::_($base . '&task=post/new'); ?>">
				<?php echo JText::_('New post'); ?>
			</a>
		</p>
		<?php } ?>
		<div class="clear"></div>
	</fieldset>

	<div id="posts">
<?php 
if ($this->rows->total() > 0) 
{
	ximport('Hubzero_User_Profile');
	ximport('Hubzero_User_Profile_Helper');

	foreach ($this->rows as $row)
	{
		$item = $row->item();

		if ($item->get('state') == 2)
		{
			$item->set('type', 'deleted');
		}
		$type = $item->get('type');
		if (!in_array($type, array('collection', 'deleted', 'image', 'file', 'text', 'link')))
		{
			$type = 'link';
		}
?>
		<div class="post <?php echo $type; ?>" id="b<?php echo $row->get('id'); ?>" data-id="<?php echo $row->get('id'); ?>" data-closeup-url="<?php echo JRoute::_($base . '&task=post/' . $row->get('id')); ?>" data-width="600" data-height="350">
			<div class="content">
			<?php
				$view = new Hubzero_Plugin_View(
					array(
						'folder'  => 'members',
						'element' => $this->name,
						'name'    => 'post',
						'layout'  => 'default_' . $type
					)
				);
				$view->name       = $this->name;
				$view->option     = $this->option;
				$view->member     = $this->member;
				$view->params     = $this->params;
				$view->dateFormat = $this->dateFormat;
				$view->timeFormat = $this->timeFormat;
				$view->tz         = $this->tz;
				$view->row        = $row;
				$view->board      = $this->collection;
				$view->display();
			?>
			<?php if (count($item->tags()) > 0) { ?>
				<div class="tags-wrap">
					<?php echo $item->tags('render'); ?>
				</div>
			<?php } ?>
				<div class="meta">
					<p class="stats">
						<span class="likes">
							<?php echo JText::sprintf('%s likes', $item->get('positive', 0)); ?>
						</span>
						<span class="comments">
							<?php echo JText::sprintf('%s comments', $item->get('comments', 0)); ?>
						</span>
						<span class="reposts">
							<?php echo JText::sprintf('%s reposts', $item->get('reposts', 0)); ?>
						</span>
					</p>
			<?php if (!$this->juser->get('guest')) { ?>
					<div class="actions">
				<?php if ($item->get('created_by') == $this->juser->get('id')) { ?>
						<a class="edit" data-id="<?php echo $row->get('id'); ?>" href="<?php echo JRoute::_($base . '&task=post/' . $row->get('id') . '/edit'); ?>">
							<span><?php echo JText::_('Edit'); ?></span>
						</a>
				<?php } else { ?>
						<a class="vote <?php echo ($item->get('voted')) ? 'unlike' : 'like'; ?>" data-id="<?php echo $row->get('id'); ?>" data-text-like="<?php echo JText::_('Like'); ?>" data-text-unlike="<?php echo JText::_('Unlike'); ?>" href="<?php echo JRoute::_($base . '&task=post/' . $row->get('id') . '/vote'); ?>">
							<span><?php echo ($item->get('voted')) ? JText::_('Unlike') : JText::_('Like'); ?></span>
						</a>
				<?php } ?>
						<a class="comment" data-id="<?php echo $row->get('id'); ?>" href="<?php echo JRoute::_($base . '&task=post/' . $row->get('id') . '/comment'); ?>">
							<span><?php echo JText::_('Comment'); ?></span>
						</a>
						<a class="repost" data-id="<?php echo $row->get('id'); ?>" href="<?php echo JRoute::_($base . '&task=post/' . $row->get('id') . '/collect'); ?>">
							<span><?php echo JText::_('Collect'); ?></span>
						</a>
				<?php if ($row->get('original') && ($item->get('created_by') == $this->juser->get('id') || $this->params->get('access-delete-item'))) { ?>
						<a class="delete" data-id="<?php echo $row->get('id'); ?>" href="<?php echo JRoute::_($base . '&task=post/' . $row->get('id') . '/delete'); ?>">
							<span><?php echo JText::_('Delete'); ?></span>
						</a>
				<?php } else if ($row->get('created_by') == $this->juser->get('id') || $this->params->get('access-edit-item')) { ?>
						<a class="unpost" data-id="<?php echo $row->get('id'); ?>" href="<?php echo JRoute::_($base . '&task=post/' . $row->get('id') . '/remove'); ?>">
							<span><?php echo JText::_('Remove'); ?></span>
						</a>
				<?php } ?>
					</div><!-- / .actions -->
			<?php } ?>
				</div><!-- / .meta -->

			<?php if ($row->original() || $item->get('created_by') != $this->member->get('uidNumber')) { ?>
				<div class="convo attribution clearfix">
					<a href="<?php echo JRoute::_('index.php?option=com_members&id=' . $item->get('created_by')); ?>" title="<?php echo $this->escape(stripslashes($item->creator()->get('name'))); ?>" class="img-link">
						<img src="<?php echo Hubzero_User_Profile_Helper::getMemberPhoto($item->creator(), 0); ?>" alt="Profile picture of <?php echo $this->escape(stripslashes($item->creator()->get('name'))); ?>" />
					</a>
					<p>
						<a href="<?php echo JRoute::_('index.php?option=com_members&id=' . $item->get('created_by')); ?>">
							<?php echo $this->escape(stripslashes($item->creator()->get('name'))); ?>
						</a> 
						posted 
						<br />
						<span class="entry-date">
							<span class="entry-date-at">@</span> <span class="date"><time datetime="<?php echo $item->get('created'); ?>"><?php echo JHTML::_('date', $item->get('created'), $this->timeFormat, $this->tz); ?></time></span> 
							<span class="entry-date-on">on</span> <span class="time"><time datetime="<?php echo $item->get('created'); ?>"><?php echo JHTML::_('date', $item->get('created'), $this->dateFormat, $this->tz); ?></time></span>
						</span>
					</p>
				</div><!-- / .attribution -->
			<?php } ?>
			<?php if (!$row->original()) {
				//if ($item->get('created_by') != $this->member->get('uidNumber')) {
					$collection = CollectionsModelCollection::getInstance($row->get('collection_id'));
					switch ($collection->get('object_type'))
					{
						case 'group':
							$href = 'index.php?option=com_groups&gid=' . $collection->get('object_id') . '&active=collections&scope=' . $collection->get('alias');
						break;

						case 'member':
						default:
							$href = 'index.php?option=com_members&id=' . $collection->get('object_id') . '&active=collections&task=' . $collection->get('alias');
						break;
					}
				?>
				<div class="convo attribution reposted clearfix">
					<a href="<?php echo JRoute::_('index.php?option=com_members&id=' . $row->get('created_by')); ?>" title="<?php echo $this->escape(stripslashes($row->creator()->get('name'))); ?>" class="img-link">
						<img src="<?php echo Hubzero_User_Profile_Helper::getMemberPhoto($row->creator(), 0); ?>" alt="Profile picture of <?php echo $this->escape(stripslashes($row->creator()->get('name'))); ?>" />
					</a>
					<p>
						<a href="<?php echo JRoute::_('index.php?option=com_members&id=' . $row->get('created_by')); ?>">
							<?php echo $this->escape(stripslashes($row->creator()->get('name'))); ?>
						</a> 
						onto 
						<a href="<?php echo JRoute::_($href); ?>">
							<?php echo $this->escape(stripslashes($collection->get('title'))); ?>
						</a>
						<br />
						<span class="entry-date">
							<span class="entry-date-at">@</span> <span class="date"><time datetime="<?php echo $row->get('created'); ?>"><?php echo JHTML::_('date', $row->get('created'), $this->timeFormat, $this->tz); ?></time></span> 
							<span class="entry-date-on">on</span> <span class="time"><time datetime="<?php echo $row->get('created'); ?>"><?php echo JHTML::_('date', $row->get('created'), $this->dateFormat, $this->tz); ?></time></span>
						</span>
					</p>
				</div><!-- / .attribution -->
			<?php } ?>
			</div><!-- / .content -->
		</div><!-- / .post -->
<?php
	}
}
else
{
?>
		<div id="collection-introduction">
	<?php if ($this->params->get('access-create-item')) { ?>
			<div class="instructions">
				<ol>
					<li><?php echo JText::_('Find images, files, links or text you want to share.'); ?></li>
					<li><?php echo JText::_('Click on "New post" button.'); ?></li>
					<li><?php echo JText::_('Add anything extra you want (tags are nice).'); ?></li>
					<li><?php echo JText::_('Done!'); ?></li>
				</ol>
			</div><!-- / .instructions -->
			<!-- <div class="questions">
				<p><strong>What is the "Collect" button for?</strong></p>
				<p>This is how you can add other content on the site to a collection. You can collect wiki pages, resources, and more. You can even collect other collections!<p>
			</div><!- / .post-type -->
	<?php } else { ?>
			<div class="instructions">
				<p><?php echo JText::_('No posts available.'); ?></p>
			</div><!-- / .instructions -->
	<?php } ?>
		</div><!-- / #collection-introduction -->
<?php
}
?>
	</div><!-- / #posts -->
</form>