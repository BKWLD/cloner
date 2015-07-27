<?php namespace Bkwld\Cloner\Stubs;

use Bkwld\Cloner\Cloneable as Cloneable;
use Illuminate\Database\Eloquent\Model;

class Author extends Model {
	use Cloneable;

	public function articles() {
		return $this->belongsToMany('Bkwld\Cloner\Stubs\Article');
	}
}