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

	public function testGetRelations() {
		$article = new Article;
		$this->assertEquals(['photos', 'authors'], $article->getCloneableRelations());
	}

	public function testAddRelation() {
		$article = new Article;
		$article->addCloneableRelation('test');
		$this->assertContains('test', $article->getCloneableRelations());
	}

	public function testAddDuplicateRelation() {
		$article = new Article;
		$article->addCloneableRelation('test');
		$article->addCloneableRelation('test');
		$this->assertEquals(['photos', 'authors', 'test'], $article->getCloneableRelations());
	}

}
