<?php

// namespace
namespace Nettools\MassMailing\QueueEngine;


use \Nettools\Mailing\Mailer;
use \Nettools\Mailing\MailerEngine\Headers;





/**
 * Class for storing data about a queue
 */
class Queue {
	
	public $count;
	public $sendOffset;
	public $batchCount;
	public $date;
	public $lastBatchDate;
	public $sendLog;
	public $title;
	public $locked;
	public $volume;
	public $id;
	
	
	// protected members defined by setup method after unserialize wake-up (environment parameters of mail queue subsystem)
	public $root;
	public $store;
	
	
	const DEFAULT_BATCH_COUNT = 50;
	
	
	
	
	/** 
	 * Send an email from the queue ; we may modify the recipient, bcc, and headers
	 *
     * @param Mailer $mailer Mailer instance to send email through
	 * @param int $index 0-index of email to sent in queue
	 * @param string $bcc Optionnal bcc recipient to add
	 * @param string $to Overriding `To` recipient 
     * @param \Nettools\Mailing\MailerEngine\Headers $suppl_headers Optionnal supplementary headers
     * @throws \Nettools\Mailing\Exception
	 */
	private function _sendFromQueue(Mailer $mailer, $index, $bcc = NULL, $to = NULL, ?\Nettools\Mailing\MailerEngine\Headers $suppl_headers = NULL)
	{
		$err = null;
		$root = $this->root;
		$qid = $this->id;
		
		
		// read mail from the queue
		$mail = $this->_mailFromQueue($index);
		
		
		// read headers string 
		$headers = Headers::fromString($mail->data->headers);
		
		// handle bcc 
		if ( !is_null($bcc) )
			$headers->set('Bcc', $bcc);

		// if supplementary headers
		if ( !is_null($suppl_headers) )
			$headers->mergeWith($suppl_headers);

		

		// where is the recipient ? either the TO recipient in the email or the $TO parameter here (if we want to override the recipient)
		$to or $to = $mail->data->to;
		try
		{
			// send the email
			$mailer->sendmail_raw($to, $mail->data->subject, $mail->email->content, $headers);
		}
		catch ( \Nettools\Mailing\Exception $e )
		{
			$err = $e->getMessage();
		}


		
		// set sending status and write config
		$mail->data->status = $err ? Data::STATUS_ERROR : Data::STATUS_SENT;
		$mail->data->write();

		
		if ( $err )
			throw new \Nettools\Mailing\Exception("Can't send email to '$to' (" . $this->title .") : $err");
	}
	
	

	/**
	 * Get an email from queue, and return it's data, headers and email content
	 *
	 * @param int $index 0-index of item in queue
	 * @return object Returns an object with properties `data` and `email` (email headers are in the `data` property), respectively of class Data and MailContent
	 */
	protected function _mailFromQueue($index)
	{
		return (object)[
				'data' => Data::read($this, $index, true),
				'email' => MailContent::read($this, $index, true)
			];
	}
	
		
	
	/**
	 * Add an email to the queue
	 *
	 * @param string $rawmail Mail content as a string
	 * @param object $data Data object with `to`, `subject`, `status` and `headers` properties
	 */
	protected function _push($rawmail, object $data)
	{
		// get ID for this email
		$mid = $this->count;
		$root = $this->root;
		$qid = $this->id;
		
		// write email raw content
		$c = new MailContent($this, $mid);
		$c->from($rawmail);
		$c->write();

		
		// increment queue size stats
		$this->volume += strlen($rawmail);
				
		
		// write headers and data
		$data->headers = Headers::fromString($data->headers)->set('X-MailSenderQueue', $qid)->toString();
		
		$d = new Data($this, $mid);
		$d->from($data);
		$d->write();
		
		
		// increment queue count
		$this->count++;
	}
	
	
	
	
	/**
	 * Magic method to serialize object properties
	 *
	 * @return string[] Returns an array of property names to serialize
	 */
	public function __sleep()
	{
		return ['count', 'sendOffset', 'batchCount', 'date', 'lastBatchDate', 'sendLog', 'title', 'locked', 'volume', 'id'];
	}
	
	
	
	/**
	 * Setup object after its creation or serialization wake up
	 *
	 * @param string[] $params Parameters of queue system
	 */
	public function setup(array $params)
	{
		$this->root = rtrim($params['root'], '/');
		$this->store = array_key_exists('store', $params) ? $params['store'] : null;
	}
	 
	

