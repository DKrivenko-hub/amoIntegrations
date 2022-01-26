<?php


namespace AmoIntegrations\Interfaces;

interface IQueryable
{
    public function getSelectQuery();

    public function getAddQuery();

    public function getInsertQuery();

    public function getDeleteQuery();
}
