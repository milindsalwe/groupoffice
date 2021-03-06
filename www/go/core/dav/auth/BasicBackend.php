<?php

/**
 * Copyright Intermesh
 *
 * This file is part of Group-Office. You should have received a copy of the
 * Group-Office license along with Group-Office. See the file /LICENSE.TXT
 *
 * If you have questions write an e-mail to info@intermesh.nl
 *
 * @version $Id: Auth_Backend.class.inc.php 7752 2011-07-26 13:48:43Z mschering $
 * @copyright Copyright Intermesh
 * @author Merijn Schering <mschering@intermesh.nl>
 */

namespace go\core\dav\auth;

use GO;
use go\core\auth\TemporaryState;
use go\modules\core\users\model\User;
use Sabre\DAV\Auth\Backend\AbstractBasic;

class BasicBackend extends AbstractBasic {
	
	private $user;
	public $checkModuleAccess='dav';
	
	public function __construct() {
		$this->setRealm("Group-Office");
	}
	
	protected function validateUserPass($username, $password) {
		
		$user = User::find(['id', 'username', 'password'])->where(['username' => $username, 'enabled' => true])->single();
		/* @var $user User */		
		if(!$user) {
			return false;
		}
		
		if(!$user->checkPassword($password)) {
			return false;
		}
		
		$state = new TemporaryState();
		$state->setUserId($user->id);		
		GO()->setAuthState($state);

		$this->user = $user;		
		return true;
	}
}
