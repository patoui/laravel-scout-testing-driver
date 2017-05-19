<?php

namespace PatOui\Scout\Engines;

use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

class TestingEngine extends Engine
{
    public $data = [];

    /**
     * Update the given model in the index.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $models
     * @return void
     */
    public function update($models)
    {
        $this->data = $models->keyBy('id')->toArray() + $this->data;
    }

    /**
     * Remove the given model from the index.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $models
     * @return void
     */
    public function delete($models)
    {
        $models->each(function ($model) {
            if (property_exists($model, 'id')) {
                $index = array_search($model->id, $this->data);
                if ($index !== false) {
                    unset($this->data[$index]);
                }
            }
        });
    }

    /**
     * Perform the given search on the engine.
     *
     * @param  \Laravel\Scout\Builder  $builder
     * @return mixed
     */
    public function search(Builder $builder)
    {
        $results = [];

        // input misspelled word
        $query = $builder->query;

        // no shortest distance found, yet
        $shortest = -1;

        // loop through words to find the closest
        foreach ($this->data as $index => $current) {

            // Filter for scalar values only
            $temp = array_filter((array) $current, function ($value) {
                return is_scalar($value);
            });
            // Filter only searchable items
            if (! empty($current->searchable)) {
                $temp = Arr::only($temp, $current->searchable);
            }
            // Placeholder for property Levenshtein value
            $propLev = -1;

            // Iterate object properties
            foreach ($temp as $property => $propertyValue) {

                // calculate the distance between the query,
                // and the current word
                $lev = levenshtein($query, $propertyValue);

                if ($lev <= $propLev || $propLev < 0) {
                    $propLev = $lev;

                    // Perfect match, exit property check loop
                    if ($propLev == 0) {
                        break;
                    }
                }

            }

            // Object had property with valid Levenshtein value,
            // add it to the results
            if ($propLev !== -1 && $propLev <= 10) {
                $current['lev'] = $propLev;
                $results[] = $current;
            }
        }

        // Sort array based on Levenshtein value (ascending)
        uasort($results, function ($a, $b) {
            if ($a->lev == $b->lev) {
                return 0;
            }
            return ($a->lev < $b->lev) ? -1 : 1;
        });

        return $results;
    }

    /**
     * Perform the given search on the engine.
     *
     * @param  \Laravel\Scout\Builder  $builder
     * @param  int  $perPage
     * @param  int  $page
     * @return mixed
     */
    public function paginate(Builder $builder, $perPage, $page)
    {
        return [];
    }

    /**
     * Pluck and return the primary keys of the given results.
     *
     * @param  mixed  $results
     * @return \Illuminate\Support\Collection
     */
    public function mapIds($results)
    {
        return Collection::make();
    }

    /**
     * Map the given results to instances of the given model.
     *
     * @param  mixed  $results
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function map($results, $model)
    {
        return Collection::make();
    }

    /**
     * Get the total count from a raw result returned by the engine.
     *
     * @param  mixed  $results
     * @return int
     */
    public function getTotalCount($results)
    {
        return count($results);
    }
}
