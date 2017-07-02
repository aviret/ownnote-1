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
use OCP\IConfig;
use OCP\AppFramework\Db\DoesNotExistException;
use DateTime;
use DOMDocument;
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
		return $this->noteMapper->findNotesFromUser($userId);
	}

	/**
	 * Get a single vault
	 *
	 * @param $note_id
	 * @param $user_id
	 * @return OwnNote[]
	 * @internal param $vault_id
	 */
	public function find($note_id, $user_id = null) {
		$vault = $this->noteMapper->find($note_id, $user_id);
		return $vault;
	}

	/**
	 * Creates a note
	 *
	 * @param array $note
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
			$entity->setNote($note['note']);
			if($note['mtime']) {
				$entity->setMtime($note['mtime']);
			} else {
				$entity->setMtime(time());
			}
			$note = $entity;
		}
		if (!is_a($note, 'OwnNote')) {
			throw new \Exception("Expected OwnNote object!");
		}

		$group = $note->getGrouping();
		$name = $note->getName();
		$content = $note->getNote();

		if ($FOLDER != '') {
			$tmpfile = $FOLDER . "/" . $name . ".htm";
			if ($group != '')
				$tmpfile = $FOLDER . "/[" . $group . "] " . $name . ".htm";
			Filesystem::file_put_contents($tmpfile, $content);
			if ($info = Filesystem::getFileInfo($tmpfile)) {
				$mtime = $info['mtime'];
			}
		}

		return $this->noteMapper->create($note);
	}

	/**
	 * Update vault
	 *
	 * @param $note
	 * @param $userId
	 * @return OwnNote
	 * @throws \Exception
	 * @internal param $vault
	 */
	public function update($FOLDER, $note) {
		if (is_array($note)) {
			$entity = new OwnNote();
			$entity->setId($note['id']);
			$entity->setName($note['name']);
			$entity->setGrouping($note['group']);
			$entity->setNote($note['note']);
			if($note['mtime']) {
				$entity->setMtime($note['mtime']);
			}
			$note = $entity;
		}
		if (!is_a($note, 'OwnNote')) {
			throw new \Exception("Expected OwnNote object!");
		}
		$group = $note->getGrouping();
		$name = $note->getName();
		$content = $note->getNote();

		if ($FOLDER != '') {
			$tmpfile = $FOLDER . "/" . $name . ".htm";
			if ($group != '')
				$tmpfile = $FOLDER . "/[" . $group . "] " . $name . ".htm";
			Filesystem::file_put_contents($tmpfile, $content);
			if ($info = Filesystem::getFileInfo($tmpfile)) {
				$note->setMtime($info['mtime']);
			}
		}

		return $this->noteMapper->updateNote($note);
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

	public function getListing($FOLDER, $showdel) {
		// Get the listing from the database
		$requery = false;
		$uid = \OC::$server->getUserSession()->getUser()->getUID();
		/**
		 * @var $results OwnNote[]
		 */
		$results = $this->findNotesFromUser($uid);

		$results2 = $results;
		if ($results)
			foreach ($results as $result) {
				foreach ($results2 as $result2) {
					if ($result->getId() != $result2->getId() && $result->getName() == $result2->getName() && $result->getGrouping() == $result2->getGrouping()) {
						// We have a duplicate that should not exist. Need to remove the offending record first
						$delid = -1;
						if ($result->getMtime() == $result2->getMtime()) {
							// If the mtime's match, delete the oldest ID.
							$delid = $result->getId();
							if ($result->getId() > $result2->getId())
								$delid = $result2->getId();
						} elseif ($result->getMtime() > $result2->getMtime()) {
							// Again, delete the oldest
							$delid = $result2->getId();
						} elseif ($result->getMtime() < $result2->getMtime()) {
							// The only thing left is if result is older
							$delid = $result->getId();
						}
						if ($delid != -1) {
							$this->delete('',$delid);
							$requery = true;
						}
					}
				}
			}
		if ($requery) {
			$results = $this->findNotesFromUser($uid);
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
					exit;
				}
			}
			// Synchronize files to the database
			$filearr = array();
			if ($listing = Filesystem::opendir($FOLDER)) {
				if (!$listing) {
					\OCP\Util::writeLog('ownnote', 'Error listing directory.', \OCP\Util::ERROR);
					exit;
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
								if ($result->getDeleted() == 0)
									if ($name == $result->getName() && $group == $result->getGrouping()) {
										$fileindb = true;
										// If it is in the DB, check if the filesystem file is newer than the DB
										if ($result->getMtime() < $info['mtime']) {
											// File is newer, this could happen if a user updates a file
											$html = "";
											$html = Filesystem::file_get_contents($FOLDER . "/" . $tmpfile);
											$n = [
												'id' => $result->getId(),
												'mtime' => $info['mtime'],
												'note' => $html
											];
											$this->update('',$n);
											$requery = true;
										}
									}
							}
						if (!$fileindb) {
							// If it's not in the DB, add it.
							$html = "";
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
				$results = $this->findNotesFromUser($uid);
			}
			// Now also make sure the files exist, they may not if the user switched folders in admin.
			if ($results)
				foreach ($results as $result) {
					if ($result->getDeleted() == 0) {
						$tmpfile = $result->getName() . ".htm";
						if ($result->getGrouping() != '')
							$tmpfile = '[' . $result->getGrouping() . '] ' . $result->getName() . '.htm';
						$filefound = false;
						foreach ($filearr as $f) {
							if ($f == $tmpfile) {
								$filefound = true;
								break;
							}
						}
						if (!$filefound) {
							$this->update($FOLDER,$result);
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
				if ($result->getDeleted() == 0 || $showdel == true) {
					$filetime->setTimestamp($result->getMtime());
					$timestring = $this->utils->getTimeString($filetime, $now);
					$f = array();
					$f['id'] = $result->getId();
					$f['uid'] = $result->getUid();
					$f['name'] = $result->getName();
					$f['group'] = $result->getGrouping();
					$f['timestring'] = $timestring;
					$f['mtime'] = $result->getMtime();
					$f['timediff'] = $now->getTimestamp() - $result->getMtime();
					$f['deleted'] = $result->getDeleted();


					$shared_with = \OCP\Share::getUsersItemShared('ownnote', $result->getId(), $result->getUid());
					// add shares (all shares, if it's an owned note, only the user for shared notes (not disclosing other sharees))
					$f['shared_with'] = ($result->getUid() == $uid) ? $shared_with : [$uid];

					$farray[$count] = $f;
					$count++;
				}
			}
		}
		return $farray;
	}
}
