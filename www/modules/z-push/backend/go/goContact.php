<?php

use GO\Base\Util\StringHelper;
use go\core\acl\model\Acl;
use go\modules\community\addressbook\model\Contact;

class goContact extends GoBaseBackendDiff {
	
	private $convertor;
	public function __construct() {
		parent::__construct();
		
		$this->convertor = new ContactConvertor();
	}

	public function DeleteMessage($folderid, $id, $contentParameters) {
		ZLog::Write(LOGLEVEL_DEBUG, 'goContact->DeleteMessage('.$folderid.','.$id.')');
		
		$contact = Contact::findById($id);		
		
		if (!$contact) {
			return true;
		} else if(!$contact->getPermissionLevel() >= Acl::LEVEL_DELETE){
			return true;
		} else {
			return $contact->delete(); // This throws an error when the contact is read only
		}
	}
	
	/**
	 * Get the item object that needs to be synced to the phone.
	 * This information will be send to the phone.
	 * 
	 * Direction: SERVER -> PHONE
	 * 
	 * @param StringHelper $folderid
	 * @param int $id
	 * @param array $contentParameters
	 * @return SyncContact
	 */
	public function GetMessage($folderid, $id, $contentParameters) {

		ZLog::Write(LOGLEVEL_DEBUG, "GetMessage($folderid, $id, ...)");
		
		$contact = Contact::findById($id);	
		if (!$contact) {
			ZLog::Write(LOGLEVEL_WARN, "Contact with ID: ".$id." not found!");
			return false;
		}

		$message = $this->convertor->GO2AS($contact, $contentParameters);
		
//		ZLog::Write(LOGLEVEL_DEBUG, var_export($message, true));
		return $message;		
	}
	
	

	/**
	 * Save the information from the phone to Group-Office.
	 * 
	 * Direction: PHONE -> SERVER
	 * 
	 * @param StringHelper $folderid
	 * @param int $id
	 * @param SyncContact $message
	 * @return array
	 */
	public function ChangeMessage($folderid, $id, $message, $contentParameters)
	{
		
		ZLog::Write(LOGLEVEL_DEBUG, "ChangeMessage($folderid, $id, .., ..)");
//		ZLog::Write(LOGLEVEL_DEBUG, var_export($message, true));

		$contact = empty($id) ? false : Contact::findById($id);

		if (!$contact) {
			$contact = new Contact();
			$contact->addressBookId = $this->convertor->getDefaultAddressBook()->id;
		} else
		{
			ZLog::Write(LOGLEVEL_DEBUG, "Found contact");
		}

		if ($contact->getPermissionLevel() < Acl::LEVEL_WRITE) {
			ZLog::Write(LOGLEVEL_DEBUG, "Skipping update of read-only contact " . $contact->name);
			return $this->StatMessage($folderid, $id);
		}

		$this->convertor->AS2GO($message, $contact, $contentParameters);
	
		return $this->StatMessage($folderid, $id);		

	}

	/**
	 * Get the status of an item
	 * 
	 * @param StringHelper $folderid
	 * @param int $id
	 * @return array
	 */
	public function StatMessage($folderid, $id) {
		ZLog::Write(LOGLEVEL_DEBUG, "StatMessage($folderid, $id)");
		
		$contact = Contact::findById($id);
		
		$stat = false;
		
		if ($contact) {
			$stat = array();
			$stat["id"] = $contact->id . "";
			$stat["flags"] = "1";
			$stat['mod'] = $contact->modifiedAt->format("U");			
		}

		return $stat;
	}

	/**
	 * Get the list of the items that need to be synced
	 * 
	 * @param StringHelper $folderid
	 * @param type $cutoffdate
	 * @return array
	 */
	public function GetMessageList($folderid, $cutoffdate) {

		ZLog::Write(LOGLEVEL_DEBUG, "GetMessageList($folderid, $cutoffdate)");
		
		$list = Contact::find()
						->select('c.id, UNIX_TIMESTAMP(c.modifiedAt) AS `mod`, "1" AS flags')
						->fetchMode(PDO::FETCH_ASSOC)
						->filter([
								"permissionLevel" => Acl::LEVEL_READ
						])->all();
		
		return $list;
	}

	/**
	 * Get the syncFolder that is attached to the given id
	 * 
	 * @param StringHelper $id
	 * @return SyncFolder
	 */
	public function GetFolder($id) {

		$folder = new SyncFolder();
		$folder->serverid = $id;
		$folder->parentid = "0";
		$folder->displayname = 'Contacts';
		$folder->type = SYNC_FOLDER_TYPE_CONTACT;

		return $folder;
	}

	/**
	 * Get a list of folders that are located in the current folder
	 * 
	 * @return array
	 */
	public function GetFolderList() {
		$folders = array();
		$folder = $this->StatFolder(BackendGoConfig::CONTACTBACKENDFOLDER);
		$folders[] = $folder;

		return $folders;
	}
	
	public function getNotification($folder = null) {
		return Contact::getState();
	}

}
