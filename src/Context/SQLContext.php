<?php

namespace Genesis\SQLExtension\Context;

use Behat\Behat\Context\Step\Given;

/*
 * This file is part of the Behat\SQLExtension
 *
 * (c) Abdul Wahab Qureshi <its.inevitable@hotmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * SQL Context 
 *
 * @author Abdul Wahab Qureshi <its.inevitable@hotmail.com>
 */
class SQLContext extends SQLHandler
{
    /**
     * @Given /^I have an? "([^"]*)" where "([^"]*)"$/
     * @Given /^I have an? "([^"]*)" with "([^"]*)"$/
     */
    public function iHaveAWhere($entity, $columns)
    {
        $this->handleParam($columns);
        list($columnNames, $columnValues) = $this->getTableColumns($entity);

        $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $entity, $columnNames, $columnValues);
        $result = $this->execute($sql, self::IGNORE_DUPLICATE);
        
        // Extract duplicate key and run update using it
        if($key = $this->getKeyFromError($result)) {
            return $this->iHaveAnExistingWithWhere(
                $entity, 
                sprintf('%s:%s',$key, $this->columns[$key]), 
                $columns
            );
        }

        return $this;
    }

    /**
     * @Given /^I dont have an? "([^"]*)" where "([^"]*)"$/
     * @Given /^I dont have an? "([^"]*)" with "([^"]*)"$/
     */
    public function iDontHaveAWhere($entity, $columns)
    {
        if(! $columns) {
            throw new \Exception('You must provide a where clause!');
        }

        $this->handleParam($columns);
        $whereClause = $this->constructClause(' AND ', $this->columns);

        $sql = sprintf('DELETE FROM %s WHERE %s', $entity, $whereClause);
        $this->execute($sql);

        return $this;
    }

    /**
     * @Given /^I have an existing "([^"]*)" with "([^"]*)" where "([^"]*)"$/
     */
    public function iHaveAnExistingWithWhere($entity, $with, $columns)
    {
        if(! $columns) {
            throw new \Exception('You must provide a where clause!');
        }

        $this->handleParam($with);
        $updateClause = $this->constructClause(', ', $this->columns);
        $this->handleParam($columns);
        $whereClause = $this->constructClause(' AND ', $this->columns);

        $sql = sprintf('UPDATE %s SET %s WHERE %s', $entity, $updateClause, $whereClause);
        $this->execute($sql, self::IGNORE_DUPLICATE);
    }
}
