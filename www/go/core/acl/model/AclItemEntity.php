<?php

namespace go\core\acl\model;

use go\core\acl\model\Acl;
use go\core\orm\Query;
use go\core\jmap\EntityController;
use go\core\jmap\Entity;

/**
 * The AclItemEntity class
 * 
 * Is used for items that belong to an entity which is an {@see AclEntity}.
 * For examples a Note is an AclItemEntity because it belongs to the NoteBook AclEntity.
 * 
 * It's main purpose is to provide the {@see applyAclToQuery()} function so you 
 * can easily query items which a user has read permissions for.
 * 
 * You can also specify another AclItemEntity so it will recurse.
 * 
 * @see AclOwnerEntity
 */
abstract class AclItemEntity extends AclEntity {

	/**
	 * Get the {@see AclOwnerEntity} or {@see AclItemEntity} class name that it 
	 * depends on.
	 * 
	 * @return string 
	 */
	abstract protected static function aclEntityClass();

	/**
	 * Get the keys for joining the aclEntityClass table.
	 * 
	 * @return array eg. ['folderId' => 'id']
	 */
	abstract protected static function aclEntityKeys();

	/**
	 * Applies conditions to the query so that only entities with the given permission level are fetched.
	 * 
	 * @param Query $query
	 * @param int $level
	 * @param int $userId Defaults to current user ID
	 * @return Query
	 */
	public static function applyAclToQuery(Query $query, $level = Acl::LEVEL_READ, $userId = null) {

		$alias = self::joinAclEntity($query);

		Acl::applyToQuery($query, $alias . '.aclId', $level, $userId);
		
		return $query;
	}
	
	public static function joinAclEntity(Query $query, $fromAlias = null) {
		$cls = static::aclEntityClass();

		/* @var $cls Entity */
		
		if(!isset($fromAlias)) {
			$fromAlias = $query->getTableAlias();
		}

		
//		$toTable = $cls::getMapping()->getTable($aclColumn->table->getName());

		$keys = [];
		foreach (static::aclEntityKeys() as $from => $to) {
			$column = $cls::getMapping()->getColumn($to);
			
			$keys[] = $fromAlias . '.' . $from . ' = ' . $column->table->getAlias() . ' . '. $to;
		}

		$query->join($column->table->getName(), $column->table->getAlias(), implode(' AND ', $keys));
		
		
		//If this is another AclItemEntity then recurse
		if(is_a($cls, AclItemEntity::class, true)) {
			return $cls::joinAclEntity($query,  $column->table->getAlias());
		} else
		{
			//otherwise this must hold the aclId column
			$aclColumn = $cls::getMapping()->getColumn('aclId');
			if(!$aclColumn) {
				throw new \Exception("Column 'aclId' is required for AclEntity '$cls'");
			}
			
			return $column->table->getAlias();
		}
	}	
	
	/**
	 * Get the entity that holds the acl id.
	 * 
	 * @return Entity
	 */
	protected function getAclEntity() {
		$cls = static::aclEntityClass();

		/* @var $cls Entity */


		$keys = [];
		foreach (static::aclEntityKeys() as $from => $to) {
			$keys[$to] = $this->{$from};
		}

		return $cls::find()->where($keys)->single();	
	}

	public function getPermissionLevel() {
		$aclEntity = $this->getAclEntity();

		return $aclEntity->getPermissionLevel(); 
	}

	/**
	 * Finds all aclId's for this entity
	 * 
	 * This query is used in the "getFooUpdates" methods of entities to determine if any of the ACL's has been changed.
	 * If so then the server will respond that it cannot calculate the updates.
	 * 
	 * @see EntityController::getUpdates()
	 * 
	 * @return Query
	 */
	public static function findAcls() {

		$cls = static::aclEntityClass();

		return $cls::findAcls();
	}
	
	public function findAclId() {
		return $this->getAclEntity()->findAclId();
	}

}
