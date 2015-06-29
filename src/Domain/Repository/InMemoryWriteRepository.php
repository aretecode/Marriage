<?php

namespace Marriage\Domain\Repository;

class InMemoryWriteRepository 
{
    use InMemoryRepositoriesTrait;

    /**
     * {@inheritDoc}
     */
    public function remove($id)
    {
        unset($this->data[(string) $id]);
    }
}
