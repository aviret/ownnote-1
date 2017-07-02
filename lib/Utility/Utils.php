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

namespace OCA\OwnNote\Utility;

use OCP\IL10N;

class Utils {
    /**
     * Gets the unix epoch UTC timestamp
     * @return int
     */
	public static function getTime() {
		return (new \DateTime())->getTimestamp();
	}
	/**
	 * @return int the current unix time in milliseconds
	 */
	public static function getMicroTime() {
		return microtime(true);
	}

	/**
	 * @param $uid
	 * @return string
	 */
	public static function getNameByUid($uid){
		$um = \OC::$server->getUserManager();
		$u = $um->get($uid);
		return $u->getDisplayName();
	}

	/**
	 * Splits a string in parts of 5Mb
	 * @param $str
	 * @return array
	 */
	public function splitContent($str) {
		$maxlength = 2621440; // 5 Megs (2 bytes per character)
		$count = 0;
		$strarray = array();
		while (true) {
			if (strlen($str) <= $maxlength) {
				$strarray[$count++] = $str;
				return $strarray;
			} else {
				$strarray[$count++] = substr($str, 0, $maxlength);
				$str = substr($str, $maxlength);
			}
		}
		return $strarray;
	}

	/**
	 * @param $filetime \DateTime
	 * @param $now \DateTime
	 * @return mixed|string
	 */
	public function getTimeString($filetime, $now) {
		$l = \OCP\Util::getL10N('ownnote');
		$difftime = $filetime->diff($now);
		$years = $difftime->y;
		$months = $difftime->m;
		$days = $difftime->d;
		$hours = $difftime->h;
		$minutes = $difftime->i;
		$seconds = $difftime->s;
		$timestring = "";
		if ($timestring == "" && $years == 1) $timestring = str_replace('#', $years, $l->t("# year ago"));
		if ($timestring == "" && $years > 0) $timestring = str_replace('#', $years, $l->t("# years ago"));
		if ($timestring == "" && $months == 1) $timestring = str_replace('#', $months, $l->t("# month ago"));
		if ($timestring == "" && $months > 0) $timestring = str_replace('#', $months, $l->t("# months ago"));
		if ($timestring == "" && $days == 1) $timestring = str_replace('#', $days, $l->t("# day ago"));
		if ($timestring == "" && $days > 0) $timestring = str_replace('#', $days, $l->t("# days ago"));
		if ($timestring == "" && $hours == 1) $timestring = str_replace('#', $hours, $l->t("# hour ago"));
		if ($timestring == "" && $hours > 0) $timestring = str_replace('#', $hours, $l->t("# hours ago"));
		if ($timestring == "" && $minutes == 1) $timestring = str_replace('#', $minutes, $l->t("# minute ago"));
		if ($timestring == "" && $minutes > 0) $timestring = str_replace('#', $minutes, $l->t("# minutes ago"));
		if ($timestring == "" && $seconds == 1) $timestring = str_replace('#', $seconds, $l->t("# second ago"));
		if ($timestring == "" && $seconds > 0) $timestring = str_replace('#', $seconds, $l->t("# seconds ago"));
		return $timestring;
	}


	/**
	 * @param $haystack
	 * @param $needle
	 * @return bool
	 */
	public function startsWith($haystack, $needle) {
		return $needle === "" || strripos($haystack, $needle, -strlen($haystack)) !== false;
	}

	/**
	 * @param $string
	 * @param $test
	 * @return bool
	 */
	public function endsWith($string, $test) {
		$strlen = strlen($string);
		$testlen = strlen($test);
		if ($testlen > $strlen) return false;
		return substr_compare($string, $test, $strlen - $testlen, $testlen, true) === 0;
	}
}