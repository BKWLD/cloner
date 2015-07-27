<?php namespace Bkwld\Cloner;

/**
 * Core class that traverses a model's relationships and replicates model
 * attributes
 */
class Cloner {

	/**
	 * @var AttachmentAdapter
	 */
	private $attachment;

	/**
	 * DI
	 * 
	 * @param AttachmentAdapter $attachment
	 */
	public function __construct(AttachmentAdapter $attachment = null) {
		$this->attachment = $attachment;
	}

	/**
	 * Clone a model instance and all of it's files and relations
	 * 
	 * @param  Illuminate\Database\Eloquent\Model $model
	 * @param  Illuminate\Database\Eloquent\Relations\Relation $relation
	 * @return Illuminate\Database\Eloquent\Model The new model instance
	 */
	public function duplicate($model, $relation = null) {		
		$clone = $this->cloneModel($model);
		$this->duplicateAttachments($clone);
		$this->saveClone($clone, $relation);
		$this->cloneRelations($model, $clone);
		return $clone;
	}

	/**
	 * Create duplicate of the model
	 *
	 * @param  Illuminate\Database\Eloquent\Model $model
	 * @return Illuminate\Database\Eloquent\Model The new model instance
	 */
	protected function cloneModel($model) {
		$exempt = method_exists($model, 'getCloneExemptAttributes') ? 
			$model->getCloneExemptAttributes() : null;
		return $model->replicate($exempt);
	}

	/**
	 * Duplicate all attachments, given them a new name, and update the attribute
	 * value
	 *
	 * @param  Illuminate\Database\Eloquent\Model $clone
	 * @return void 
	 */
	protected function duplicateAttachments($clone) {
		if (!$this->attachment || !method_exists($clone, 'getCloneableFileAttributes')) return;
		foreach($clone->getCloneableFileAttributes() as $attribute) {
			if (!$original = $clone->getAttribute($attribute)) continue;
			$clone->setAttribute($attribute, $this->attachment->duplicate($original));
		}
	}

	/**
	 * Save the clone. If a relation was passed, save the clone onto that
	 * relation.  Otherwise, just save it.
	 *
	 * @param  Illuminate\Database\Eloquent\Model $clone
	 * @param  Illuminate\Database\Eloquent\Relations\Relation $relation
	 * @return void
	 */
	protected function saveClone($clone, $relation = null) {
		if (method_exists($clone, 'onCloning')) $clone->onCloning();
		if ($relation) $relation->save($clone);
		else $clone->save();
		if (method_exists($clone, 'onCloned')) $clone->onCloned();
	}

	/**
	 * Loop through relations and clone or re-attach them
	 *
	 * @param  Illuminate\Database\Eloquent\Model $model
	 * @param  Illuminate\Database\Eloquent\Model $clone
	 * @return void
	 */
	protected function cloneRelations($model, $clone) {
		if (!method_exists($model, 'getCloneableRelations')) return;
		foreach($model->getCloneableRelations() as $relation_name) {
			$this->duplicateRelation($model, $relation_name, $clone);
		}
	}

	/**
	 * Duplicate relationships to the clone
	 *
	 * @param  Illuminate\Database\Eloquent\Model $model
	 * @param  string $relation_name
	 * @param  Illuminate\Database\Eloquent\Model $clone
	 * @return void
	 */
	protected function duplicateRelation($model, $relation_name, $clone) {
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
	protected function duplicatePivotedRelation($relation, $relation_name, $clone) {
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
	protected function duplicateDirectRelation($relation, $relation_name, $clone) {
		$relation->get()->each(function($foreign) use ($clone, $relation_name) {
			$this->duplicate($foreign, $clone->$relation_name());
		});
	}
}
