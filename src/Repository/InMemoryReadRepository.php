<?php

namespace Marriage\Domain\Repository;

use Marriage\Domain\Specification\Specification;

class InMemoryReadRepository 
{
    use InMemoryRepositoriesTrait;

    /**
     * {@inheritDoc}
     */
    public function find($id)
    {
        $id = (string) $id;
        if (isset($this->data[$id])) {
            return $this->data[$id];
        }
        return null;
    }
    /**
     * {@inheritDoc}
     */
    public function findBy(array $fields)
    {
        if (! $fields) {
            return array();
        }
        return array_values(array_filter($this->data, function ($model) use ($fields) {
            foreach ($fields as $field => $value) {
                $getter = 'get' . ucfirst($field);
                $modelValue = $model->$getter();
                if (is_array($modelValue) && ! in_array($value, $modelValue)) {
                    return false;
                } elseif (! is_array($modelValue) && $modelValue !== $value) {
                    return false;
                }
            }
            return true;
        }));
    }
    /**
     * {@inheritDoc}
     */
    public function findAll()
    {
        return array_values($this->data);
    }
    /**
     * 
     * can be 0+, just duplicate to return a single one that is not an array and rename this `findAllSatisfying`
     * @return array<Object|empty>
     *
     * @param Specification $specification
     */
    public function findSatisfying($specification) {
        $data = $this->findAll();
        $result = array();
        foreach ($data as $object) {
            if ($specification->isSatisfiedBy($object)) {
                $result[] = $object;
            }
        }  
        return $result;
    }
}
