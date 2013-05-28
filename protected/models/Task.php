<?php 

class Task extends EMongoDocument {
	
	public $start;
	public $finish;
	public $log = array();
	public $status;

    /**
     * Check if the current loop doesn't exceed maximum execution time
     * @return bool
     */
    public function takesTooLong() {
        $result = false;
        $maxExec = ((int) ini_get('max_execution_time') - 10) * 1000;
        if (self::getTimestamp() > $this->start + $maxExec) {
            array_push($this->log, $this->formatLogString('Maximum execution time exceeded'));
            $this->status = 'Finished with errrors';
            $result = true;
        }
        return $result;
    }

	/**
	 * Return UNIX timestamp in milliseconds (for compatibility with JavaScript)
	 * @param string $time
     * @return int
	 */
	public static function getTimestamp($time = null) {
		if (is_null($time)) {
			$time = microtime(true);
		}
		return round($time * 1000);		
	}
	
	public function setStart($time = NULL) {
		$this->start = self::getTimestamp($time);
	}
	
	public function setFinish($time = NULL) {
		$this->finish = self::getTimestamp($time);
	}

    // return maximum execution time
    /*public function getMaxExec() {
        return ((int) ini_get('max_execution_time') - 10) * 1000;
    }*/
	
	public function formatLogString($str) {
		return date(DATE_RSS) . ': ' . $str;
	}
	
	public function getCollectionName() {
		return 'task';
	}
	
	public static function model($classname = __CLASS__) {
		return parent::model($classname);
	}

}
?>