	/**
	 * Create a new queue
	 *
	 * @param string $name Queue name
	 * @param string[] $params Parameters of queue system
	 * @param int $batchCount Items sent per batch call
	 * @return Queue
	 * @throws \Nettools\Mailing\Exception
	 */
	static function create($name, $params, $batchCount = self::DEFAULT_BATCH_COUNT)
	{
		$q = new Queue();
		$q->count = 0;
		$q->sendOffset = 0;
		$q->date = time();
		$q->lastBatchDate = NULL;
		$q->sendLog = [];
		$q->title = $name;
		$q->batchCount = $batchCount;
		$q->locked = false;
		$q->volume = 0;
		$q->id = uniqid();
		
		
		// take parameters from environement (such as root path)
		$q->setup($params);
		
		// create a sub-folder for this queue
		if ( !file_exists($q->root . "/$q->id") )
			if ( !mkdir($q->root . "/$q->id") )
				throw new \Nettools\Mailing\Exception("Can't create folder for queue '$name'");
		
		
		return $q;
	}
	
	
	
	/**
	 * Rename the queue
	 *
	 * @param string $newName
	 */
	public function rename($newName)
	{
		$this->title = $newName;
		$this->commit();
	}
	
	
	
	/**
	 * Unlock queue
	 */
	public function unlock()
	{
		$this->locked = false;
		$this->commit();
	}
	
	
	
	/**
	 * Clear log
	 */
	public function clearLog()
	{
		$this->sendLog = [];
		$this->commit();
	}
	
	
	
	/**
	 * Commit queue updates
	 */
	public function commit()
	{
		$this->store->commit();
	}
	
	
	
	/**
     * Erase a queue
     */
	function delete()
	{
		$qid = $this->id;
		$root = $this->root;
		
		$files = glob("$root/$qid/$qid.*");
		if ( is_array($files) )
			foreach ( $files as $f )
				unlink($f);
				
		if ( file_exists("$root/$qid") )
			rmdir("$root/$qid");
		
		
		// removing queue from subsystem
		$this->store->removeQueue($this);
	}

	
	
	/** 
	 * Search for an email in the queue
	 *
	 * @param string $recipient Email recipient
	 * @return FALSE|int Returns the index of mail recipient in the queue, or FALSE if not recipient not found
	 * @throws \Nettools\Mailing\Exception
	 */
	public function search($recipient)
	{
		$qid = $this->id;
		$root = $this->root;
		
		for ( $i = 0 ; $i < $this->count ; $i++ )
			// read Data object (don't throw an exception is data file missing)
			if ( $data = Data::read($this, $i, false) )
				if ( $data->to == $recipient )
					return $i;
			
		return FALSE;
	}
	
	
	
	/**
	 * Get Eml file from queue
	 *
	 * @param int $index 0-index of email in the queue
     * @return string Email raw text (headers and content)
	 * @throws \Nettools\Mailing\Exception
     */
	function emlAt($index)
	{
		// read email from queue et get a string for an EML file
		$mail = $this->_mailFromQueue($index);
		
		
		// append to existing headers
		$h = $mail->data->headers ? $mail->data->headers . "\r\n" : '';
		
		$h .=	"To: " . $mail->data->to . "\r\n" .
				"Subject: " . $mail->data->subject;
		
		return $h . "\r\n\r\n" . $mail->email->content;
	}
	
	
	
	/**
     * Push an email to the queue
     * 
     * @param \Nettools\Mailing\MailBuilder\Content $mail Email object
     * @param string $from Email address of the sender
     * @param string $to Email recipient
     * @param string $subject Email subject
     */
	function push(\Nettools\Mailing\MailBuilder\Content $mail, $from, $to, $subject)
	{
		// adding to queue
		$this->_push(
						$mail->getContent(), 
						(object)[ 
							'to'		=> $to, 
							'subject'	=> $subject,
							'status'	=> Data::STATUS_TOSEND,
							'headers'	=> $mail->getAllHeaders()->set('From', $from)->toString()
						]
					);
	}
	
	
	
	/**
     * Push an email string to the queue
     * 
     * @param string $mail Email as string
     * @param string $headers Headers as string (must already include `From` header)
     * @param string $to Email recipient
     * @param string $subject Email subject
     */
	function pushAsString($mail, $headers, $to, $subject)
	{
		// adding to queue
		$this->_push(
						$mail, 
						(object)[ 
							'to'		=> $to, 
							'subject'	=> $subject,
							'status'	=> Data::STATUS_TOSEND,
							'headers'	=> $headers
						]
					);
	}
	
	
	
