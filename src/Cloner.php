<?php namespace Bkwld\Cloner;

/**
 * Core class that traverses a model's relationships and replicates model
 * attributes
 */
class Cloner {

	/**
	 * @var AttachmentAdapter
	 */
	private $attachment_adapter;

	/**
	 * DI
	 * 
	 * @param AttachmentAdapter $attachment_adapter
	 */
	public function __construct(
		AttachmentAdapter $attachment_adapter = null) {
		$this->attachment_adapter = $attachment_adapter;
	}

	/**
	 * Clone a model instance and all of it's files and relations
	 * 
	 * @param  Illuminate\Database\Eloquent\Model $model
	 * @param  Illuminate\Database\Eloquent\Relations\Relation $relation
	 * @return Illuminate\Database\Eloquent\Model The new model instance
	 */
	public function duplicate($model, $relation = null) {

		// Duplicate the model
		$clone = $model->replicate($model->getCloneExemptAttributes());

		// Save the model.  If a relation was passed, save the clone onto that
		// relation.  Otherwise, just save it.
		$clone->onCloning();
		if ($relation) $relation->save($clone);
		else $clone->save();
		$clone->onCloned();

		// Loop though all of it's cloneable relationshsips and duplicate the 
		// relationship
		foreach($model->getCloneableRelations() as $relation_name) {
			$this->duplicateRelation($model, $relation_name, $clone);
		}
		
		// Return the duplicated model
		return $clone;

	}

	/**
	 * Duplicate relationships to the clone
	 *
	 * @param  Illuminate\Database\Eloquent\Model $model
	 * @param  string $relation_name
	 * @param  Illuminate\Database\Eloquent\Model $clone
	 * @return void
	 */
	public function duplicateRelation($model, $relation_name, $clone) {
		$relation = call_user_func([$model, $relation_name]);
		if (is_a($relation, 'Illuminate\Database\Eloquent\Relations\BelongsToMany')) {
			$this->duplicatePivotedRelation($relation, $relation_name, $clone);
		} else $this->duplicateDirectRelation($relation, $relation_name, $clone);
	}

	/**
	 * Duplicate a many-to-many style relation where we are just attaching the
	 * relation to the dupe
	 *
	 * @param  Illuminate\Database\Eloquent\Relations\Relation $relation
	 * @param  string $relation_name
	 * @param  Illuminate\Database\Eloquent\Model $clone
	 * @return void
	 */
	public function duplicatePivotedRelation($relation, $relation_name, $clone) {
		$relation->get()->each(function($foreign) use ($clone, $relation_name) {
			$clone->$relation_name()->attach($foreign);
		});
	}

	/**
	 * Duplicate a one-to-many style relation where the foreign model is ALSO
	 * cloned and then associated
	 *
	 * @param  Illuminate\Database\Eloquent\Relations\Relation $relation
	 * @param  string $relation_name
	 * @param  Illuminate\Database\Eloquent\Model $clone
	 * @return void
	 */
	public function duplicateDirectRelation($relation, $relation_name, $clone) {
		$relation->get()->each(function($foreign) use ($clone, $relation_name) {
			$this->duplicate($foreign, $clone->$relation_name());
		});
	}
}
