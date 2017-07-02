<?php
/**
 * Nextcloud - ownnote
 *
 * @copyright Copyright (c) 2015, Ben Curtis <ownclouddev@nosolutions.com>
 * @copyright Copyright (c) 2017, Sander Brand (brantje@gmail.com)
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\OwnNote\Controller;

use OC\User\Manager;
use \OCP\AppFramework\ApiController;
use \OCP\AppFramework\Http\JSONResponse;
use \OCP\AppFramework\Http\Response;
use \OCP\AppFramework\Http;
use OCP\IConfig;
use OCP\ILogger;
use \OCP\IRequest;
use \OCA\OwnNote\Lib\Backend;


class OwnnoteAjaxController extends ApiController {

	private $backend;
	private $config;

	public function __construct($appName, IRequest $request,ILogger $logger, IConfig $config) {
		parent::__construct($appName, $request);
		$this->config = $config;
		$this->backend = new Backend($config);
	}

	/**
	 * AJAX FUNCTIONS
	 */

	/**
	 * @NoAdminRequired
	 */
	public function ajaxindex() {
		$FOLDER = $this->config->getAppValue('ownnote', 'folder', '');
		return $this->backend->getListing($FOLDER, false);
	}


	/**
	 * @NoAdminRequired
	 */
	public function ajaxcreate($name, $group) {
		$FOLDER = $this->config->getAppValue('ownnote', 'folder', '');
		if (isset($name) && isset($group))
			return $this->backend->createNote($FOLDER, $name, $group);
	}

	/**
	 * @NoAdminRequired
	 */
	public function ajaxdel($nid) {
		$FOLDER = $this->config->getAppValue('ownnote', 'folder', '');
		if (isset($nid)) {
			return $this->backend->deleteNote($FOLDER, $nid);
		}
	}

	/**
	 * @NoAdminRequired
	 */
	public function ajaxedit($nid) {
		if (isset($nid)) {
			return $this->backend->editNote($nid);
		}
	}

	/**
	 * @NoAdminRequired
	 */
	public function ajaxsave($id, $content) {
		$FOLDER = $this->config->getAppValue('ownnote', 'folder', '');
		if (isset($id) && isset($content)) {
			return $this->backend->saveNote($FOLDER, $id, $content, 0);
		}
	}

	/**
	 * @NoAdminRequired
	 */
	public function ajaxren($id, $newname, $newgroup) {
		$FOLDER = $this->config->getAppValue('ownnote', 'folder', '');
		if (isset($id) && isset($newname) && isset($newgroup))
			return $this->backend->renameNote($FOLDER, $id, $newname, $newgroup);
	}

	/**
	 * @NoAdminRequired
	 */
	public function ajaxdelgroup($group) {
		$FOLDER = $this->config->getAppValue('ownnote', 'folder', '');
		if (isset($group))
			return $this->backend->deleteGroup($FOLDER, $group);
	}

	/**
	 * @NoAdminRequired
	 */
	public function ajaxrengroup($group, $newgroup) {
		$FOLDER = $this->config->getAppValue('ownnote', 'folder', '');
		if (isset($group) && isset($newgroup))
			return $this->backend->renameGroup($FOLDER, $group, $newgroup);
	}

	/**
	 * @NoAdminRequired
	 */
	public function ajaxversion() {
		return $this->backend->getVersion();
	}

	/**
	 */
	public function ajaxsetval($field, $value) {
		return $this->backend->setAdminVal($field, $value);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function ajaxgetsharemode() {
		return $this->config->getAppValue('ownnote', 'sharemode', '');
	}
}