	/** 
     * Send a batch of email through a Mailer instance, and optionnally add headers
     *
     * @param Mailer $mailer Mailer instance to send email through
     * @param \Nettools\Mailing\MailerEngine\Headers $suppl_headers Optionnal supplementary headers
     * @throws \Nettools\Mailing\Exception
     */
	function send(Mailer $mailer, ?Headers $suppl_headers = NULL)
	{
		// are the emails to sent ?
		if ( $this->sendOffset < $this->count )
		{
			$ret = array();
			
			// handle a batch of emails, until queue end is reached
			$max = min(array($this->sendOffset + $this->batchCount, $this->count));
			for ( $i = $this->sendOffset ; $i < $max ; $i++ )
			{
				try
				{
					$this->_sendFromQueue($mailer, $i, NULL, NULL, $suppl_headers);
				}
				catch ( \Nettools\Mailing\Exception $e )
				{
					$ret[] = $e->getMessage();
				}
			}
			
			
			// increment offset
			$this->sendOffset += min($this->batchCount, $this->count - $this->sendOffset);
			
			// save log
			$this->sendLog = array_merge($this->sendLog, $ret);
			
			// write timestamp for last sent batch
			$this->lastBatchDate = time();
			
			// if all emails in queue have been sent, locking the queue
			if ( $this->sendOffset == $this->count )
				$this->locked = true;

			// saving queue data
			$this->commit();
			
			// check errors
			if ( count($ret) )
				throw new \Nettools\Mailing\Exception("Errors occured during queue processing '$this->title'");
		}
		else
			throw new \Nettools\Mailing\Exception("Queue '$this->title' is empty");
	}
	
	
	
	/** 
     * Send again an email from the queue
     * 
     * @param Mailer $mailer Mailer used for sending the email again
     * @param int $index 0-index of the email to send in the queue
     * @param string|NULL $bcc Recipient in bcc, if necessary
     * @param string|NULL $to Recipient to send the email to, if we want to override the previous recipient
     * @throws \Nettools\Mailing\Exception
     */
	function resend(Mailer $mailer, $index, $bcc = NULL, $to = NULL)
	{
		$this->_sendFromQueue($mailer, $index, $bcc, $to);
	}
	
	
	
	/**
    * Create a new queue with email whose status is error
    * 
    * @param string $title Name of the new queue
    * @return Queue Returns the new queue created
    */
	function newQueueFromErrors($title)
	{
		// creating queue
		$q = $this->store->createQueue($title, $this->batchCount);
		
		
		// check all emails from the queue
		for ( $i = 0 ; $i < $this->count ; $i++ )
		{
			// read data and throw error if file missing
			$data = Data::read($this, $i, true);
			
			// if email was not sent, pushing it to the new queue
			if ( $data->status == Data::STATUS_ERROR )
			{
				$data->status = Data::STATUS_TOSEND;
				
				// pushing mail content and data to new queue (exception if file missing)
				$q->_push(MailContent::read($this, $i, true)->content, $data);
			}
		}
		
		
		// commit all emails pushed
		$q->commit();
		
		
		return $q;
	}
	
	
	
	/**
     * Get recipients for a queue
     *
     * The litteral objects returned in the array have the following properties :
     * - to : recipient
     * - index : 0-index of the email in the source queue
     * - status : one of the Data::STATUS constants
     * 
     * @return object[] Return an array of litteral objects about recipients 
	 * @throws \Nettools\Mailing\Exception
     */
	function recipients()
	{
		$ret = array();
		$root = $this->root;
		$qid = $this->id;
			
			
		for ( $i = 0 ; $i < $this->count ; $i++ )
			if ( $data = Data::read($this, $i, false) )
				$ret[] = (object) array('to'=>$data->to, 'index'=>$i, 'status'=>$data->status);
	
		
		return $ret;
	}
	
	
	
	/**
     * Set an email to error (after it has been sent)
     * 
     * Useful when the email recipient is later reported to be wrong
     *
     * @param int 0-index of the email to set to error status
	 * @throws \Nettools\Mailing\Exception
     */
	function recipientError($index)
	{
		$root = $this->root;
		$qid = $this->id;
		


		// read data (throw exception if not found)
		$data = Data::read($this, $index, true);

		// modify status and write config
		$data->status = Data::STATUS_ERROR;
		$data->write();

		// update log
		$this->sendLog = array_merge($this->sendLog, array("Error for '" . $data->to . "' (" . $this->title . ") : set to Error by user"));
		
		// commit
		$this->commit();
	}
}

?>