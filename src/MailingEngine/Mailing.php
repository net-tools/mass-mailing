<?php

// namespace
namespace Nettools\MassMailing\MailingEngine;


// clauses use
use \Nettools\MassMailing\QueueEngine\Store;
use \Nettools\Mailing\MailBuilder\Content;




/**
 * Helper class to send several emails
 *
 * Subject, from address, replyto can be set at object construction, then all required customizations are applied to mail objects sent through `send` method.
 * It also makes it possible to send emails to tests recipients or to a queue object, using fluent design
 */
class Mailing
{
	protected $_from = NULL;
	protected $_subject = NULL;
	protected $_replyTo = NULL;
	protected $_bccTo = NULL;
	protected $_ccTo = NULL;
	protected $_userDefinedHeaders = [];
	
	protected $_queue = NULL;
	protected $_queueObj = NULL;
	protected $_testRecipients = [];

	protected $_engine = NULL;
	

	
	
	/**
	 * Constructor
	 *
	 * @param Engine $engine
	 * @param string[] $params Associative array of parameters to set in constructor ; equivalent of calling corresponding fluent functions
	 */
	function __construct(Engine $engine, array $params = [])
	{
		$this->_engine = $engine;
		
		
		// maybe we want to set already some parameters, calling fluent method from here
		foreach ( $params as $k => $v )
			if ( method_exists($this, $k) )
				call_user_func([$this, $k], $v);
	}
	
	
	
	/** 
	 * Send emails to queue subsystem defined with fluent design
	 *
	 * @param Queue $queue Queue definition object
	 * @return Mailing Returns $this for chaining calls
	 */
	 function toQueue(Queue $queue)
	 {
		 $this->_queue = $queue;		 
		 return $this;
	 }
	
	
	
	/** 
	 * Set test recipients
	 *
	 * @param string[] $testRecipients
	 * @return Mailing Returns $this for chaining calls
	 */
	 function toTestRecipients(array $testRecipients)
	 {
		 $this->_testRecipients = $testRecipients;
		 return $this;
	 }
	
	

	/** 
	 * Fluent function to set From value
	 *
	 * @param string $from
	 * @return Mailing Returns $this for chaining calls
	 */
	function from($from)
	{
		$this->_from = $from;
		return $this;
	}
	

	
	/** 
	 * Fluent function to set Subject value
	 *
	 * @param string $about
	 * @return Mailing Returns $this for chaining calls
	 */
	function about($subject)
	{
		$this->_subject = $subject;
		return $this;
	}
	

	
	/** 
	 * Fluent function to set ReplyTo value
	 *
	 * @param string $rto
	 * @return Mailing Returns $this for chaining calls
	 */
	function replyTo($rto)
	{
		$this->_replyTo = $rto;
		return $this;
	}
	
	
	
	/** 
	 * Fluent function to set Bcc value
	 *
	 * @param string $bcc
	 * @return Mailing Returns $this for chaining calls
	 */
	function bccTo($bcc)
	{
		$this->_bccTo = $bcc;
		return $this;
	}
	
	
	
	/** 
	 * Fluent function to set Cc value
	 *
	 * @param string $cc
	 * @return Mailing Returns $this for chaining calls
	 */
	function ccTo($cc)
	{
		$this->_ccTo = $cc;
		return $this;
	}
	
	
	
	/**
	 * Conditionnal statement
	 *
	 * @param bool $cond Bool value to test ; if `$cond` = True, the action callback is called, otherwise it's ignored
	 * @param function $callback Function called as callback if `$cond` equals True, with `$this` as parameter so that calls can be chained
	 * @return Mailing Return $this for chaining calls
	 */
	function when($cond, $callback)
	{
		if ( $cond )
			// call user function
			call_user_func($callback, $this, $this->_engine);
		
		return $this;
	}
	
	
	
	/** 
	 * Fluent function to add a user defined header
	 *
	 * @param string $name
	 * @param string $value
	 * @return Mailing Returns $this for chaining calls
	 */
	function header($name, $value)
	{
		$this->_userDefinedHeaders[$name] = $value;
		return $this;
	}
	
	
	
	/** 
	 * Fluent function to set UserDefinedHeaders value
	 *
	 * @param string $headers Associative array of headers ; key is header name, value is header value ; previous value of _userDefinedHeaders is lost
	 * @return Mailing Returns $this for chaining calls
	 */
	function withHeaders(array $headers)
	{
		$this->_userDefinedHeaders = $headers;
		return $this;
	}
	
	
	
	/**
	 * Prepare email headers before sending it
	 *
	 * @param \Nettools\Mailing\MailBuilder\Content $mail
	 */
	function prepareHeaders(Content $mail)
	{
		if ( $this->_replyTo )
			$mail->headers->replyTo = $this->_replyTo;
		if ( $this->_bccTo )
			$mail->headers->Bcc = $this->_bccTo;
		if ( $this->_ccTo )
			$mail->headers->Cc = $this->_ccTo;
		
		foreach ( $this->_userDefinedHeaders as $k => $h )
			$mail->headers->set($k, $h);
	}
	
	
	
