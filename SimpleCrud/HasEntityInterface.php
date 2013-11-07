<?php
/**
 * SimpleCrud\HasEntityInterface
 * 
 * Interface used by the data collectors
 */
namespace SimpleCrud;

use SimpleCrud\Entity;

interface HasEntityInterface {
	public function isCollection ();
	public function toArray (array $parentEntities = array());
	public function load (array $entities);
}
