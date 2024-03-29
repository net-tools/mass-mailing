<?php

// namespace
namespace Nettools\MassMailing\QueueEngine;



/**
 * Class to store email data of a queue
 */
class MailContent {
	
	public $content;
	
	public $queue;
	public $index;
	
	
	
	/**
	 * Constructor
	 *
	 * @param Queue $q Queue object the email content is related to
	 * @param int $index Index of email content in the queue
	 */
	public function __construct(Queue $q, $index)
	{
		$this->queue = $q;
		$this->index = $index;
	}
	
	
	
	/** 
	 * Set properties 
	 *
	 * @param string $content
	 */
	public function from($content)
	{
		$this->content = $content;
	}
	
	
	
	/**
	 * Read a email content file at index for a given queue
	 * 
	 * @param Queue $q
	 * @param int $index
	 * @param bool $throwErrorIfMissingFiles
	 * @return NULL|MailContent Returns a MailContent object or NULL if not found and $throwErrorIfMissingFiles equals to False
     * @throws \Nettools\Mailing\Exception
	 */
	static function read(Queue $q, $index, $throwErrorIfMissingFiles = false)
	{
		$root = $q->root;
		$qid = $q->id;
		
		if ( file_exists("$root/$qid/$qid.$index.mail") && ($content = file_get_contents("$root/$qid/$qid.$index.mail")) )
		{
			$c = new MailContent($q, $index);
			$c->from($content);
			return $c;
		}
		
		
		if ( $throwErrorIfMissingFiles )
			throw new \Nettools\Mailing\Exception ("No email content at index $index of queue '$q->title'");
		else
			return NULL;
	}
	
	
	
	/**
	 * Write email content to file
	 */
	function write()
	{
		$root = $this->queue->root;
		$qid = $this->queue->id;
		$index = $this->index;
		
		$f = fopen("$root/$qid/$qid.$index.mail", "w");
		fwrite($f, $this->content);
		fclose($f);
	}
	
}

?>