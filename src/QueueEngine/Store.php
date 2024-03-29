<?php

// namespace
namespace Nettools\MassMailing\QueueEngine;



/**
 * Class for storing data about all queues
 */
class Store {
	
	public $root;
	public $queues;
	
	
	
	const SORTORDER_ASC = "asc";
	const SORTORDER_DESC = "desc";
	const SORT_COUNT = 'count';
	const SORT_DATE = 'date';
	const SORT_TITLE = 'title';
	const SORT_STATUS = 'status';
	const SORT_VOLUME = 'volume';

	
	
	
	/**
	 * Constructor
	 * 
	 * @param string $root File path to root folder of mail queue subsystemn
	 */
	public function __construct($root)
	{
		$this->root = $root;
		$this->queues = [];
	}
	
	

	/**
	 * Create a queue
	 *
	 * @param string $title Queue name
	 * @param int $batchCount Items sent per batch call
	 * @return Queue
	 * @throws \Nettools\Mailing\Exception
	 */
	public function createQueue($title, $batchCount = 50)
	{
		$q = Queue::create($title, $this->getParams(), $batchCount);
		$this->queues[$q->id] = $q;
		
		// saving queues
		$this->commit();
		
		return $q;
	}
	
	
	
	/**
	 * Get an indexed array of queue subsystem parameters
	 *
	 * @return string[]
	 */
	public function getParams()
	{
		return [ 'root' => $this->root, 'store' => $this];
	}
	
	
	
	/** 
	 * Commit queues to storage
	 */
	public function commit()
	{
		// serialize queues
		$data = serialize($this->queues);
		$root = $this->root;
		
		$f = fopen("$root/store.serialized", "w");
		fwrite($f, $data);
		fclose($f);
	}
	
	
	
	/**
	 * Remove a queue from the store
	 *
	 * The `delete` method of Queue object must have been called to clear queue storage
	 *
	 * @param Queue $queue
	 */
	public function removeQueue(Queue $queue)
	{
		unset($this->queues[$queue->id]);
		$this->commit();
	}
	
	
	
	/**
	 * Empty all store from its queues
	 */
	public function clear()
	{
		// get a copy of array keys (queue ids), as the queues property will be updated during loop
		$keys = array_keys($this->queues);
		
		foreach ( $keys as $k )
			$this->queues[$k]->delete();
	}
	
	
	
	/**
	 * Get a queue
	 *
	 * @param string $id Queue id
	 * @throws \Nettools\Mailing\Exception
	 */
	public function getQueue($id)
	{
		if ( array_key_exists($id, $this->queues) )
			return $this->queues[$id];
		else
			throw new \Nettools\Mailing\Exception("Queue id '$id' does not exist in store");
	}
	
	
	
	/**
	 * Listing queues
	 *
     * @param string $sort One of the SORT_xxx constant defined here 
     * @param string $sortorder One of the SORTORDER_xxx constant defined here
     * @return Queue[] Returns an indexed array of Queue objects (indexes are queue ids)
	 */
	public function getList($sort, $sortorder = self::SORTORDER_ASC)
	{
		// create a copy of queues array
		$ret = $this->queues;
				
		$inf = ($sortorder == self::SORTORDER_ASC ) ? '-1':'1';
		$sup = ($sortorder == self::SORTORDER_ASC ) ? '1':'-1';
		
		
		// if sorting on an existing property
		if ( $sort != self::SORT_STATUS )
			$fun = function($a, $b) use ($inf, $sup, $sort)
				{
					if ( $a->$sort < $b->$sort ) 
						return $inf;
					else if ( $a->$sort == $b->$sort ) 
						return 0;  
					else return $sup;
				};
		else
			// if sorting on the status
			$fun = function($a, $b) use ($inf, $sup)
				{
					$st_a = $a->count - $a->sendOffset;
					$st_b = $b->count - $b->sendOffset;
					if ( $st_a > $st_b ) 
						return $inf;
					else if ( $st_a == $st_b ) 
						return 0;
					else
						return $sup;
				};
		
		
		// sort array and return
		uasort($ret, $fun);		
		return $ret;
	}
	
	
	
	/** 
	 * Read queues from storage
	 *
	 * @param string $root File path to root folder of mail queue subsystem
	 * @param bool $create May create the store if it does exist yes
	 * @return Store
	 */
	static function read($root, $create = false)
	{
		$store = new Store($root);

		if ( !file_exists("$root/store.serialized") )
			if ( $create )
				$store->commit();
			else
				throw new \Nettools\Mailing\Exception ('Store file for queues subsystem does not exist');

		
		// unserialize queues
		$store->queues = unserialize(file_get_contents("$root/store.serialized"));	
		
		
		// resetting params of queue subsystem
		$params = $store->getParams();
		foreach ( $store->queues as $q )
			$q->setup($params);
		
		return $store;
	}
}

?>