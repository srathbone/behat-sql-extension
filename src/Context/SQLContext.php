<?php

namespace Genesis\SQLExtension\Context;

use Behat\Behat\Context\Step\Given;
use Behat\Gherkin\Node\TableNode;
use Exception;

/*
 * This file is part of the Behat\SQLExtension
 *
 * (c) Abdul Wahab Qureshi <its.inevitable@hotmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * SQL Context.
 *
 * @author Abdul Wahab Qureshi <its.inevitable@hotmail.com>
 */
class SQLContext extends SQLHandler implements Interfaces\SQLContextInterface
{
    /**
     * @Given /^(?:|I )have(?:| an| a) "([^"]*)" where:$/
     */
    public function iHaveWhere($entity, TableNode $nodes)
    {
        $queries = $this->convertTableNodeToQueries($nodes);
        $sqls = [];

        foreach ($queries as $query) {
            $sqls[] = $this->iHaveAWhere($entity, $query);
        }

        return $sqls;
    }

    /**
     * @Given /^(?:|I )have:$/
     */
    public function iHave(TableNode $nodes)
    {
        $nodes = $nodes->getRows();
        unset($nodes[0]);
        $sqls = [];

        // Loop through all nodes and try inserting values.
        foreach ($nodes as $node) {
            $sqls[] = $this->iHaveAWhere($node[0], $node[1]);
        }

        return $sqls;
    }

    /**
     * @Given /^(?:|I )have(?:| an| a) "([^"]*)" where "([^"]*)"$/
     * @Given /^(?:|I )have(?:| an| a) "([^"]*)" with "([^"]*)"$/
     */
    public function iHaveAWhere($entity, $columns)
    {
        $this->debugLog('------- I HAVE WHERE -------');
        $this->debugLog('Trying to select existing record.');

        // Normalize data.
        $this->setEntity($entity);
        $columns = $this->resolveQuery($columns);

        // $this->debugLog('No record found, trying to insert.');
        $this->setCommandType('insert');

        // If the record does not already exist, create it.
        list($columnNames, $columnValues) = $this->getTableColumns($this->getEntity(), $columns);

        // Build up the sql.
        $sql = "INSERT INTO {$this->getEntity()} ({$columnNames}) VALUES ({$columnValues})";
        $statement = $this->execute($sql);

        // Throw exception if no rows were effected.
        $this->throwErrorIfNoRowsAffected($statement, self::IGNORE_DUPLICATE);
        $this->setKeywordsFromId($this->getLastId());

        $this->get('dbManager')->closeStatement($statement);

        return $sql;
    }

    /**
     * User friendly version of iHaveAWith.
     *
     * @param $table The table to insert into.
     * @param $values Values to insert.
     *
     * @return string
     */
    public function insert($table, $values)
    {
        return $this->iHaveAWhere($table, $values);
    }

    /**
     * @Given /^(?:|I )don't have(?:| an| a) "([^"]*)" where "([^"]*)"$/
     * @Given /^(?:|I )don't have(?:| an| a) "([^"]*)" with "([^"]*)"$/
     * @Given /^(?:|I )do not have(?:| an| a) "([^"]*)" where "([^"]*)"$/
     */
    public function iDontHaveAWhere($entity, $columns)
    {
        $this->debugLog('------- I DONT HAVE WHERE -------');

        if (! $columns) {
            throw new Exception('You must provide a where clause!');
        }

        $this->setEntity($entity);
        $this->setCommandType('delete');

        $whereClause = $this->resolveQueryToSQLClause($this->getCommandType(), $columns);

        // Construct the delete statement.
        $sql = "DELETE FROM {$this->getEntity()} WHERE {$whereClause}";

        // Execute statement.
        $statement = $this->execute($sql);

        // Throw an exception if errors are found.
        $this->throwExceptionIfErrors($statement);
        $this->get('dbManager')->closeStatement($statement);

        return $sql;
    }

    /**
     * @Given /^(?:|I )don't have:$/
     * @Given /^(?:|I )do not have:$/
     */
    public function iDontHave(TableNode $nodes)
    {
        // Get all table node rows.
        $nodes = $nodes->getRows();

        // Get rid of first row as its just for readability.
        unset($nodes[0]);
        $sqls = [];

        // Loop through all nodes and try inserting values.
        foreach ($nodes as $node) {
            $sqls[] = $this->iDontHaveAWhere($node[0], $node[1]);
        }

        return $sqls;
    }

    /**
     * @Given /^(?:|I )do not have(?:| an| a) "([^"]*)" where:$/
     */
    public function iDontHaveWhere($entity, TableNode $nodes)
    {
        // Convert table node to parse able string.
        $queries = $this->convertTableNodeToQueries($nodes);
        $sqls = [];

        // Run through the dontHave step definition for each query.
        foreach ($queries as $query) {
            $sqls[] = $this->iDontHaveAWhere($entity, $query);
        }

        return $sqls;
    }

    /**
     * User friendly version of iDontHaveAWhere.
     *
     * @param string $table The table to delete from.
     * @param string $where The where clause.
     *
     * @return string
     */
    public function delete($table, $where)
    {
        return $this->iDontHaveAWhere($table, $where);
    }

