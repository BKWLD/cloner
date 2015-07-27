<?php

// Deps
use Bkwld\Cloner\Cloner;
use Bkwld\Cloner\Stubs\Article;
use Bkwld\Cloner\Stubs\Author;
use Bkwld\Cloner\Stubs\Photo;
use Illuminate\Database\Capsule\Manager as DB;

class ClonerTest extends PHPUnit_Framework_TestCase {

	public function setUp() {
		parent::setUp();
		$this->setUpDatabase();
		$this->migrateTables();
		$this->seed();
	}

	// https://github.com/laracasts/TestDummy/blob/master/tests/FactoryTest.php#L18
	protected function setUpDatabase() {
		$db = new DB;

		$db->addConnection([
				'driver' => 'sqlite',
				'database' => ':memory:'
		]);

		$db->bootEloquent();
		$db->setAsGlobal();
	}

	// https://github.com/laracasts/TestDummy/blob/master/tests/FactoryTest.php#L31
	protected function migrateTables() {
		DB::schema()->create('articles', function ($table) {
			$table->increments('id');
			$table->string('title');
			$table->timestamps();
		});

		DB::schema()->create('authors', function ($table) {
			$table->increments('id');
			$table->string('name');
			$table->timestamps();
		});

		DB::schema()->create('article_author', function ($table) {
			$table->increments('id');
			$table->integer('article_id')->unsigned();
			$table->integer('author_id')->unsigned();
		});

		DB::schema()->create('photos', function ($table) {
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

		Photo::unguard();
		$this->article->photos()->save(new Photo([
			'uid' => 1,
			'image' => '/test.jpg',
			'source' => true,
		]));
	}

	// public function testExists() {

	// }

	public function testDuplicate() {
		$cloner = new Cloner;
		$clone = $cloner->duplicate($this->article);

		// Test that the new article was created
		$this->assertTrue($clone->exists);
		$this->assertEquals(2, $clone->id);
		$this->assertEquals('Test', $clone->title);

		// Test that new author relationship was formed
		$this->assertEquals(1, $clone->authors()->count());
		$this->assertEquals('Steve', $clone->authors()->first()->name);
		$this->assertEquals(2, DB::table('article_author')->count());

		// Test that the duplicate photo was formed
		$this->assertEquals(1, $clone->photos()->count());
		$photo = $clone->photos()->first();

		// Test that the exempt rule worked
		$this->assertNull($photo->source);

		// Test that onCloning worked
		$this->assertNotEquals(1, $photo->uid);
		
		// $this->assertNotEquals('/test.jpg', $clone->photos()->first()->image);


	}

}