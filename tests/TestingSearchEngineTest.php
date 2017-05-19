<?php

use Illuminate\Database\Eloquent\Collection;
use Laravel\Scout\Builder;
use PatOui\Scout\Engines\TestingEngine;

class TestingEngineTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testUpdateAddsObjectsToIndex()
    {
        // Arrange
        $engine = new TestingEngine;
        $model = new TestModel;
        $model->id = 123;
        $model->title = 'My Awesome Title';
        $this->assertTrue(empty($engine->data));

        // Act
        $engine->update(Collection::make([$model]));

        // Assert
        $this->assertTrue(! empty($engine->data));
    }

    public function testSearchReturnsExpectedResult()
    {
        // Arrange
        $engine = new TestingEngine;
        $model = new TestModel;
        $model->id = 101;
        $model->title = 'Awesome Title';
        $model2 = new TestModel;
        $model2->id = 201;
        $model2->title = 'Awesome Title Works';
        $model3 = new TestModel;
        $model3->id = 301;
        $model3->title = 'Random Non Sense';
        $this->assertTrue(empty($engine->data));
        $engine->update(Collection::make([$model, $model2, $model3]));
        $scoutBuilder = new Builder($model, 'Awesome Title');

        // Act
        $results = $engine->search($scoutBuilder);

        // Assert
        $this->assertTrue(! empty($results));
        $this->assertCount(2, $results);
    }
}

class TestModel
{
    public $searchable = ['title'];
}