    /**
     * @Given /^(?:|I )have an existing "([^"]*)" with "([^"]*)" where "([^"]*)"$/
     */
    public function iHaveAnExistingWithWhere($entity, $with, $columns)
    {
        $this->debugLog('------- I HAVE AN EXISTING WITH WHERE -------');

        if (! $columns) {
            throw new Exception('You must provide a where clause!');
        }

        $this->setEntity($entity);
        $this->setCommandType('update');

        // Build up the update clause.
        $with = $this->resolveQuery($with);
        $updateClause = $this->constructSQLClause($this->getCommandType(), ', ', $with);

        $whereClause = $this->resolveQueryToSQLClause($this->getCommandType(), $columns);

        // Build up the update statement.
        $sql = "UPDATE {$this->getEntity()} SET {$updateClause} WHERE {$whereClause}";

        // Execute statement.
        $statement = $this->execute($sql);

        // If no exception is throw, save the last id.
        $this->setKeywordsFromCriteria(
            $this->getEntity(),
            $whereClause
        );

        $this->get('dbManager')->closeStatement($statement);

        return $sql;
    }

    /**
     * User friendly version of iHaveAnExistingWithWhere.
     *
     * @param string $table The table to delete from.
     * @param string $where The where clause.
     *
     * @return string
     */
    public function update($table, $update, $where)
    {
        return $this->iHaveAnExistingWithWhere($table, $update, $where);
    }

    /**
     * @Given /^(?:|I )have(?:| an| a) existing "([^"]*)" where "([^"]*)"$/
     */
    public function iHaveAnExistingWhere($entity, $where)
    {
        $this->debugLog('------- I HAVE AN EXISTING WHERE -------');

        $this->setEntity($entity);
        $this->setCommandType('select');

        $selectWhereClause = $this->resolveQueryToSQLClause($this->getCommandType(), $where);

        // Execute sql for setting last id.
        return $this->setKeywordsFromCriteria(
            $this->getEntity(),
            $selectWhereClause
        );
    }

    /**
     * User friendly version of iHaveAnExistingWhere.
     *
     * @param string $table The table to delete from.
     * @param string $where The where clause.
     *
     * @return string
     */
    public function select($table, $where)
    {
        return $this->iHaveAnExistingWhere($table, $where);
    }

    /**
     * @Then /^(?:|I )should have(?:| an| a) "([^"]*)" with:$/
     */
    public function iShouldHaveAWithTable($entity, TableNode $with)
    {
        // Convert the table node to parse able string.
        $clause = $this->convertTableNodeToSingleContextClause($with);

        // Run through the shouldHaveWith step definition.
        $sql = $this->iShouldHaveAWith($entity, $clause);

        return $sql;
    }

    /**
     * @Then /^(?:|I )should have(?:| an| a) "([^"]*)" with "([^"]*)"(?:| in the database)$/
     */
    public function iShouldHaveAWith($entity, $with)
    {
        $this->debugLog('------- I SHOULD HAVE A WITH -------');
        $this->setEntity($entity);

        // Set the clause type.
        $this->setCommandType('select');

        $selectWhereClause = $this->resolveQueryToSQLClause($this->getCommandType(), $with);

        // Create the sql to be inserted.
        $sql = "SELECT * FROM {$this->getEntity()} WHERE {$selectWhereClause}";

        // Execute the sql query, if the query throws a generic not found error,
        // catch it and give it some context.
        $statement = $this->execute($sql);
        if (! $this->hasFetchedRows($statement)) {
            throw new Exceptions\RecordNotFoundException(
                $selectWhereClause,
                $this->getEntity()
            );
        }

        $this->get('dbManager')->closeStatement($statement);

        return $sql;
    }

    /**
     * @Then /^(?:|I )should not have(?:| an| a) "([^"]*)" with "([^"]*)"(?:| in the database)$/
     */
    public function iShouldNotHaveAWith($entity, $with)
    {
        $this->debugLog('------- I SHOULD NOT HAVE A WHERE -------');

        $this->setEntity($entity);

        // Set clause type.
        $this->setCommandType('select');

        $selectWhereClause = $this->resolveQueryToSQLClause($this->getCommandType(), $with);

        // Create the sql to be inserted.
        $sql = "SELECT * FROM {$this->getEntity()} WHERE {$selectWhereClause}";

        // Execute the sql query, if the query throws a generic not found error,
        // catch it and give it some context.
        $statement = $this->execute($sql);

        if ($this->hasFetchedRows($statement)) {
            throw new Exceptions\RecordFoundException(
                $selectWhereClause,
                $this->getEntity()
            );
        }

        $this->get('dbManager')->closeStatement($statement);

        return $sql;
    }

    /**
     * @Then /^(?:|I )should not have(?:| an| a) "([^"]*)" with:$/
     */
    public function iShouldNotHaveAWithTable($entity, TableNode $with)
    {
        // Convert the table node to parse able string.
        $clause = $this->convertTableNodeToSingleContextClause($with);

        // Run through the shouldNotHave step definition.
        $sql = $this->iShouldNotHaveAWith($entity, $clause);

        return $sql;
    }

    /**
     * @Given /^(?:|I )save the id as "([^"]*)"$/
     */
    public function iSaveTheIdAs($key)
    {
        $this->debugLog('------- I SAVE THE ID -------');

        $this->setKeyword($key, $this->getLastId());

        return $this;
    }

    /**
     * @Given /^(?:|I )am in debug mode$/
     */
    public function iAmInDebugMode()
    {
        $this->debugLog('------- I AM IN DEBUG MODE -------');

        if (! defined('DEBUG_MODE')) {
            define('DEBUG_MODE', 1);
        }
    }
}
