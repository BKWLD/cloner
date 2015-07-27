<?php namespace Bkwld\Cloner\Stubs;

use Illuminate\Database\Eloquent\Model;

class Author extends Model {

	public function articles() {
		return $this->belongsToMany('Bkwld\Cloner\Stubs\Article');
	}
}