<?php
abstract class Migration_Abstract
{
    protected
        $_sql = array();

    protected function _addSql($sql)
    {
        if($sql) $this->_sql[] = $sql;
        return $this;
    }

    protected function _clearSql()
    {
        $this->_sql = array();
        return $this;
    }


    public function getSql()
    {
        return $this->_sql;
    }

    abstract public function up();
    abstract public function down();

    public function getUpSql()
    {
        $this->_clearSql();
        $this->up();
        return $this->getSql();
    }

    public function getDownSql()
    {
        $this->_clearSql();
        $this->down();
        return $this->getSql();
    }

    public function isSkip()
    {
        return false;
    }

    public function getDescription()
    {
        return '';
    }

}