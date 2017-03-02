<?php
/**
 * Created by PhpStorm.
 * User: vjvan
 * Date: 1/27/2017
 * Time: 11:16
 */
namespace Text\Processing\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\ResultSet\ResultSetInterface;
use Zend\Db\Sql\Sql;

class TextsTableGateway extends TableGateway
{
    public function __construct($table, AdapterInterface $adapter, $features = null, ResultSetInterface $resultSetPrototype = null, Sql $sql = null)
    {
        parent::__construct($table, $adapter, $features, $resultSetPrototype, $sql);
    }

    /**
     * Extend parent function by allowing the use of Select object for where condition
     *
     * @param  null|\Zend\Db\Sql\Select $where
     * @return null|ResultSetInterface
     */
    public function select($where = null)
    {
        if (!$this->isInitialized) {
            $this->initialize();
        }

        if ($where instanceof \Zend\Db\Sql\Select) {
            $select = $where;
        } else {
            $select = $this->sql->select();
        }

        if ($where instanceof \Closure) {
            $where($select);
        } elseif ($where !== null && ! $where instanceof \Zend\Db\Sql\Select) {
            $select->where($where);
        }

        return $this->selectWith($select);
    }

    public function setTable($table)
    {
        if ($this->table != $table) {
            $newTableGateway = clone $this;
            $newTableGateway->table = $table;
            $newSql = new Sql($this->adapter, $table);
            $newTableGateway->sql = $newSql;
        }
        return $newTableGateway;
    }
    
    /**
     * Extend parent function by allowing the table to be set after TextsTableGateway has been constructed.
     *
     * @param  array $set
     * @return int
     */
    public function insert($set, $table = null)
    {
        if (!$this->isInitialized) {
            $this->initialize();
        }
        if (!empty($table)) {
            $newTableGateway = $this->setTable($table);
            $insert = $newTableGateway->sql->insert();
            $insert->values($set);

            return $newTableGateway->executeInsert($insert);
        } else {
            $insert = $this->sql->insert();
            $insert->values($set);
            
            return $this->executeInsert($insert);
        }
    }

    /**
     * Extend parent function by allowing the table to be set after TextsTableGateway has been constructed.
     *
     * @param  array      $set   new database values
     * @param  null       $where where condition
     * @param  array|null $joins joins condition
     * @param  null       $table table name
     * @return int
     */
    public function update($set, $where = null, array $joins = null, $table = null)
    {
        if (!$this->isInitialized) {
            $this->initialize();
        }
        if (!empty($table)) {
            $newTableGateway = $this->setTable($table);
            $update = $newTableGateway->sql->update();
        } else {
            $update = $this->sql->update();    
        }

        $update->set($set);
        if ($where !== null) {
            $update->where($where);
        }

        if ($joins) {
            foreach ($joins as $join) {
                $type = isset($join['type']) ? $join['type'] : Join::JOIN_INNER;
                $update->join($join['name'], $join['on'], $type);
            }
        }

        if (!empty($table)) {
            $newTableGateway->executeUpdate($update);
        } else {
            return $this->executeUpdate($update);
        }
    }

    /**
     * Extend parent function by allowing the table to be set after TextsTableGateway has been constructed.
     *
     * @param array|\Closure|string|\Zend\Db\Sql\Where $where   where condition
     * @param null                                     $table   table name
     * @return int
     */
    public function delete($where, $table = null)
    {
        if (!$this->isInitialized) {
            $this->initialize();
        }
        
        if (!empty($table)) {
            $newTableGateway = $this->setTable($table);
            $delete = $newTableGateway->sql->delete();
        } else {
            $delete = $this->sql->delete();
        }
        if ($where instanceof \Closure) {
            $where($delete);
        } else {
            $delete->where($where);
        }
        
        if (!empty($table)) {
            $newTableGateway->executeDelete($delete);
        } else {
            return $this->executeDelete($delete);
        }
    }
}