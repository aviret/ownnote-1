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
use OCA\OwnNote\Db\OwnNote;
use OCA\OwnNote\Service\OwnNoteGroupService;
use OCA\OwnNote\Service\OwnNoteService;
use \OCP\AppFramework\ApiController;
use \OCP\AppFramework\Http\JSONResponse;
use \OCP\AppFramework\Http\Response;
use \OCP\AppFramework\Http;
use OCP\IConfig;
use OCP\ILogger;
use \OCP\IRequest;
use \OCA\OwnNote\Lib\Backend;
use \OCP\App;


class OwnnoteAjaxController extends ApiController {

	private $backend;
	private $config;
	private $noteService;
	private $uid;
	private $noteGroupService;

	public function __construct($appName, IRequest $request, ILogger $logger, IConfig $config, OwnNoteService $noteService, OwnNoteGroupService $groupService) {
		parent::__construct($appName, $request);
		$this->config = $config;
		$this->noteService = $noteService;
		$this->backend = new Backend($config);
		$this->noteGroupService = $groupService;
		$this->uid = \OC::$server->getUserSession()->getUser()->getUID();
	}

	/**
	 * AJAX FUNCTIONS
	 */

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function ajaxindex() {
		$FOLDER = $this->config->getAppValue($this->appName, 'folder', '');
		return $this->noteService->getListing($FOLDER, false);
	}


	/**
	 * @NoAdminRequired
	 */
	public function ajaxcreate($name, $group) {
		$FOLDER = $this->config->getAppValue($this->appName, 'folder', '');
		if (isset($name) && isset($group)) {
			$note = [
				'name' => $name,
				'group' => $group
			];
			return $this->noteService->create($FOLDER, $note, $this->uid);
		}
	}

	/**
	 * @NoAdminRequired
	 */
	public function ajaxdel($nid) {
		$FOLDER = $this->config->getAppValue($this->appName, 'folder', '');
		if (isset($nid)) {
			return $this->noteService->delete($FOLDER, $nid);
		}
	}

	/**
	 * @NoAdminRequired
	 */
	public function ajaxedit($nid) {
		if (isset($nid)) {
			/**
			 * @param OwnNote $note
			 */
			$note = $this->noteService->find($nid);
			if($note) {
				return $note->getNote();
			}
			return '';
		}
	}

	/**
	 * @NoAdminRequired
	 */
	public function ajaxsave($id, $content) {
		$FOLDER = $this->config->getAppValue($this->appName, 'folder', '');
		if (isset($id) && isset($content)) {
			$note = [
				'id' => $id,
				'note' => $content
			];

			return ($this->noteService->update($FOLDER, $note));
		}
	}

	/**
	 * @NoAdminRequired
	 */
	public function ajaxren($id, $newname, $newgroup) {
		$FOLDER = $this->config->getAppValue($this->appName, 'folder', '');
		if (isset($id) && isset($newname) && isset($newgroup))
			return $this->noteService->renameNote($FOLDER, $id, $newname, $newgroup, $this->uid);
	}

	/**
	 * @NoAdminRequired
	 */
	public function ajaxdelgroup($group) {
		$FOLDER = $this->config->getAppValue($this->appName, 'folder', '');
		if (isset($group))
			return $this->noteGroupService->deleteGroup($FOLDER, $group);
	}

	/**
	 * @NoAdminRequired
	 */
	public function ajaxrengroup($group, $newgroup) {
		$FOLDER = $this->config->getAppValue($this->appName, 'folder', '');
		if (isset($group) && isset($newgroup))
			return $this->noteGroupService->renameGroup($FOLDER, $group, $newgroup);
	}

	/**
	 * @NoAdminRequired
	 */
	public function ajaxversion() {
		$AppInstance = new App();
		return $AppInstance->getAppInfo($this->appName)["version"];
	}

	/**
	 */
	public function ajaxsetval($field, $value) {
		$this->config->setAppValue($this->appName, $field, $value);
		return true;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function ajaxgetsharemode() {
		return $this->config->getAppValue($this->appName, 'sharemode', '');
	}
}
