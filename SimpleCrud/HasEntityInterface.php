<?php
/**
 * SimpleCrud\HasEntityInterface
 * 
 * Interface used by the data collectors
 */
namespace SimpleCrud;

use SimpleCrud\Entity;

interface HasEntityInterface {
	public function entity ();
	public function manager ();
	public function isCollection ();
}
