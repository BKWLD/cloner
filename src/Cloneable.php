<?php namespace Bkwld\Cloner;

trait Cloneable {

	/**
	 * Return the list of attributes on this model that should be cloned
	 * 
	 * @return array
	 */
	public function getCloneExemptAttributes() {

		// Alwyas make the id and timestamps exempt
		$defaults = [
			$this->getKeyName(),
			$this->getCreatedAtColumn(),
			$this->getUpdatedAtColumn(),
		];

		// It none specified, just return the defaults, else, merge them
		if (!isset($this->clone_exempt_attributes)) return $defaults;
		return array_merge($defaults, $this->clone_exempt_attributes);
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
