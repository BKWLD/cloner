<?php

// Deps
use Bkwld\Cloner\Cloner;
use Bkwld\Cloner\Adapters\Upchuck;
use Bkwld\Cloner\Stubs\Article;
use Bkwld\Cloner\Stubs\Author;
use Bkwld\Cloner\Stubs\Photo;
use Bkwld\Upchuck\Helpers;
use Bkwld\Upchuck\Storage;
use Illuminate\Database\Capsule\Manager as DB;
use League\Flysystem\Filesystem;
use League\Flysystem\Vfs\VfsAdapter as Adapter;
use League\Flysystem\MountManager;
use Mockery as m;
use VirtualFileSystem\FileSystem as Vfs;

class FunctionalTest extends PHPUnit_Framework_TestCase {

	protected function initUpchuck() {

		// Setup filesystem
		$fs = new Vfs;
		$this->fs_path = $fs->path('/');
		$this->disk = new Filesystem(new Adapter($fs));

		// Create upchuck adapter instance

		$this->helpers = new Helpers([
			'url_prefix' => '/uploads/'
		]);

		$manager = new MountManager([
			'tmp' => $this->disk,
			'disk' => $this->disk,
		]);

		$storage = new Storage($manager, $this->helpers);

		$this->upchuck_adapter = new Upchuck(
			$this->helpers,
			$storage,
			$this->disk
		);
	}

	protected function mockEvents() {
		return m::mock('Illuminate\Events\Dispatcher', [ 'fire' => null ]);
	}

	// https://github.com/laracasts/TestDummy/blob/master/tests/FactoryTest.php#L18
	protected function setUpDatabase() {
		$db = new DB;

		$db->addConnection([
				'driver' => 'sqlite',
				'database' => ':memory:'
		], 'default');

		$db->addConnection([
				'driver' => 'sqlite',
				'database' => ':memory:'
		], 'alt');

		$db->bootEloquent();
		$db->setAsGlobal();
	}

	// https://github.com/laracasts/TestDummy/blob/master/tests/FactoryTest.php#L31
	protected function migrateTables($connection = 'default') {
		DB::connection($connection)->getSchemaBuilder()->create('articles', function ($table) {
			$table->increments('id');
			$table->string('title');
			$table->timestamps();
		});

		DB::connection($connection)->getSchemaBuilder()->create('authors', function ($table) {
			$table->increments('id');
			$table->string('name');
			$table->timestamps();
		});

		DB::connection($connection)->getSchemaBuilder()->create('article_author', function ($table) {
			$table->increments('id');
			$table->integer('article_id')->unsigned();
			$table->integer('author_id')->unsigned();
		});

		DB::connection($connection)->getSchemaBuilder()->create('photos', function ($table) {
			$table->increments('id');
			$table->integer('article_id')->unsigned();
			$table->string('uid');
			$table->string('image');
			$table->boolean('source')->nullable();
			$table->timestamps();
		});
	}

	protected function seed() {
		Article::unguard();
		$this->article = Article::create([
			'title' => 'Test',
		]);

		Author::unguard();
		$this->article->authors()->attach(Author::create([
			'name' => 'Steve',
		]));

		$this->disk->write('test.jpg', 'contents');

		Photo::unguard();
		$this->article->photos()->save(new Photo([
			'uid' => 1,
			'image' => '/uploads/test.jpg',
			'source' => true,
		]));
	}

	// Test that a record is created in the same database
	function testExists() {
		$this->initUpchuck();
		$this->setUpDatabase();
		$this->migrateTables();
		$this->seed();

		$cloner = new Cloner($this->upchuck_adapter, $this->mockEvents());
		$clone = $cloner->duplicate($this->article);

		// Test that the new article was created
		$this->assertTrue($clone->exists);
		$this->assertEquals(2, $clone->id);
		$this->assertEquals('Test', $clone->title);

		// Test mamny to many
		$this->assertEquals(1, $clone->authors()->count());
		$this->assertEquals('Steve', $clone->authors()->first()->name);
		$this->assertEquals(2, DB::table('article_author')->count());

		// Test one to many
		$this->assertEquals(1, $clone->photos()->count());
		$photo = $clone->photos()->first();

		// Test excemptions
		$this->assertNull($photo->source);

		// Test callbacks
		$this->assertNotEquals(1, $photo->uid);

		// Test the file was created in a different place
		$this->assertNotEquals('/uploads/test.jpg', $photo->image);

		// Test that the file is the same
		$path = $this->helpers->path($photo->image);
		$this->assertTrue($this->disk->has($path));
		$this->assertEquals('contents', $this->disk->read($path));
	}

	// Test that model is created in a differetnt database.  These checks don't
	// use eloquent because Laravel has issues with relationships on models in
	// a different connection
	// https://github.com/laravel/framework/issues/9355
	function testExistsInAltDatabaseAndFilesystem() {
		$this->initUpchuck();
		$this->setUpDatabase();
		$this->migrateTables();
		$this->migrateTables('alt');
		$this->seed();

		// ADd the remote disk to upchuck adapter
		$this->remoteDisk = new Filesystem(new Adapter(new Vfs));
		$this->upchuck_adapter->setDestination($this->remoteDisk);

		// Make sure that the alt databse is empty
		$this->assertEquals(0, DB::connection('alt')->table('articles')->count());
		$this->assertEquals(0, DB::connection('alt')->table('authors')->count());
		$this->assertEquals(0, DB::connection('alt')->table('photos')->count());

		$cloner = new Cloner($this->upchuck_adapter, $this->mockEvents());
		$clone = $cloner->duplicateTo($this->article, 'alt');

		// Test that the new article was created
		$this->assertEquals(1, DB::connection('alt')->table('articles')->count());
		$clone = DB::connection('alt')->table('articles')->first();
		$this->assertEquals(1 , $clone->id);
		$this->assertEquals('Test', $clone->title);

		// Test that mamny to many failed
		$this->assertEquals(0, DB::connection('alt')->table('authors')
			->where('article_id', $clone->id)->count());

		// Test one to many
		$this->assertEquals(1, DB::connection('alt')->table('photos')
			->where('article_id', $clone->id)->count());
		$photo = DB::connection('alt')->table('photos')
			->where('article_id', $clone->id)->first();

		// Test excemptions
		$this->assertNull($photo->source);

		// Test callbacks
		$this->assertNotEquals(1, $photo->uid);

		// Test the file was created on the remote disk
		$path = $this->helpers->path($photo->image);
		$this->assertTrue($this->remoteDisk->has($path));

		// Test that the file is the same
		$this->assertEquals('contents', $this->remoteDisk->read($path));
	}
}
