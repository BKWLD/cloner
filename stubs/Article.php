<?php namespace Bkwld\Cloner\Stubs;

use Bkwld\Cloner\Cloneable as Cloneable;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Article extends Eloquent {
	use Cloneable;

	public $cloneable_relations = ['photos', 'authors'];

	public function photos() {
		return $this->hasMany('Bkwld\Cloner\Stubs\Photo');
	}

	public function authors() {
		return $this->belongsToMany('Bkwld\Cloner\Stubs\Author');
	}
}