	/**
	 * Create queue if not done
	 *
	 * @return bool Returns True if queue target is enabled (queue may be created as required) ; returns False if queue target disabled
	 */
	public function createQueue()
	{
		// queue already created
		if ( $this->_queueObj )
			return true;
		
		// queue not used
		if ( is_null($this->_queue) )
			return false;
		
		
		// creating queue
		$store = Store::read($this->_queue->root, true);
		$this->_queueObj = $store->createQueue($this->_queue->name . '_' . date('Ymd'), $this->_queue->batchCount);
		return true;
	}
	
	
	
	/**
	 * Mass-mailing is now over, do house cleaning stuff ; if using queue, committing queue to storage
	 */
	public function done()
	{
		// if using queue, commit updates
		if ( $this->_queueObj && ($this->_queueObj->count > 0) )
			$this->_queueObj->commit();
		
		// closing connections
		$this->_engine->destroy();
	}
	
	
	
	/** 
	 * Send an email to some recipients already created with TemplateEngine or any other way, provided it's a Nettools\Mailing\MailBuilder\Content object
	 * and apply any headers required
	 *
	 * This function is suitable to send a customized email to one or more recipients (generally one, but twice or more can be set as recipients ; however, they
	 * will all appear in `To` header). 
	 *
	 * For each call to `send`, the `$mail` object is converted to a string. So, if a same email must be sent to a lot of recipients, one recipient at a time (so that 
	 * all recipients don't appear in `To` header), the sendBatch function is prefered.
	 *
	 * @param \Nettools\Mailing\MailBuilder\Content $mail 
	 * @param string $to Recipients separated by `,`
	 * @param string $overrideSubject If a custom subject is used for sending mail (makes it possible to have a user-defined value for each mail sent, such as user name)
	 * @return Mailing Returns $this for chaining calls
	 * @throws \Nettools\MassMailing\MailingEngine\Exception
	 */
	function send(Content $mail, $to, $overrideSubject = NULL)
	{
		// checking mandatory values
		if ( !$this->_from )
			throw new \Nettools\MassMailing\MailingEngine\Exception("Mass-mailing `From` value missing");
		
		if ( !$to )
			throw new \Nettools\MassMailing\MailingEngine\Exception("Mass-mailing `To` value missing");
		
		if ( !$overrideSubject && !$this->_subject )
			throw new \Nettools\MassMailing\MailingEngine\Exception("Mass-mailing `Subject` value missing");

		
		// set appropriate headers (except From, Subject, and To)
		$this->prepareHeaders($mail);
		
		
		// test mode enabled ? if so, ignoring $to parameter and sending to next available test recipient
		if ( count($this->_testRecipients) )
		{
			// next test mail
			$to = current($this->_testRecipients);
			next($this->_testRecipients);

			// if no more test email ($to = NULL), exiting as we only simulate
			if ( !$to )
				return $this; 
		}		
		

		// sending mail
		if ( $this->createQueue() )
			$this->_queueObj->push($mail, $this->_from, $to, $overrideSubject ? $overrideSubject : $this->_subject); 
		else
			$this->_engine->getMailer()->sendmail($mail, $this->_from, $to, $overrideSubject ? $overrideSubject : $this->_subject);
		
		
		return $this;
	}
	
	
	
	/** 
	 * Send a defined email to a lot of recipients
	 *
	 * Each email is sent to a single recipient and optimizations are done to prevent uneccesary stuff
	 *
	 * @param \Nettools\Mailing\MailBuilder\Content $mail 
	 * @param string[] $to Array of recipients
	 * @return Mailing Returns $this for chaining calls
	 * @throws \Nettools\MassMailing\MailingEngine\Exception
	 */
	function batchSend(Content $mail, array $to)
	{
		// checking mandatory values
		if ( !$this->_from )
			throw new \Nettools\MassMailing\MailingEngine\Exception("Mass-mailing `From` value missing");
		
		if ( !count($to) )
			throw new \Nettools\MassMailing\MailingEngine\Exception("Mass-mailing `To` value missing");

		if ( !$this->_subject )
			throw new \Nettools\MassMailing\MailingEngine\Exception("Mass-mailing `Subject` value missing");
		
		// set appropriate headers (except From, Subject, and To)
		$this->prepareHeaders($mail);
		
		
		// test mode enabled ? if so, use array of test recipients
		if ( count($this->_testRecipients) )
			$to = $this->_testRecipients;
		
		
		// convert mail to string and get headers
		$m = $mail->getContent();
		$h = $mail->getAllHeaders()->set('From', $this->_from);
		$hs = $h->toString();
		

		// sending mail
		foreach ( $to as $recipient )
			if ( $this->createQueue() )
				$this->_queueObj->pushAsString($m, $hs, $recipient, $this->_subject); 
			else
				$this->_engine->getMailer()->sendmail_raw($recipient, $this->_subject, $m, $h);
		
		
		return $this;
	}
}

?>