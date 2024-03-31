<?php

// namespace
namespace Nettools\MassMailing\MailingEngine;





/**
 * Helper class to define queue target with fluent interface
 */
class Queue
{
	public $name = NULL;
	public $root = NULL;
	public $batchCount = \Nettools\MassMailing\QueueEngine\Queue::DEFAULT_BATCH_COUNT;
	

	
	
	/**
	 * Constructor
	 *
	 * @param string $name Queue name
	 * @param string $root Path to root directory for queue storage
	 */
	function __construct($name, $root)
	{
		$this->name = $name;
		$this->root = $root;
	}
	
	
	
	/** 
	 * Define batch count for queue
	 *
	 * @param int $count
	 * @return Queue Returns $this for chaining calls
	 */
	 function batchCount($count)
	 {
		 $this->batchCount = $count;
		 return $this;
	 }
}

?>