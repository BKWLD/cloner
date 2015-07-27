<?php namespace Bkwld\Cloner;

interface AttachmentAdapter {

	/**
	 * Duplicate a file, identified by the reference string, which was pulled from
	 * a model attribute
	 * 
	 * @param  string $reference
	 * @return string New reference to duplicated file
	 */
	public function duplicate($reference);

}
