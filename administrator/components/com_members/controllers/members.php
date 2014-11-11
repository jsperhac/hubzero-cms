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

include_once(JPATH_ROOT . DS . 'components' . DS . 'com_members' . DS . 'models' . DS . 'registration.php');

/**
 * Manage site members
 */
class MembersControllerMembers extends \Hubzero\Component\AdminController
{
	/**
	 * Display a list of site members
	 *
	 * @return     void
	 */
	public function displayTask()
	{
		// Get configuration
		$config = JFactory::getConfig();
		$app = JFactory::getApplication();

		// Get filters
		$this->view->filters = array();
		$this->view->filters['search']       = urldecode($app->getUserStateFromRequest(
			$this->_option . '.' . $this->_controller . '.search',
			'search',
			''
		));
		$this->view->filters['search_field'] = urldecode($app->getUserStateFromRequest(
			$this->_option . '.' . $this->_controller . '.search_field',
			'search_field',
			'name'
		));
		$this->view->filters['sort']         = trim($app->getUserStateFromRequest(
			$this->_option . '.' . $this->_controller . '.sort',
			'filter_order',
			'registerDate'
		));
		$this->view->filters['sort_Dir']     = trim($app->getUserStateFromRequest(
			$this->_option . '.' . $this->_controller . '.sortdir',
			'filter_order_Dir',
			'DESC'
		));
		$this->view->filters['registerDate']         = trim($app->getUserStateFromRequest(
			$this->_option . '.' . $this->_controller . '.registerDate',
			'registerDate',
			''
		));
		$this->view->filters['emailConfirmed']         = trim($app->getUserStateFromRequest(
			$this->_option . '.' . $this->_controller . '.emailConfirmed',
			'emailConfirmed',
			0,
			'int'
		));
		$this->view->filters['public']         = trim($app->getUserStateFromRequest(
			$this->_option . '.' . $this->_controller . '.public',
			'public',
			-1,
			'int'
		));

		//$this->view->filters['show']         = '';
		//$this->view->filters['scope']        = '';
		//$this->view->filters['authorized']   = true;

		$this->view->filters['sortby']       = $this->view->filters['sort'].' '.$this->view->filters['sort_Dir'];

		// Get paging variables
		$this->view->filters['limit']        = $app->getUserStateFromRequest(
			$this->_option . '.' . $this->_controller . '.limit',
			'limit',
			$config->getValue('config.list_limit'),
			'int'
		);
		$this->view->filters['start']        = $app->getUserStateFromRequest(
			$this->_option . '.' . $this->_controller . '.limitstart',
			'limitstart',
			0,
			'int'
		);
		// In case limit has been changed, adjust limitstart accordingly
		$this->view->filters['start'] = ($this->view->filters['limit'] != 0 ? (floor($this->view->filters['start'] / $this->view->filters['limit']) * $this->view->filters['limit']) : 0);

		$obj = new MembersProfile($this->database);

		// Get a record count
		$this->view->total = $obj->getRecordCount($this->view->filters, true);

		// Get records
		$this->view->rows = $obj->getRecordEntries($this->view->filters, true);

		// Initiate paging
		jimport('joomla.html.pagination');
		$this->view->pageNav = new JPagination(
			$this->view->total,
			$this->view->filters['start'],
			$this->view->filters['limit']
		);

		$this->view->config = $this->config;

		// Set any errors
		if ($this->getError())
		{
			foreach ($this->getErrors() as $error)
			{
				$this->view->setError($error);
			}
		}

		// Output the HTML
		$this->view->display();
	}

	/**
	 * Create a new member
	 *
	 * @return     void
	 */
	public function addTask()
	{
		JRequest::setVar('hidemainmenu', 1);

		// Set any errors
		if ($this->getError())
		{
			foreach ($this->getErrors() as $error)
			{
				$this->view->setError($error);
			}
		}

		// Output the HTML
		$this->view->display();
	}

