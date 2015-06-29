<?php

namespace Marriage\Domain\Repository;

trait InMemoryRepositoriesTrait {
    protected $data = array();

    /**
     * {@inheritDoc}
     */
    public function save($model) // ReadModelInterface --- would be, but since in memory... /// getId
    {
        $this->data[(string) $model->getAggregateRootId()] = $model;
    }  
    /**
     * {@inheritDoc}
     */
    public function transferTo($otherRepository) // TransferableInterface RepositoryInterface
    {
        foreach ($this->data as $model) {
            $otherRepository->save($model);
        }
    }
}
