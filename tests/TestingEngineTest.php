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
        $model = [
            'id' => 123,
            'title' => 'My Awesome Title',
            'searchable' => ['title']
        ];
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
        $model = [
            'id' => 101,
            'title' => 'Awesome Title',
            'searchable' => ['title']
        ];
        $model2 = [
            'id' => 201,
            'title' => 'Awesome Title Works',
            'searchable' => ['title']
        ];
        $model3 = [
            'id' => 301,
            'title' => 'Random Non Sense',
            'searchable' => ['title']
        ];
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
