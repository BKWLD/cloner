<?php namespace Bkwld\Cloner\Stubs;

use Bkwld\Cloner\Cloneable as Cloneable;
use Illuminate\Database\Eloquent\Model;

class Photo extends Model {
	use Cloneable;

	public $clone_except_attributes;

	public function article() {
		return $this->belongsTo('Bkwld\Cloner\Stubs\Article');
	}
}