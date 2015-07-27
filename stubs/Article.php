<?php namespace Bkwld\Cloner\Stubs;

use Bkwld\Cloner\Cloneable as Cloneable;
use Illuminate\Database\Eloquent\Model;

class Article extends Model {
	use Cloneable;

	public $clone_except_attributes;
	public $cloneable_relations = ['photos', 'authors'];

	public function photos() {
		return $this->hasMany('Bkwld\Cloner\Stubs\Photo');
	}

	public function authors() {
		return $this->belongsToMany('Bkwld\Cloner\Stubs\Author');
	}
}