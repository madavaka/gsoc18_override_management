<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_config
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Config\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Component\Templates\Administrator\Controller\Style;
use Joomla\Component\Templates\Administrator\Controller\StyleController;
use Joomla\CMS\Client\ClientHelper;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

/**
 * Component Controller
 *
 * @since  1.5
 */
class TemplatesController extends BaseController
{
	/**
	 * Constructor.
	 *
	 * @param   array                $config   An optional associative array of configuration settings.
	 * @param   MVCFactoryInterface  $factory  The factory.
	 * @param   CMSApplication       $app      The JApplication for the dispatcher
	 * @param   \JInput              $input    Input
	 *
	 * @since  1.6
	 * @see    \JControllerLegacy
	 */
	public function __construct($config = array(), MVCFactoryInterface $factory = null, $app = null, $input = null)
	{
		parent::__construct($config, $factory, $app, $input);

		// Apply, Save & New, and Save As copy should be standard on forms.
		$this->registerTask('apply', 'save');
	}

	/**
	 * Method to handle cancel
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   3.2
	 */
	public function cancel()
	{
		// Redirect back to home(base) page
		$this->setRedirect(Uri::base());
	}

	/**
	 * Method to save global configuration.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   3.2
	 */
	public function save()
	{
		// Check for request forgeries.
		if (!Session::checkToken())
		{
			$this->setRedirect('index.php', Text::_('JINVALID_TOKEN'));

			return false;
		}

		// Check if the user is authorized to do this.
		if (!Factory::getUser()->authorise('core.admin'))
		{
			$this->setRedirect('index.php', Text::_('JERROR_ALERTNOAUTHOR'));

			return false;
		}

		// Set FTP credentials, if given.
		ClientHelper::setCredentialsFromRequest('ftp');

		$app = $this->app;

		// Access backend com_templates
		$controllerClass = $app->bootComponent('com_templates')->createMVCFactory($app)->createController('Style', 'Administrator');

		// Get a document object
		$document = $app->getDocument();

		// Set backend required params
		$document->setType('json');
		$this->input->set('id', $app->getTemplate(true)->id);

		// Execute backend controller
		$return = $controllerClass->save();

		// Reset params back after requesting from service
		$document->setType('html');

		// Check the return value.
		if ($return === false)
		{
			// Save the data in the session.
			// TODO Which data?! How did that work?
			$app->setUserState('com_config.config.global.data', $data);

			// Save failed, go back to the screen and display a notice.
			$message = Text::sprintf('JERROR_SAVE_FAILED');

			$app->redirect(Route::_('index.php?option=com_config&view=templates', false), $message, 'error');

			return false;
		}

		// Set the success message.
		$message = Text::_('COM_CONFIG_SAVE_SUCCESS');

		$this->setMessage($message);

		// Redirect back to com_config display
		$this->redirect(Route::_('index.php?option=com_config&view=templates', false));

		return true;
	}
}
