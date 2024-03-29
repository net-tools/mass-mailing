<?php

// namespace
namespace Nettools\MassMailing\QueueEngine;



/**
 * Class to store email data of a queue
 */
class Data {
	
	public $to;
	public $headers;
	public $subject;
	public $status;
	
	public $queue;
	public $index;
	const PROPERTIES = ['to', 'headers', 'subject', 'status'];
	
	const STATUS_TOSEND = -1;
	const STATUS_SENT = 0;
	const STATUS_ERROR = 1;
	
	
	/**
	 * Constructor
	 *
	 * @param Queue $q Queue object the data is related to
	 * @param int $index Index of email data in the queue
	 */
	public function __construct(Queue $q, $index)
	{
		$this->queue = $q;
		$this->index = $index;
	}
	
	
	
	/** 
	 * Set data properties from an object
	 *
	 * @param object $data
	 */
	public function from(object $data)
	{
		// copy each required property from argument to the object
		foreach ( self::PROPERTIES as $p )
			$this->$p = $data->$p;
	}
	
	
	
	/**
	 * Read a data file at index for a given queue
	 * 
	 * @param Queue $q
	 * @param int $index
	 * @param bool $throwErrorIfMissingFiles
	 * @return NULL|Data Returns a Data object or NULL if not found and $throwErrorIfMissingFiles equals to False
     * @throws \Nettools\Mailing\Exception
	 */
	static function read(Queue $q, $index, $throwErrorIfMissingFiles = false)
	{
		$root = $q->root;
		$qid = $q->id;
		
		
		if ( file_exists("$root/$qid/$qid.$index.data") && ($data = file_get_contents("$root/$qid/$qid.$index.data")) )
		{
			// decoding json data
			$data = json_decode($data);
			if ( is_null($data) )
				throw new \Nettools\Mailing\Exception ("Malformed Json at index $index of queue '$q->title'");
			
			
			$d = new Data($q, $index);
			$d->from($data);
			return $d;
		}
		
		
		if ( $throwErrorIfMissingFiles )
			throw new \Nettools\Mailing\Exception ("No data at index $index of queue '$q->title'");
		else
			return NULL;
	}
	
	
	
	/**
	 * Write data file
	 */
	function write()
	{
		$root = $this->queue->root;
		$qid = $this->queue->id;
		$index = $this->index;
		
		$d = [];
		foreach ( self::PROPERTIES as $p )
			$d[$p] = $this->$p;
				
		$f = fopen("$root/$qid/$qid.$index.data", "w");
		fwrite($f, json_encode((object)$d));
		fclose($f);
	}
	
}

?>