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
use OCP\IConfig;
use OCP\ILogger;
use \OCP\IRequest;
use \OCA\OwnNote\Lib\Backend;


class OwnnoteApiController extends ApiController {

	private $backend;
	private $config;

	public function __construct($appName, IRequest $request, ILogger $logger, IConfig $config){
		parent::__construct($appName, $request);
		$this->backend = new Backend($config);
		$this->config = $config;
	}

	/**
	* MOBILE FUNCTIONS
	*/

	/**
	* @NoAdminRequired
	* @NoCSRFRequired
	*/
	public function index() {
		$FOLDER = $this->config->getAppValue('ownnote', 'folder', '');
		return $this->backend->getListing($FOLDER, false);
	}

	/**
	* @NoAdminRequired
	* @NoCSRFRequired
	*/
	public function mobileindex() {
		$FOLDER = $this->config->getAppValue('ownnote', 'folder', '');
		return $this->backend->getListing($FOLDER, true);
	}

	/**
	* @NoAdminRequired
	* @NoCSRFRequired
	*/
	public function remoteindex() {
		$FOLDER = $this->config->getAppValue('ownnote', 'folder', '');
		return json_encode($this->backend->getListing($FOLDER, true));
	}

	/**
	* @NoAdminRequired
	* @NoCSRFRequired
	*/
	public function create($name, $group) {
		$FOLDER = $this->config->getAppValue('ownnote', 'folder', '');
		if (isset($name) && isset($group))
			return $this->backend->createNote($FOLDER, $name, $group);
	}

	/**
	* @NoAdminRequired
	* @NoCSRFRequired
	*/
	public function del($nid) {
		$FOLDER = $this->config->getAppValue('ownnote', 'folder', '');
		if (isset($nid))
			return $this->backend->deleteNote($FOLDER, $nid);
	}

	/**
	* @NoAdminRequired
	* @NoCSRFRequired
	*/
	public function edit($id) {
		if (isset($id)) {
			return $this->backend->editNote($id);
		}
	}

	/**
	* @NoAdminRequired
	* @NoCSRFRequired
	*/
	public function save($id, $content) {
		$FOLDER = $this->config->getAppValue('ownnote', 'folder', '');
		if (isset($id) && isset($content))
			return $this->backend->saveNote($FOLDER, $id, $content, 0);
	}

	/**
	* @NoAdminRequired
	* @NoCSRFRequired
	*/
	public function ren($id, $newname, $newgroup) {
		$FOLDER = $this->config->getAppValue('ownnote', 'folder', '');
		if (isset($id) && isset($newname) && isset($newgroup))
			return $this->backend->renameNote($FOLDER, $id, $newname, $newgroup);
	}

	/**
	* @NoAdminRequired
	* @NoCSRFRequired
	*/
	public function delgroup($group) {
		$FOLDER = $this->config->getAppValue('ownnote', 'folder', '');
		if (isset($group))
			return $this->backend->deleteGroup($FOLDER, $group);
	}

	/**
	* @NoAdminRequired
	* @NoCSRFRequired
	*/
	public function rengroup($group, $newgroup) {
		$FOLDER = $this->config->getAppValue('ownnote', 'folder', '');
		if (isset($group) && isset($newgroup))
			return $this->backend->renameGroup($FOLDER, $group, $newgroup);
	}

	/**
	* @NoAdminRequired
	* @NoCSRFRequired
	*/
	public function version() {
		return $this->backend->getVersion();
	}
}
