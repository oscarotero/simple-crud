<?php
namespace SimpleCrud\Queries;

/**
 * Common function to manage LIMIT clause
 */
trait LimitTrait
{
    protected $limit;
    protected $offset;

    /**
     * Adds a LIMIT clause
     *
     * @param integer $limit
     *
     * @return self
     */
    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Adds an offset to the LIMIT clause
     *
     * @param integer $offset
     *
     * @return self
     */
    public function offset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Generate LIMIT clause
     * 
     * @return string
     */
    protected function limitToString()
    {
        if (!empty($this->limit)) {
            $query = ' LIMIT';

            if (!empty($this->offset)) {
                $query .= ' '.$this->offset.',';
            }

            return $query.' '.$this->limit;
        }

        return '';
    }
}
