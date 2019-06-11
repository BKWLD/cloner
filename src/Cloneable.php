<?php

namespace Bkwld\Cloner;

use App;
use Illuminate\Support\Str;

/**
 * Mixin accessor methods, callbacks, and the duplicate() helper into models.
 */
trait Cloneable
{
    /**
     * Return the list of attributes on this model that should be cloned.
     *
     * @return array
     */
    public function getCloneExemptAttributes()
    {
        // Always make the id and timestamps exempt
        $defaults = [
            $this->getKeyName(),
            $this->getCreatedAtColumn(),
            $this->getUpdatedAtColumn(),
        ];

        // Include the relationship count columns in the exempt columns
        $count_columns = [];
        
        foreach ($this->withCount as $name => $constraints) {
            
            if (is_numeric($name)) {
                $name = $constraints;
            }
            
            $segments = explode(' ', $name);
            unset($alias);
            
            if (count($segments) === 3 && Str::lower($segments[1]) === 'as') {
                [$name, $alias] = [$segments[0], $segments[2]];
            }
            
            $count_columns[] = $alias ?? Str::snake($name.'_count');
        }

        $defaults = array_merge($defaults, $count_columns);

        // It none specified, just return the defaults, else, merge them
        if (!isset($this->clone_exempt_attributes)) {
            return $defaults;
        }

        return array_merge($defaults, $this->clone_exempt_attributes);
    }

    /**
     * Return a list of attributes that reference files that should be duplicated
     * when the model is cloned.
     *
     * @return array
     */
    public function getCloneableFileAttributes()
    {
        if (!isset($this->cloneable_file_attributes)) {
            return [];
        }

        return $this->cloneable_file_attributes;
    }

    /**
     * Return the list of relations on this model that should be cloned.
     *
     * @return array
     */
    public function getCloneableRelations()
    {
        if (!isset($this->cloneable_relations)) {
            return [];
        }

        return $this->cloneable_relations;
    }

    /**
     * Add a relation to cloneable_relations uniquely.
     *
     * @param string $relation
     *
     * @return void
     */
    public function addCloneableRelation(string $relation)
    {
        $relations = $this->getCloneableRelations();
        
        if (in_array($relation, $relations)) {
            return;
        }
        
        $relations[] = $relation;
        $this->cloneable_relations = $relations;
    }

    /**
     * Clone the current model instance.
     *
     * @return Illuminate\Database\Eloquent\Model The new, saved clone
     */
    public function duplicate()
    {
        return App::make('cloner')->duplicate($this);
    }

    /**
     * Clone the current model instance to a specific Laravel database connection.
     *
     * @param string $connection A Laravel database connection
     *
     * @return Illuminate\Database\Eloquent\Model The new, saved clone
     */
    public function duplicateTo(string $connection)
    {
        return App::make('cloner')->duplicateTo($this, $connection);
    }

    /**
     * A no-op callback that gets fired when a model is cloning but before it gets
     * committed to the database.
     *
     * @param Illuminate\Database\Eloquent\Model $src
     * @param bool                               $child
     *
     * @return void
     */
    public function onCloning($src, $child = false)
    {
        //
    }

    /**
     * A no-op callback that gets fired when a model is cloned and saved to the
     * database.
     *
     * @param Illuminate\Database\Eloquent\Model $src
     *
     * @return void
     */
    public function onCloned($src)
    {
        //
    }
}
