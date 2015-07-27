<?php namespace Bkwld\Cloner;

trait Cloneable {

	/**
	 * Return the list of attributes on this model that should be cloned
	 * 
	 * @return array
	 */
	public function getCloneExemptAttributes() {
		if (!isset($this->clone_except_attributes)) return [];
		return $this->clone_except_attributes;
	}

	/**
	 * Return the list of relations on this model that should be cloned
	 *
	 * @return array 
	 */
	public function getCloneableRelations() {
		if (!isset($this->cloneable_relations)) return [];
		return $this->cloneable_relations;
	}

	/**
	 * A no-op callback that gets fired when a model is cloning but before it gets
	 * committed to the database
	 * 
	 * @return void
	 */
	public function onCloning() {}

	/**
	 * A no-op callback that gets fired when a model is cloned and saved to the
	 * database
	 * 
	 * @return void
	 */
	public function onCloned() {}

}
