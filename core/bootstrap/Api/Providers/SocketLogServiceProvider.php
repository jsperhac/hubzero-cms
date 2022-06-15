<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright (c) 2005-2020 The Regents of the University of California.
 * @license    http://opensource.org/licenses/MIT MIT
 */

namespace Bootstrap\Api\Providers;

use Hubzero\Log\Manager;
use Hubzero\Base\ServiceProvider;

/**
 * Event service provider
 */
class SocketLogServiceProvider extends ServiceProvider
{
	/**
	 * Register the service provider.
	 *
	 * @return  void
	 */
	public function register()
	{
		// JMS
		$this->app['socket-log'] = function($app)
		{
			$path = $app['config']->get('log_path');
			// JMS
			if (is_dir('/dev/log'))
			{
				// JMS
				$path = '/dev/log';
			}

			$dispatcher = null;
			if ($app->has('dispatcher'))
			{
				$dispatcher = $app['dispatcher'];
			}

			$manager = new Manager($path);

			$manager->register('debug', array(
				'file'       => 'cmsdebug.log',
				'dispatcher' => $dispatcher
			));

			$manager->register('auth', array(
				'file'       => 'cmsauth.sock',
				'level'      => 'info',
				'format'     => "%datetime% [api] %message%\n",
				'dispatcher' => $dispatcher
			));

			$manager->register('spam', array(
				'file'       => 'cmsspam.log',
				'dispatcher' => $dispatcher
			));

			return $manager;
		};
	}
}
