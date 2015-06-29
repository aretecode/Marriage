<?php

namespace Marriage\Domain\Repository;

class InMemoryRepository 
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
    /**
     * {@inheritDoc}
     */
    public function remove($id)
    {
        unset($this->data[(string) $id]);
    }
}
