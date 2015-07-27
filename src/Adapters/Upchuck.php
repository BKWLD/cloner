<?php namespace Bkwld\Cloner\Adapters;

// Deps
use Bkwld\Cloner\AttachmentAdapter;
use Bkwld\Upchuck\Helpers;
use Bkwld\Upchuck\Storage;
use League\Flysystem\Filesystem;

/**
 * File attachment adpater for https://github.com/BKWLD/upchuck
 */
class Upchuck implements AttachmentAdapter {

	/**
	 * @var Bkwld\Upchuck\Helpers
	 */
	private $helpers;

	/**
	 * @var Bkwld\Upchuck\Storage
	 */
	private $storage;

	/**
	 * @var League\Flysystem\Filesystem
	 */
	private $disk;

	/**
	 * DI
	 */
	public function __construct(Helpers $helpers, 
		Storage $storage, 
		Filesystem $disk) {
		$this->helpers = $helpers;
		$this->storage = $storage;
		$this->disk = $disk;
	}

	/**
	 * Duplicate a file given it's URL
	 * @param  string $url
	 * @return string
	 */
	public function duplicate($url) {
		$current_path = $this->helpers->path($url);
		$new_path = $this->storage->makeNestedAndUniquePath(basename($current_path));
		$this->disk->copy($current_path, $new_path);
		return $this->helpers->url($new_path);
	}

}