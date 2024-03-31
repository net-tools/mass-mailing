<?php

// namespace
namespace Nettools\MassMailing\MailingEngine;


// clauses use
use \Nettools\Mailing\Mailer;





/**
 * Helper class to send several emails
 *
 * Subject, from address, replyto can be set at object construction, then all required customizations are applied to mail objects sent through `send` method.
 * It also makes it possible to send emails to tests recipients or to a queue object, again just by setting appropriate parameters of constructor
 */
class Engine 
{
	protected $_mailer = NULL;
	
	
	
	/**
	 * Constructor
	 *
	 * @param \Nettools\Mailing\Mailer $mailer
     */	
	function __construct(Mailer $mailer)
	{
		$this->_mailer = $mailer;
	}
	
	
	
	/**
	 * Prepare mailing through a Mailing object with fluent design
	 *
	 * @param string[] $params Associative array of parameters to set in constructor ; equivalent of calling corresponding fluent functions
	 */
	function mailing(array $params = [])
	{
		return new Mailing($this, $params);
	}
	
	
	
	/**
	 * Get underlying Mailer object
	 *
	 * @return Mailer
	 */
	function getMailer()
	{
		return $this->_mailer;
	}
	
	
	
	/**
	 * Constructor
	 *
	 * Optionnal parameters for `$params` are :
	 *   - queue : If set, a Nettools\MassMailing\QueueEngine\Queue name to create and append emails to
	 *   - queueParams : If set, parameters of queue subsystem as an associative array with values for keys `root` and `batchCount`

	 *   - testRecipients : If set, an array of email addresses to send emails to for testing purposes
	 *   - replyTo : If set, an email address to set in a ReplyTo header
	 *   - testMode : If true, email are sent to testing addresses (see `testRecipients` optionnal parameter) ; defaults to false
	 */

	
	/**
	 * Destruct object
	 */
	public function destroy()
	{
		if ( $this->_mailer )
			$this->_mailer->destroy();
	}
	
	
	
	/**
	 * Send the email
	 *
	 * @param string $mto Email recipient
	 * @param string $subject Specific email subject ; if NULL, the default value passed to the constructor will be used
	 * @param mixed $data Data that may be required during rendering process
	 * @throws \Nettools\MassMailing\MailingEngine\Exception
	 */
	public function prepareAndSend($mto, $subject = NULL, $data = NULL)
	{
		// compute email content and get a MailBuilder\Content object
		$mail = $this->_render($data);
		
		
		// if sending as batch (otherwise NULL), msender contains the queue name at first call ; then it will contain the queue object
		if ( $this->queue && is_string($this->queue) )
		{
			// opening queues store
			$store = Store::read($this->queueParams['root'], true);
			$queue = $store->createQueue($this->queue . '_' . date('Ymd'), $this->queueParams['batchCount']);
			$this->queue = $queue;
		}


		// test mode ?
		if ( $this->testMode )
		{
			// next test mail
			$to = current($this->testRecipients);
			next($this->testRecipients);

			// if no more test email ($to = NULL), exiting as we only simulate
			if ( !$to )
				return; 
		}
		else
			// in real mode, sending to a real email address
			$to = $mto; 


		$dest = $to;		
		
		
		// checking email syntax
		if ( is_null($dest) )
			throw new \Nettools\MassMailing\MailingEngine\Exception("Empty email recipient");
		/*if ( !preg_match("/^[a-z0-9]+([_|\.|-]{1}[a-z0-9]+)*@[a-z0-9]+([_|\.|-]{1}[a-z0-9]+)*[\.]{1}[a-z]{2,6}$/", $dest) )
			throw new \Nettools\MassMailing\MailingEngine\Exception("Malformed email : '$dest'");*/


		// if sending to a queue
		if ( is_object($this->queue) && ($this->queue instanceof Queue) )
			$this->queue->push($mail, $this->from, $dest, $subject); 
		else
			$this->mailer->sendmail($mail, $this->from, $dest, $subject);
	}
	
	
	
	/**
	 * Closing queue
	 */
	public function closeQueue()
	{
		if ( $this->getQueueCount() > 0 )
			$this->queue->commit();
	}
	

	
	/**
	 * Get count of emails in queue
	 *
	 * @return int
	 */
	public function getQueueCount()
	{
		// if `msender` property is an object, the queue has already been created and used : at least an email is in there
		if ( is_object($this->queue) && ($this->queue instanceof Queue) )
			return $this->queue->count;

		
		// if queue but not used yet
		else if ( is_string($this->queue) )
			return 0;
		
		
		// no queue used
		else
			return NULL;
	}
}

?>