	/**
	 * Create a new user
	 *
	 * @param      integer $redirect Redirect to main listing?
	 * @return     void
	 */
	public function newTask($redirect=1)
	{
		// Check for request forgeries
		JRequest::checkToken() or jexit('Invalid Token');

		// Incoming profile edits
		$p = JRequest::getVar('profile', array(), 'post', 'none', 2);

		// Initialize new usertype setting
		$usersConfig = JComponentHelper::getParams('com_users');
		$newUsertype = $usersConfig->get('new_usertype');
		if (!$newUsertype)
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true)
				->select('id')
				->from('#__usergroups')
				->where('title = "Registered"');
			$db->setQuery($query);
			$newUsertype = $db->loadResult();
		}

		$name  = trim($p['givenName']).' ';
		$name .= (trim($p['middleName']) != '') ? trim($p['middleName']).' ' : '';
		$name .= trim($p['surname']);

		$date = JFactory::getDate();
		$user = JUser::getInstance();
		$user->set('username', trim($p['username']));
		$user->set('name', $name);
		$user->set('email', trim($p['email']));
		$user->set('id', 0);
		$user->set('groups', array($newUsertype));
		$user->set('registerDate', $date->toMySQL());
		$user->set('password', trim($p['password']));
		$user->set('password_clear', trim($p['password']));
		$user->save();
		$user->set('password_clear', '');

		// Attempt to get the new user
		$profile = \Hubzero\User\Profile::getInstance($user->get('id'));
		$result  = is_object($profile);

		// Did we successfully create an account?
		if ($result)
		{
			// Set the new info
			$profile->set('givenName', trim($p['givenName']));
			$profile->set('middleName', trim($p['middleName']));
			$profile->set('surname', trim($p['surname']));
			$profile->set('name', $name);
			$profile->set('emailConfirmed', -rand(1, pow(2, 31)-1));
			$profile->set('public', 0);
			$profile->set('password', '');
			$result = $profile->store();
		}

		if ($result)
		{
			$result = \Hubzero\User\Password::changePassword($profile->get('uidNumber'), $p['password']);
			// Set password back here in case anything else down the line is looking for it
			$profile->set('password', $p['password']);
			$profile->store();
		}

		// Did we successfully create/update an account?
		if (!$result)
		{
			$this->setRedirect(
				'index.php?option=' . $this->_option . '&controller=' . $this->_controller,
				$user->getError(),
				'error'
			);
			return;
		}

		// Redirect
		$this->setRedirect(
			JRoute::_('index.php?option='.$this->_option.'&controller='.$this->_controller.'&task=edit&id[]='.$profile->get('uidNumber'), false),
			JText::_('COM_MEMBERS_MEMBER_SAVED')
		);
	}

	/**
	 * Edit a member's information
	 *
	 * @param      integer $id ID of member to edit
	 * @return     void
	 */
	public function editTask($id=0)
	{
		JRequest::setVar('hidemainmenu', 1);

		$this->view->setLayout('edit');

		if (!$id)
		{
			// Incoming
			$id = JRequest::getVar('id', array());

			// Get the single ID we're working with
			if (is_array($id))
			{
				$id = (!empty($id)) ? $id[0] : 0;
			}
		}

		// Initiate database class and load info
		$this->view->profile = new \Hubzero\User\Profile();
		$this->view->profile->load($id);

		$this->view->password = \Hubzero\User\Password::getInstance($id);

		// Get password rules
		$password_rules = \Hubzero\Password\Rule::getRules();

		// Get the password rule descriptions
		$this->view->password_rules = array();
		foreach ($password_rules as $rule)
		{
			if (!empty($rule['description']))
			{
				$this->view->password_rules[] = $rule['description'];
			}
		}

		// Validate the password
		$this->view->validated = (isset($this->validated)) ? $this->validated : false;

		// Get the user's interests (tags)
		include_once(JPATH_ROOT . DS . 'components' . DS . $this->_option . DS . 'models' . DS . 'tags.php');

		$mt = new MembersModelTags($id);
		$this->view->tags = $mt->render('string');

		// Set any errors
		if ($this->getError())
		{
			foreach ($this->getErrors() as $error)
			{
				$this->view->setError($error);
			}
		}

		// Output the HTML
		$this->view->display();
	}

	/**
	 * Save an entry and return to edit form
	 *
	 * @return     void
	 */
	public function applyTask()
	{
		$this->saveTask(0);
	}

	/**
	 * Save an entry and return to main listing
	 *
	 * @param      integer $redirect Redirect to main listing?
	 * @return     void
	 */
	public function saveTask($redirect=1)
	{
		// Check for request forgeries
		JRequest::checkToken() or jexit('Invalid Token');

		// Incoming user ID
		$id = JRequest::getInt('id', 0, 'post');

		// Do we have an ID?
		if (!$id)
		{
			JError::raiseError(500, JText::_('COM_MEMBERS_NO_ID'));
			return;
		}

		// Incoming profile edits
		$p = JRequest::getVar('profile', array(), 'post', 'none', 2);

		// Load the profile
		$profile = new \Hubzero\User\Profile();
		$profile->load($id);

		// Set the new info
		$profile->set('givenName', trim($p['givenName']));
		$profile->set('middleName', trim($p['middleName']));
		$profile->set('surname', trim($p['surname']));

		$name  = trim($p['givenName']).' ';
		$name .= (trim($p['middleName']) != '') ? trim($p['middleName']).' ' : '';
		$name .= trim($p['surname']);

		$profile->set('name', $name);
		if (isset($p['vip']))
		{
			$profile->set('vip',$p['vip']);
		}
		else
		{
			$profile->set('vip',0);
		}
		$profile->set('orcid', trim($p['orcid']));
		$profile->set('url', trim($p['url']));
		$profile->set('phone', trim($p['phone']));
		$profile->set('orgtype', trim($p['orgtype']));
		$profile->set('organization', trim($p['organization']));
		$profile->set('bio', trim($p['bio']));
		if (isset($p['public']))
		{
			$profile->set('public',$p['public']);
		}
		else
		{
			$profile->set('public',0);
		}
		$profile->set('modifiedDate', JFactory::getDate()->toSql());

		$profile->set('jobsAllowed', intval(trim($p['jobsAllowed'])));

		$profile->set('homeDirectory', trim($p['homeDirectory']));

		$profile->set('loginShell', trim($p['loginShell']));

		$ec = JRequest::getInt('emailConfirmed', 0, 'post');
		if ($ec)
		{
			$profile->set('emailConfirmed', 1);
		}
		else
		{
			$confirm = MembersHelperUtility::genemailconfirm();
			$profile->set('emailConfirmed', $confirm);
		}

		if (isset($p['email']))
		{
			$profile->set('email', trim($p['email']));
		}
		if (isset($p['mailPreferenceOption']))
		{
			$profile->set('mailPreferenceOption', trim($p['mailPreferenceOption']));
		}
		else
		{
			$profile->set('mailPreferenceOption', -1);
		}

		if (!empty($p['gender']))
		{
			$profile->set('gender', trim($p['gender']));
		}

		if (!empty($p['disability']))
		{
			if ($p['disability'] == 'yes')
			{
				if (!is_array($p['disabilities']))
				{
					$p['disabilities'] = array();
				}
				if (count($p['disabilities']) == 1
				 && isset($p['disabilities']['other'])
				 && empty($p['disabilities']['other']))
				{
					$profile->set('disability',array('no'));
				}
				else
				{
					$profile->set('disability',$p['disabilities']);
				}
			}
			else
			{
				$profile->set('disability',array($p['disability']));
			}
		}

		if (!empty($p['hispanic']))
		{
			if ($p['hispanic'] == 'yes')
			{
				if (!is_array($p['hispanics']))
				{
					$p['hispanics'] = array();
				}
				if (count($p['hispanics']) == 1
				 && isset($p['hispanics']['other'])
				 && empty($p['hispanics']['other']))
				{
					$profile->set('hispanic', array('no'));
				}
				else
				{
					$profile->set('hispanic',$p['hispanics']);
				}
			}
			else
			{
				$profile->set('hispanic',array($p['hispanic']));
			}
		}

		if (isset($p['race']) && is_array($p['race']))
		{
			$profile->set('race',$p['race']);
		}

		// Save the changes
		if (!$profile->update())
		{
			JError::raiseWarning('', $profile->getError());
			return false;
		}

		// Do we have a new pass?
		$newpass = trim(JRequest::getVar('newpass', '', 'post'));
		if ($newpass != '')
		{
			// Get password rules and validate
			$password_rules = \Hubzero\Password\Rule::getRules();
			$validated      = \Hubzero\Password\Rule::validate($newpass, $password_rules, $profile->get('uidNumber'));

			if (!empty($validated))
			{
				// Set error
				$this->setError(JText::_('COM_MEMBERS_PASSWORD_DOES_NOT_MEET_REQUIREMENTS'));
				$this->validated = $validated;
				$redirect = false;
			}
			else
			{
				// Save password
				\Hubzero\User\Password::changePassword($profile->get('username'), $newpass);
			}
		}

		$passinfo = \Hubzero\User\Password::getInstance($id);

		if (is_object($passinfo))
		{
			// Do we have shadow info to change?
			$shadowMax     = JRequest::getInt('shadowMax', false, 'post');
			$shadowWarning = JRequest::getInt('shadowWarning', false, 'post');
			$shadowExpire  = JRequest::getVar('shadowExpire', '', 'post');

			if ($shadowMax || $shadowWarning || (!is_null($passinfo->get('shadowExpire')) && empty($shadowExpire)))
			{
				if ($shadowMax)
				{
					$passinfo->set('shadowMax', $shadowMax);
				}
				if ($shadowExpire || (!is_null($passinfo->get('shadowExpire')) && empty($shadowExpire)))
				{
					if (preg_match("/[0-9]{4}-[0-9]{2}-[0-9]{2}/", $shadowExpire))
					{
						$shadowExpire = strtotime($shadowExpire) / 86400;
						$passinfo->set('shadowExpire', $shadowExpire);
					}
					elseif (preg_match("/[0-9]+/", $shadowExpire))
					{
						$passinfo->set('shadowExpire', $shadowExpire);
					}
					elseif (empty($shadowExpire))
					{
						$passinfo->set('shadowExpire', NULL);
					}
				}
				if ($shadowWarning)
				{
					$passinfo->set('shadowWarning', $shadowWarning);
				}

				$passinfo->update();
			}
		}

		// Get the user's interests (tags)
		$tags = trim(JRequest::getVar('tags', ''));

		// Process tags
		include_once(JPATH_ROOT . DS . 'components' . DS . $this->_option . DS . 'models' . DS . 'tags.php');

		$mt = new MembersModelTags($id);
		$mt->setTags($tags, $id);

		// Make sure certain changes make it back to the Joomla user table
		$juser = JUser::getInstance($id);
		$juser->set('name', $name);
		$juser->set('email', $profile->get('email'));
		if (!$juser->save())
		{
			JError::raiseWarning('', JText::_($juser->getError()));
			return false;
		}

		if ($redirect)
		{
			// Redirect
			$this->setRedirect(
				JRoute::_('index.php?option='.$this->_option),
				JText::_('MEMBER_SAVED')
			);
		}
		else
		{
			$this->editTask($id);
		}
	}

	/**
	 * Removes a profile entry, associated picture, and redirects to main listing
	 *
	 * @return     void
	 */
	public function removeTask()
	{
		// Check for request forgeries
		JRequest::checkToken() or jexit('Invalid Token');

		// Incoming
		$ids = JRequest::getVar('ids', array());

		// Do we have any IDs?
		if (!empty($ids))
		{
			// Loop through each ID and delete the necessary items
			foreach ($ids as $id)
			{
				$id = intval($id);

				// Delete any associated pictures
				$path = JPATH_ROOT . DS . trim($this->config->get('webpath', '/site/members'), DS) . DS . \Hubzero\Utility\String::pad($id);
				if (!file_exists($path . DS . $file) or !$file)
				{
					$this->setError(JText::_('COM_MEMBERS_FILE_NOT_FOUND'));
				}
				else
				{
					unlink($path . DS . $file);
				}

				// Remove any contribution associations
				$assoc = new MembersAssociation($this->database);
				$assoc->authorid = $id;
				$assoc->deleteAssociations();

				// Remove the profile
				$profile = new \Hubzero\User\Profile();
				$profile->load($id);
				$profile->delete();
			}
		}

		// Output messsage and redirect
		$this->setRedirect(
			'index.php?option=' . $this->_option . '&controller=' . $this->_controller,
			JText::_('COM_MEMBERS_REMOVED')
		);
	}

	/**
	 * Set a member's emailConfirmed to confirmed
	 *
	 * @return     void
	 */
	public function confirmTask()
	{
		$this->stateTask(1);
	}

	/**
	 * Set a member's emailConfirmed to unconfirmed
	 *
	 * @return     void
	 */
	public function unconfirmTask()
	{
		$this->stateTask(0);
	}

	/**
	 * Sets the emailConfirmed state of a member
	 *
	 * @return     void
	 */
	public function stateTask($state=1)
	{
		// Check for request forgeries
		JRequest::checkToken('get') or JRequest::checkToken() or jexit('Invalid Token');

		// Incoming user ID
		$ids = JRequest::getVar('id', array());
		$ids = (!is_array($ids) ? array($ids) : $ids);

		// Do we have an ID?
		if (empty($ids))
		{
			$this->setRedirect(
				'index.php?option=' . $this->_option . '&controller=' . $this->_controller,
				JText::_('COM_MEMBERS_NO_ID'),
				'error'
			);
			return;
		}

		foreach ($ids as $id)
		{
			// Load the profile
			$profile = new \Hubzero\User\Profile();
			$profile->load(intval($id));

			if ($state)
			{
				$profile->set('emailConfirmed', $state);
			}
			else
			{
				$confirm = MembersHelperUtility::genemailconfirm();
				$profile->set('emailConfirmed', $confirm);
			}

			if (!$profile->update())
			{
				$this->setRedirect(
					'index.php?option=' . $this->_option . '&controller=' . $this->_controller,
					$profile->getError(),
					'error'
				);
				return;
			}
		}

		$this->setRedirect(
			'index.php?option=' . $this->_option . '&controller=' . $this->_controller,
			JText::_('COM_MEMBERS_CONFIRMATION_CHANGED')
		);
	}

	/**
	 * Cancel a task (redirects to default task)
	 *
	 * @return     void
	 */
	public function cancelTask()
	{
		$this->setRedirect(
			'index.php?option=' . $this->_option . '&controller=' . $this->_controller
		);
	}

	/**
	 * Return results for autocompleter
	 *
	 * @return     string JSON
	 */
	public function autocompleteTask()
	{
		if ($this->juser->get('guest'))
		{
			return;
		}

		$restrict = '';

		$filters = array();
		$filters['limit']  = 20;
		$filters['start']  = 0;
		$filters['search'] = strtolower(trim(JRequest::getString('value', '')));

		// Fetch results
		$query = "SELECT xp.uidNumber, xp.name, xp.username, xp.organization, xp.picture, xp.public
				FROM #__xprofiles AS xp
				INNER JOIN #__users u ON u.id = xp.uidNumber AND u.block = 0
				WHERE LOWER(xp.name) LIKE " . $this->database->quote('%' . $filters['search'] . '%') . " AND xp.emailConfirmed>0 $restrict
				ORDER BY xp.name ASC";

		$this->database->setQuery($query);
		$rows = $this->database->loadObjectList();

		$base = str_replace('/administrator', '', rtrim(JURI::getInstance()->base(true), '/'));

		// Output search results in JSON format
		$json = array();
		if (count($rows) > 0)
		{
			$default = DS . trim($this->config->get('defaultpic', '/components/com_members/images/profile.gif'), DS);
			$default = \Hubzero\User\Profile\Helper::thumbit($default);
			foreach ($rows as $row)
			{
				$picture = $default;

				$name = str_replace("\n", '', stripslashes(trim($row->name)));
				$name = str_replace("\r", '', $name);
				$name = str_replace('\\', '', $name);

				if ($row->public && $row->picture)
				{
					$thumb  = $base . DS . trim($this->config->get('webpath', '/site/members'), DS);
					$thumb .= DS . \Hubzero\User\Profile\Helper::niceidformat($row->uidNumber);
					$thumb .= DS . ltrim($row->picture, DS);
					$thumb = \Hubzero\User\Profile\Helper::thumbit($thumb);

					if (file_exists(JPATH_ROOT . $thumb))
					{
						$picture = $thumb;
					}
				}

				$obj = array();
				$obj['id']      = $row->uidNumber;
				$obj['name']    = $name;
				$obj['org']     = ($row->public ? $row->organization : '');
				$obj['picture'] = $picture;

				$json[] = $obj;
			}
		}

		echo json_encode($json);
	}

	/**
	 * Download a picture
	 *
	 * @return  void
	 */
	public function pictureTask()
	{
		//get vars
		$id = JRequest::getInt('id', 0);

		//check to make sure we have an id
		if (!$id || $id == 0)
		{
			return;
		}

		//Load member profile
		$member = \Hubzero\User\Profile::getInstance($id);

		// check to make sure we have member profile
		if (!$member)
		{
			return;
		}

		$file  = DS . trim($this->config->get('webpath', '/site/members'), DS);
		$file .= DS . \Hubzero\User\Profile\Helper::niceidformat($member->get('uidNumber'));
		$file .= DS . JRequest::getVar('image', $member->get('picture'));

		// Ensure the file exist
		if (!file_exists(JPATH_ROOT . DS . $file))
		{
			JError::raiseError(404, JText::_('COM_MEMBERS_FILE_NOT_FOUND') . ' ' . $file);
			return;
		}

		// Serve up the image
		$xserver = new \Hubzero\Content\Server();
		$xserver->filename(JPATH_ROOT . DS . $file);
		$xserver->disposition('attachment');
		$xserver->acceptranges(false); // @TODO fix byte range support

		//serve up file
		if (!$xserver->serve())
		{
			// Should only get here on error
			JError::raiseError(404, JText::_('COM_MEMBERS_MEDIA_ERROR_SERVING_FILE'));
		}
		else
		{
			exit;
		}
		return;
	}
}

