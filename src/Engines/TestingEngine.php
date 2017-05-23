<?php

namespace PatOui\Scout\Engines;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;

class TestingEngine extends Engine
{
    public $filesystem;

    /**
     * constructor
     *
     * @param Filesystem $filesystem
     * @param config $config
     */
    public function __construct(Filesystem $filesystem, $config)
    {
        $this->filesystem = $filesystem;
        $this->config = $config;
    }

    /**
     * Remove the given model from the index.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $models
     * @return void
     */
    public function delete($models)
    {
        $data = $this->getFile(true);
        $models->each(function ($model) use ($data) {
            if (property_exists($model, 'id')) {
                $index = array_search($model->id, $data);
                if ($index !== false) {
                    unset($data[$index]);
                }
            }
        });
    }

    /**
     * Get file contents
     * @param boolean $toArray determine whether or
     * not to return data as an array
     * @return string|array
     */
    public function getFile($toArray = false)
    {
        $file = $this->getStoragePath();

        // Verify file exists
        if (! $this->filesystem->exists($file)) {
            throw new \Exception("File at specified storage '$file' does not exist");
        }

        // Verify file is readable
        if (! $this->filesystem->isReadable($file)) {
            throw new \Exception("File at storage specified '$file' is not writable");
        }

        // Get file contents
        $data = $this->filesystem->get($this->getStoragePath());

        // Check whether to return string or array
        $data = $toArray ? json_decode($data, true) : $data;

        return $data;
    }

    private function getStoragePath()
    {
        $config = $this->config;

        if (! isset($config['testing'])) {
            throw new \Exception("Config for 'testing' is not set");
        }

        if (! isset($config['testing']['storage'])) {
            throw new \Exception("Config for 'testing.storage' is not set");
        }

        return $config['testing']['storage'];
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

    /**
     * Check that string is valid json
     *
     * @param $string String to check for valid json
     * @return boolean
     */
    private function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
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
        if (count($results['hits']) === 0) {
            return Collection::make();
        }

        $keys = collect($results['hits'])
                        ->pluck('id')->values()->all();

        $models = $model->whereIn(
            $model->getQualifiedKeyName(), $keys
        )->get()->keyBy($model->getKeyName());

        return Collection::make($results['hits'])->map(function ($hit) use ($model, $models) {
            $key = $hit['id'];

            if (isset($models[$key])) {
                return $models[$key];
            }
        })->filter();
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
     * Update the given model in the index.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $models
     * @return void
     */
    public function update($models)
    {
        $this->updateFile($models->keyBy('id')->toArray());
    }

    /**
     * Update file with data
     *
     * @param $data array|string Variable containing data to update file with
     * @return boolean
     */
    private function updateFile($data)
    {
        $file = $this->getStoragePath();

        // Check if file/directory is writable
        if ($this->filesystem->isWritable($file)) {
            throw new \Exception("File at storage specified '$file' is not writable");
        }

        if (is_array($data)) {
            // Convert array to json
            $data = json_encode($data);
        } elseif (is_string($data)) {
            // Check string is valid json
            if ($this->isJson($data) === false) {
                throw new \Exception("Invalid json string passed");
            }
        } else {
            // Invalid data type passed, throw exception
            throw new \Exception("Invalid type of data passed, must be array or string");
        }

        // If file exists, get contents and merge with new data
        if ($this->filesystem->exists($file)) {
            $existingData = $this->getFile(true);
            $data = json_encode(json_decode($data, true) + $existingData);
        }

        // Write data to file
        if ($this->filesystem->put($file, $data) === false) {
            throw new \Exception("Error occurred while writing to '$file'");
        }

        return true;
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

        // Input
        $query = $builder->query;

        // No shortest distance found, yet
        $shortest = -1;

        // Get data from file
        $data = $this->getFile(true);

        // loop through words to find the closest
        foreach ($data as $index => $current) {

            // Filter for scalar values only
            $temp = array_filter((array) $current, function ($value) {
                return is_scalar($value);
            });
            // Filter only searchable items
            if (! empty($current->searchable)) {
                $temp = Arr::only($temp, $current['searchable']);
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
            if ($a['lev'] == $b['lev']) {
                return 0;
            }
            return ($a['lev'] < $b['lev']) ? -1 : 1;
        });

        return [
            'hits' => $results
        ];
    }
}
