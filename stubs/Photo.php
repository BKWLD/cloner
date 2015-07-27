<?php namespace Bkwld\Cloner\Stubs;

use Bkwld\Cloner\Cloneable as Cloneable;
use Illuminate\Database\Eloquent\Model;

class Photo extends Model {
	use Cloneable;

	public $clone_exempt_attributes = ['uid', 'source'];

	public function article() {
		return $this->belongsTo('Bkwld\Cloner\Stubs\Article');
	}

	public function onCloning() {
		$this->uid = 2;
	}
}