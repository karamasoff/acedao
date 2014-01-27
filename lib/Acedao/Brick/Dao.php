<?php
namespace Acedao\Brick;


use Acedao\Exception\MissingKeyException;

trait Dao {

    public $filters;

    public function loadFilters() {
        $this->filters = $this->defineFilters();
    }

    public function defineFilters() {
        return array();
    }

    public function getFilters($key = null) {
        if ($key === null) {
            return $this->filters;
        }

        if (!isset($this->filters[$key])) {
            throw new MissingKeyException(sprintf("The key you gave (%s) is not defined in this object filters.", $key));
        }

        return $this->filters[$key];
    }
} 