<?php

// Deps
use Bkwld\Cloner\Stubs\Article;
use Mockery as m;

/**
 * Test the trait
 */
class CloneableTest extends PHPUnit_Framework_TestCase {

	public function testDuplicate() {

		m::mock('alias:App', [
			'make' => m::mock('Bkwld\Cloner\Cloner', [
				'duplicate' => m::mock('Bkwld\Cloner\Stubs\Article'),
			])
		]);

		$article = new Article;
		$clone = $article->duplicate();
		$this->assertInstanceOf('Bkwld\Cloner\Stubs\Article', $clone);
	}

	public function testDuplicateWithDifferentDB() {

		m::mock('alias:App', [
			'make' => m::mock('Bkwld\Cloner\Cloner', [
				'duplicateTo' => m::mock('Bkwld\Cloner\Stubs\Article'),
			])
		]);

		$article = new Article;
		$clone = $article->duplicateTo('connection');
		$this->assertInstanceOf('Bkwld\Cloner\Stubs\Article', $clone);
	}

}
