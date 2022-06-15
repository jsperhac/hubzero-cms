<?php
/**
 * @package    framework
 * @copyright  Copyright (c) 2005-2020 The Regents of the University of California.
 * @license    http://opensource.org/licenses/MIT MIT
 */

namespace Hubzero\Session\Storage;

use Hubzero\Session\Store;
use Exception;
use Event;

/**
 * Database session storage handler
 *
 * Inspired by Joomla's JSessionStorageDatabase class
 */
class Database extends Store
{
	/**
	 * Profiler for debugging
	 *
	 * @var  object
	 */
	private $profiler = null;

	/**
	 * Skip session writes?
	 *
	 * @var  bool
	 */
	private $skipWrites = false;

	/**
	 * Constructor
	 *
	 * @param   array  $options  Optional parameters.
	 * @return  void
	 */
	public function __construct($options = array())
	{
		if (!isset($options['database']) || !($options['database'] instanceof \JDatabase))
		{
			$options['database'] = \App::get('db');
		}

		$this->connection = $options['database'];

		if (isset($options['profiler']))
		{
			$this->profiler = $options['profiler'];
		}

		if (isset($options['skipWrites']))
		{
			$this->skipWrites = (bool)$options['skipWrites'];
		}

		parent::__construct($options);
	}

	/**
	 * Read the data for a particular session identifier from the SessionHandler backend.
	 *
	 * @param   string  $id  The session identifier.
	 * @return  mixed   The session data on success, False on failure.
	 */
	public function read($session_id)
	{
		// Get the database connection object and verify its connected.
		if (!$this->connection->connected())
		{
			return false;
		}

		try
		{
			// Get the session data from the database table.
			$query = $this->connection->getQuery()
				->select('data')
				->from('#__session')
				->whereEquals('session_id', $session_id);

			$this->connection->setQuery($query->toString());

			return (string) $this->connection->loadResult();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Write session data to the SessionHandler backend.
	 *
	 * @param   string   $session_id    The session identifier.
	 * @param   string   $session_data  The session data.
	 * @return  boolean  True on success, false otherwise.
	 */
	public function write($session_id, $session_data)
	{
		// Skip session write on API and command line calls
		if ($this->skipWrites)
		{
			if ($this->profiler)
			{
				$this->profiler->mark('sessionStore');
			}
			return true;
		}

		// Get the database connection object and verify its connected.
		if ($this->connection->connected())
		{
			try
			{
				$query = $this->connection->getQuery()
					->update('#__session')
					->set(array(
						'data' => $session_data,
						'time' => (int) time(),
						'ip'   => $_SERVER['REMOTE_ADDR']
					))
					->whereEquals('session_id', $session_id);

				// Try to update the session data in the database table.
				$this->connection->setQuery($query->toString());

				if ($this->connection->execute())
				{
					if ($this->profiler)
					{
						$this->profiler->mark('sessionStore');
					}
					return true;
				}

				// Since $db->execute did not throw an exception, so the query was successful.
				// Either the data changed, or the data was identical.
				// In either case we are done.
			}
			catch (Exception $e)
			{
			}
		}

		if ($this->profiler)
		{
			$this->profiler->mark('sessionStore');
		}
		return false;
	}

	/**
	 * Destroy the data for a particular session identifier in the SessionHandler backend.
	 * This is called, for example, when a user logs out of the site.
	 *
	 * @param   string   $id  The session identifier.
	 * @return  boolean  True on success, false otherwise.
	 */
	public function destroy($session_id)
	{
		// Get the database connection object and verify its connected.
		if (!$this->connection->connected())
		{
			return false;
		}

		try
		{
			// JMS experiment only:
			// JMS get the session object with the username, put in an array
			// this works to log the session:
			/*
			$session_info = $this->session($session_id);
			$user = array('username' => $session_info->username,
				  'userid'   => $session_info->userid,
				  'time'   => $session_info->time);
		    */

			$query = $this->connection->getQuery()
				->delete('#__session')
				->whereEquals('session_id', $session_id);

			// Remove a session from the database.
			$this->connection->setQuery($query->toString());

			// JMS experiment only:
			// JMS trigger the timeout event in the user plugin:
			// this works to log the session:
			//\Event::trigger('user.onUserSessionTimeout', array($user));

			// JMS experiment only:
			// JMS: now try to invoke gc() from here, for testing:
			//$this->gc(10);
			//\Event::trigger('user.onUserSessionGCCalled', array(999));

			return (boolean) $this->connection->execute();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Garbage collect stale sessions from the SessionHandler backend.
	 *
	 * @param   integer  $lifetime  The maximum age of a session.
	 * @return  boolean  True on success, false otherwise.
	 */
	public function gc($lifetime = 1440)
	{
		// Get the database connection object and verify its connected.
		if (!$this->connection->connected())
		{
			return false;
		}

		// Determine the timestamp threshold with which to purge old sessions.
		$past = time() - $lifetime;
		//dump($past);

		// JMS test
		// test the brakes: JMS
		\Event::trigger('user.onUserSessionGCCalled', array($past));
		//$testarr = array($past);
		//ddie($testarr[0]);

		try
		{
			// JMS: first, query for the array of expired sessions:
			// could be improved by getting list of sessions once, note that delete below
			// re-runs the time query
			$session_info = $this->sessionTimeouts($past);
			//dump($session_info);

			// JMS: place entry in log for each user (non guest) session that has expired:
			//$NOT_GUEST = 0;
			//$expiring[];
			foreach ($session_info as $session)
			{
				$user = [];
				$user = array('username' => $session->username,
				  'userid'   => $session->userid,
				  'time'   => $session->time);

				  //ddie($user);
				  \Event::trigger('user.onUserSessionTimeout', array($user));
			}

			// prepare to delete these sessions from the database:
			$query = $this->connection->getQuery()
				->delete('#__session')
				->where('time', '<', (int) $past);

			// Remove expired sessions from the database.
			$this->connection->setQuery($query->toString());

			return (boolean) $this->connection->execute();
		}
			catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Get list of all user sessions that have timed out
	 *
	 * @param   integer  $threshold 	unix time when session timed out
	 * @return  array
	 */
	public function sessionTimeouts($threshold)
	{
		$query = $this->connection->getQuery()
			->select('userid')
			->select('username')
			->select('time')
			->from('#__session')
			->whereEquals('guest', 0)
			->where('time', '<', (int) $threshold);

			/*->select('session_id')
			->select('userid')
			->select('username')
			->select('time')
			->select('guest')
			->from('#__session')
			->whereEquals('guest', 0)
			->where('time', '<', (int) $threshold);
			*/
		$this->connection->setQuery($query->toString());
		return $this->connection->loadObjectList();
	}

	/**
	 * Get single session data as an object
	 *
	 * @param   integer  $session_id  Session Id
	 * @return  object
	 */
	public function session($session_id)
	{
		$query = $this->connection->getQuery()
			->select('*')
			->from('#__session')
			->group('userid')
			->group('client_id')
			->order('time', 'desc');

		$this->connection->setQuery($query->toString());
		return $this->connection->loadObject();
	}

	/**
	 * Get list of all sessions
	 *
	 * @param   array  $filters
	 * @return  array
	 */
	public function all($filters = array())
	{
		$query = $this->connection->getQuery()
			->select('session_id')
			->select('client_id')
			->select('guest')
			->select('time')
			->select('data')
			->select('userid')
			->select('username')
			->select('ip')
			->from('#__session');

		$max = '';
		if (isset($filters['distinct']) && $filters['distinct'] == 1)
		{
			$query->select('MAX(time)', 'time');
		}

		if (isset($filters['guest']))
		{
			$query->whereEquals('guest', $filters['guest']);
		}

		if (isset($filters['client']))
		{
			if (!is_array($filters['client']))
			{
				$filters['client'] = array($filters['client']);
			}

			$query->whereIn('client_id', $filters['client']);
		}

		if (isset($filters['distinct']) && $filters['distinct'] == 1)
		{
			$query
				->group('session_id')
				->group('userid')
				->group('client_id');
		}

		$query->order('time', 'desc');

		$this->connection->setQuery($query->toString());
		return $this->connection->loadObjectList();
	}
}
