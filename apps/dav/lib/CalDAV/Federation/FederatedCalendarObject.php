<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Federation;

use Sabre\CalDAV\ICalendarObject;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAVACL\IACL;

class FederatedCalendarObject implements ICalendarObject, IACL {

	public function __construct(
		protected FederatedCalendar $calendarObject,
		protected $objectData,
	) {
	}

	public function getName() {
		return $this->objectData['uri'];
	}

	public function setName($name) {
		throw new \Exception('Not implemented');
	}

	public function get() {
		return $this->objectData['calendardata'];
	}

	public function put($data) {

		$etag = $this->calendarObject->updateFile($this->objectData['uri'], $data);
		$this->objectData['calendardata'] = $data;
		$this->objectData['etag'] = $etag;

		return $etag;
	}

	/**
	 * Deletes the calendar object.
	 */
	public function delete() {
		$this->calendarObject->deleteFile($this->objectData['uri']);
	}

	/**
	 * Returns the mime content-type.
	 *
	 * @return string
	 */
	public function getContentType() {
		$mime = 'text/calendar; charset=utf-8';
		if (isset($this->objectData['component']) && $this->objectData['component']) {
			$mime .= '; component=' . $this->objectData['component'];
		}

		return $mime;
	}

	/**
	 * Returns an ETag for this object.
	 *
	 * The ETag is an arbitrary string, but MUST be surrounded by double-quotes.
	 *
	 * @return string
	 */
	public function getETag() {
		if (isset($this->objectData['etag'])) {
			return $this->objectData['etag'];
		} else {
			return '"' . md5($this->get()) . '"';
		}
	}

	/**
	 * Returns the last modification date as a unix timestamp.
	 *
	 * @return int
	 */
	public function getLastModified() {
		return $this->objectData['lastmodified'];
	}

	/**
	 * Returns the size of this object in bytes.
	 *
	 * @return int
	 */
	public function getSize() {
		if (array_key_exists('size', $this->objectData)) {
			return $this->objectData['size'];
		} else {
			return strlen($this->get());
		}
	}

	/**
	 * Returns the owner principal.
	 *
	 * This must be a url to a principal, or null if there's no owner
	 *
	 * @return string|null
	 */
	public function getOwner() {
		return $this->calendarObject->getPrincipalURI();
	}

	public function getGroup() {
		return null;
	}

	public function getACL() {
		return $this->calendarObject->getACL();
	}

	public function setACL(array $acl) {
		throw new MethodNotAllowed('Changing ACLs on federated events is not allowed');
	}

	public function getSupportedPrivilegeSet() {
		return null;
	}

}
