<?php
/**
 * Nextcloud - passman
 *
 * @copyright Copyright (c) 2016, Sander Brand (brantje@gmail.com)
 * @copyright Copyright (c) 2016, Marcos Zuriaga Miguel (wolfi@wolfi.es)
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

namespace OCA\OwnNote\Service;

use OCA\OwnNote\Db\OwnNote;
use OCA\OwnNote\Utility\Evernote;
use OCA\OwnNote\Utility\Utils;
use DateTime;
use OC\Files\Filesystem;
use OCA\OwnNote\Db\OwnNoteMapper;
use Punic\Exception;


class OwnNoteService {

	private $noteMapper;
	private $utils;

	public function __construct(OwnNoteMapper $noteMapper, Utils $utils) {
		$this->noteMapper = $noteMapper;
		$this->utils = $utils;
	}

	/**
	 * Get vaults from a user.
	 *
	 * @param $userId
	 * @return OwnNote[]
	 */
	public function findNotesFromUser($userId) {
		// Get shares
		return $this->noteMapper->findNotesFromUser($userId);
	}

	/**
	 * Get a single vault
	 *
	 * @param $note_id
	 * @param $user_id
	 * @return OwnNote
	 * @internal param $vault_id
	 */
	public function find($note_id, $user_id = null) {
		$note = $this->noteMapper->find($note_id, $user_id);
		return $note;
	}

	/**
	 * Creates a note
	 *
	 * @param array|OwnNote $note
	 * @param $userId
	 * @return OwnNote
	 * @throws \Exception
	 */
	public function create($FOLDER, $note, $userId) {
		if (is_array($note)) {
			$entity = new OwnNote();
			$entity->setName($note['name']);
			$entity->setUid($userId);
			$entity->setGrouping($note['group']);
			$entity->setNote($note['note'] ? $note['note'] : '');
			if ($note['mtime']) {
				$entity->setMtime($note['mtime']);
			} else {
				$entity->setMtime(time());
			}
			$note = $entity;
		}
		if (!$note instanceof OwnNote) {
			throw new \Exception("Expected OwnNote object!");
		}

		$group = $note->getGrouping();
		$name = $note->getName();
		$content = $note->getNote();

		if ($FOLDER != '' && $name) {
			$tmpfile = $FOLDER . "/" . $name . ".htm";
			if ($group != '')
				$tmpfile = $FOLDER . "/[" . $group . "] " . $name . ".htm";
			Filesystem::file_put_contents($tmpfile, $content);
			if ($info = Filesystem::getFileInfo($tmpfile)) {
				$note->setMtime($info['mtime']);
			}
		}

		return $this->noteMapper->create($note);
	}

	/**
	 * Update vault
	 *
	 * @param $FOLDER
	 * @param $note
	 * @return OwnNote|bool
	 * @throws \Exception
	 * @internal param $userId
	 * @internal param $vault
	 */
	public function update($FOLDER, $note) {

		if (is_array($note)) {
			$entity = $this->find($note['id']);
			if(!$entity){
				$entity = new OwnNote();
			}
			if (isset($note['name'])) {
				$entity->setName($note['name']);
			}
			if(isset($note['group']) || $note['group']) {
				$entity->setGrouping($note['group']);
			}

			if (isset($note['note']) || $note['note'] == '') {
				$entity->setNote($note['note']);
			}
			if (isset($note['mtime'])) {
				$entity->setMtime($note['mtime']);
			}
			$note = $entity;
		}
		if (!$note instanceof OwnNote) {
			throw new \Exception("Expected OwnNote object!");
		}
		$group = $note->getGrouping();
		$name = $note->getName();
		$content = $note->getNote();
//		if (!$this->checkPermissions(\OCP\Constants::PERMISSION_UPDATE, $note->getId())) {
//			return false;
//		}
		if ($FOLDER != '' && $name) {
			$tmpfile = $FOLDER . "/" . $name . ".htm";
			if ($group != '')
				$tmpfile = $FOLDER . "/[" . $group . "] " . $name . ".htm";
			Filesystem::file_put_contents($tmpfile, $content);
			if ($info = Filesystem::getFileInfo($tmpfile)) {
				$note->setMtime($info['mtime']);
			}
		}
		if(!$note->getId()){
			return $this->noteMapper->create($note);
		}
		return $this->noteMapper->updateNote($note);
	}

	public function renameNote($FOLDER, $id, $in_newname, $in_newgroup, $uid = null) {
		$newname = str_replace("\\", "-", str_replace("/", "-", $in_newname));
		$newgroup = str_replace("\\", "-", str_replace("/", "-", $in_newgroup));

		// We actually need to delete and create so that the delete flag exists for syncing clients
		$note = $this->find($id);
		$arr = $note->jsonSerialize();
		if($note->getName() != $newname || $note->getGrouping() != $newgroup) {
			$arr['name'] = $newname;
			$arr['group'] = $newgroup;
			$this->delete($FOLDER, $note->getId());
		}
		$this->update($FOLDER, $arr);

		return true;
	}

	/**
	 * Delete a vault from user
	 *
	 * @param $note_id
	 * @param string $user_id
	 * @return bool
	 * @internal param string $vault_guid
	 */
	public function delete($FOLDER, $note_id, $user_id = null) {
		if (!$this->checkPermissions(\OCP\Constants::PERMISSION_DELETE, $note_id)) {
			return false;
		}

		$note = $this->noteMapper->find($note_id, $user_id);
		if ($note instanceof OwnNote) {
			$group = $note->getGrouping();
			$name = $note->getName();
			if ($FOLDER != '') {
				$tmpfile = $FOLDER . "/" . $name . ".htm";
				if ($group != '')
					$tmpfile = $FOLDER . "/[" . $group . "] " . $name . ".htm";
				if (Filesystem::file_exists($tmpfile))
					Filesystem::unlink($tmpfile);
			}
			$this->noteMapper->deleteNote($note);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @param $FOLDER
	 * @param OwnNote $note
	 */
	public function removeFile($FOLDER, $note){
		$group = $note->getGrouping();
		$name = $note->getName();
		if ($FOLDER != '') {
			$tmpfile = $FOLDER . "/" . $name . ".htm";
			if ($group != '')
				$tmpfile = $FOLDER . "/[" . $group . "] " . $name . ".htm";
			if (Filesystem::file_exists($tmpfile))
				Filesystem::unlink($tmpfile);
		}
	}

	/**
	 * @param $FOLDER
	 * @param boolean $showdel
	 * @return array
	 */
	public function getListing($FOLDER, $showdel) {
		// Get the listing from the database
		$requery = false;
		$uid = \OC::$server->getUserSession()->getUser()->getUID();
		$shared_items = \OCP\Share::getItemsSharedWith('ownnote', 'populated_shares');
		/**
		 * @var $results OwnNote[]
		 */
		$results = array_merge($this->findNotesFromUser($uid), $shared_items);

		$results2 = $results;
		if ($results)
			foreach ($results as $result) {
				if($result instanceof OwnNote) {
					$result = $result->jsonSerialize();
				}

				foreach ($results2 as $result2) {
					if($result2 instanceof OwnNote) {
						$result2 = $result2->jsonSerialize();
					}
					if ($result['id'] != $result2['id'] && $result['name'] == $result2['name'] && $result['grouping'] == $result2['grouping']) {
						// We have a duplicate that should not exist. Need to remove the offending record first
						$delid = -1;
						if ($result['mtime'] == $result2['mtime']) {
							// If the mtime's match, delete the oldest ID.
							$delid = $result['id'];
							if ($result['id'] > $result2['id'])
								$delid = $result2['id'];
						} elseif ($result['mtime'] > $result2['mtime']) {
							// Again, delete the oldest
							$delid = $result2['id'];
						} elseif ($result['mtime'] < $result2['mtime']) {
							// The only thing left is if result is older
							$delid = $result['id'];
						}
						if ($delid != -1) {
							$this->delete('', $delid);
							$requery = true;
						}
					}
				}
			}
		if ($requery) {
			$shared_items = \OCP\Share::getItemsSharedWith('ownnote', 'populated_shares');
			$results = array_merge($this->findNotesFromUser($uid), $shared_items);
			$requery = false;
		}

		// Tests to add a bunch of notes
		//$now = new DateTime();
		//for ($x = 0; $x < 199; $x++) {
		//saveNote('', "Test ".$x, '', '', $now->getTimestamp());
		//}
		$farray = array();
		if ($FOLDER != '') {
			// Create the folder if it doesn't exist
			if (!Filesystem::is_dir($FOLDER)) {
				if (!Filesystem::mkdir($FOLDER)) {
					\OCP\Util::writeLog('ownnote', 'Could not create ownNote directory.', \OCP\Util::ERROR);
					throw new \Exception("Error creating ownNote directory");
				}
			}
			// Synchronize files to the database
			$filearr = array();
			if ($listing = Filesystem::opendir($FOLDER)) {
				if (!$listing) {
					\OCP\Util::writeLog('ownnote', 'Error listing directory.', \OCP\Util::ERROR);
					throw new \Exception("Error listing dir");
				}
				while (($file = readdir($listing)) !== false) {
					$tmpfile = $file;
					if ($tmpfile == "." || $tmpfile == "..") continue;
					if (!$this->utils->endsWith($tmpfile, ".htm") && !$this->utils->endsWith($tmpfile, ".html")) continue;
					if ($info = Filesystem::getFileInfo($FOLDER . "/" . $tmpfile)) {
						// Check for EVERNOTE but wait to rename them to get around:
						// https://github.com/owncloud/core/issues/16202
						if ($this->utils->endsWith($tmpfile, ".html")) {
							Evernote::checkEvernote($FOLDER, $tmpfile);
						}
						// Separate the name and group name
						$name = preg_replace('/\\.[^.\\s]{3,4}$/', '', $tmpfile);
						$group = "";
						if (substr($name, 0, 1) == "[") {
							$end = strpos($name, ']');
							$group = substr($name, 1, $end - 1);
							$name = substr($name, $end + 1, strlen($name) - $end + 1);
							$name = trim($name);
						}
						// Set array for later checking
						$filearr[] = $tmpfile;
						// Check to see if the file is in the DB
						$fileindb = false;
						if ($results)
							foreach ($results as $result) {
								if($result instanceof OwnNote) {
									$result = $result->jsonSerialize();
								}
								if ($result['deleted'] == 0)
									if ($name == $result['name'] && $group == $result['grouping']) {
										$fileindb = true;
										// If it is in the DB, check if the filesystem file is newer than the DB
										if ($result['mtime'] < $info['mtime']) {
											// File is newer, this could happen if a user updates a file
											$html = Filesystem::file_get_contents($FOLDER . "/" . $tmpfile);
											$n = [
												'id' => $result['id'],
												'mtime' => $info['mtime'],
												'note' => ($html) ? $html : ''
											];
											$this->update('', $n);
											$requery = true;
										}
									}
							}
						if (!$fileindb) {
							// If it's not in the DB, add it.
							if ($html = Filesystem::file_get_contents($FOLDER . "/" . $tmpfile)) {
							} else {
								$html = "";
							}
							$n = [
								'name' => $name,
								'group' => $group,
								'note' => $html,
								'mtime' => $info['mtime'],
								'uid' => $uid
							];
							$this->create('', $n, $uid);
							$requery = true;
						}
						// We moved the rename down here to overcome the OC issue
						if ($this->utils->endsWith($tmpfile, ".html")) {
							$tmpfile = substr($tmpfile, 0, -1);
							if (!Filesystem::file_exists($FOLDER . "/" . $tmpfile)) {
								Filesystem::rename($FOLDER . "/" . $file, $FOLDER . "/" . $tmpfile);
							}
						}
					}
				}
			}
			if ($requery) {
				$shared_items = \OCP\Share::getItemsSharedWith('ownnote', 'populated_shares');
				$results = array_merge($this->findNotesFromUser($uid), $shared_items);
			}
			// Now also make sure the files exist, they may not if the user switched folders in admin.
			if ($results)
				foreach ($results as $result) {
					if($result instanceof OwnNote) {
						$result = $result->jsonSerialize();
					}
					if ($result['deleted'] == 0) {
						$tmpfile = $result['name'] . ".htm";
						if ($result['grouping'] != '')
							$tmpfile = '[' . $result['grouping'] . '] ' . $result['name'] . '.htm';
						$filefound = false;
						foreach ($filearr as $f) {
							if ($f == $tmpfile) {
								$filefound = true;
								break;
							}
						}
						if (!$filefound) {
							$this->update($FOLDER, $result);
						}
					}
				}
		}
		// Now loop through and return the listing
		if ($results) {
			$count = 0;
			$now = new \DateTime();
			$filetime = new \DateTime();

			foreach ($results as $result) {
				if($result instanceof OwnNote) {
					$result = $result->jsonSerialize();
				}
				if ($result['deleted'] == 0 || $showdel == true) {
					$filetime->setTimestamp($result['mtime']);
					$timestring = $this->utils->getTimeString($filetime, $now);
					$f = array();
					$f['id'] = $result['id'];
					$f['uid'] = $result['uid'];
					$f['name'] = $result['name'];
					$f['group'] = ($result['grouping']) ? $result['grouping'] : '';
					$f['timestring'] = $timestring;
					$f['mtime'] = $result['mtime'];
					$f['timediff'] = $now->getTimestamp() - $result['mtime'];
					$f['deleted'] = $result['deleted'];
					$f['permissions'] = @$result['permissions'];


					$shared_with = \OCP\Share::getUsersItemShared('ownnote', $result['id'], $result['uid']);
					// add shares (all shares, if it's an owned note, only the user for shared notes (not disclosing other sharees))
					$f['shared_with'] = ($result['uid'] == $uid) ? $shared_with : [$uid];

					$farray[$count] = $f;
					$count++;
				}
			}
		}
		return $farray;
	}

	private function checkPermissions($permission, $nid) {
		// gather information
		$uid = \OC::$server->getUserSession()->getUser()->getUID();
		$note = $this->find($nid);
		// owner is allowed to change everything
		if ($uid === $note->getUid()) {
			return true;
		}

		// check share permissions
		$shared_note = \OCP\Share::getItemSharedWith('ownnote', $nid, 'populated_shares')[0];
		return $shared_note['permissions'] & $permission;
	}
}
