<?php
namespace Driver\Base;

abstract class Generator
{
    protected
        $_dump = null,
        $_firstDataSource = null,
        $_secondDataSource = null;

    public function getDiff()
    {
        $after = $this->_convertArray($this->_getFirstDataSource());
        $before = $this->_convertArray($this->_getSecondDataSource());

        $beforeKeyDiff = array_diff_key($before, $after);
        $afterKeyDiff = array_diff_key($after, $before);

        if ($beforeKeyDiff == $afterKeyDiff) return;

        return $this->_generateSql($afterKeyDiff, $beforeKeyDiff);

    }

    /**
     * @return null
     */
    protected function _getFirstDataSource()
    {
        if (!$this->_firstDataSource) throw new \Exception('First data source not set');
        return $this->_firstDataSource;
    }

    /**
     * @param Generator $firstDataSource
     */
    public function setFirstDataSource(array $firstDataSource)
    {
        $this->_firstDataSource = $firstDataSource;
        return $this;
    }

    /**
     * @return null
     */
    protected function _getSecondDataSource()
    {
        if (!$this->_secondDataSource) throw new \Exception('Second data source not set');
        return $this->_secondDataSource;
    }

    /**
     * @param Generator $secondDataSource
     */
    public function setSecondDataSource(array $secondDataSource)
    {
        $this->_secondDataSource = $secondDataSource;
        return $this;
    }

}