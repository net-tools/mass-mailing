<?php

// namespace
namespace Nettools\MassMailing\MailingEngine;


// clauses use
/*use \Nettools\Mailing\Mailer;
use \Nettools\MassMailing\QueueEngine\Queue;
use \Nettools\MassMailing\QueueEngine\Store;*/
use \Nettools\Mailing\MailBuilder\Content;




/**
 * Helper class to send several emails
 *
 * Subject, from address, replyto can be set at object construction, then all required customizations are applied to mail objects sent through `send` method.
 * It also makes it possible to send emails to tests recipients or to a queue object, again just by setting appropriate parameters of constructor
 */
class Mailing
{
	protected $_from = NULL;
	protected $_subject = NULL;
	protected $_replyTo = false;
	protected $_userDefinedHeaders = [];
	
	protected $_engine = NULL;
	
	protected $_queue = NULL;
	protected $_queueParams = NULL;	
	protected $_testRecipients = [];

	
	
	
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
	 * Send emails to queue subsystem
	 *
	 * @param string $queueName Queue name
	 * @param string[] $queueParams Associative array of parameters for queue, defining `root` and `batchCount` values
	 * @return Engine Returns $this for chaining calls
	 */
	 function toQueue($queueName, $queueParams)
	 {
		 $this->_queue = $queueName;
		 $this->_queueParams = $queueParams;
		 
		 return $this;
	 }
	
	
	
	/** 
	 * Set test recipients
	 *
	 * @param string[] $testRecipients
	 * @return Engine Returns $this for chaining calls
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
	 * @return Engine Returns $this for chaining calls
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
	 * @return Engine Returns $this for chaining calls
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
	 * @return Engine Returns $this for chaining calls
	 */
	function replyTo($rto)
	{
		$this->_replyTo = $rto;
		return $this;
	}
	
	
	
	/** 
	 * Fluent function to add a user defined header
	 *
	 * @param string $name
	 * @param string $value
	 * @return Engine Returns $this for chaining calls
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
	 * @return Engine Returns $this for chaining calls
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
		
		foreach ( $this->_userDefinedHeaders as $k => $h )
			$mail->headers->set($k, $h);
	}
	
	
	
	/** 
	 * Send an email already created with TemplateEngine or any other way, provided it's a Nettools\Mailing\MailBuilder\Content object
	 * and apply any headers required
	 *
	 * @param \Nettools\Mailing\MailBuilder\Content $mail 
	 * @param string $to Recipient
	 * @param string $overrideSubject If a custom subject is used for sending mail (makes it possible to have a user-defined value for each mail sent, such as user name)
	 */
	function send(Content $mail, $to, $overrideSubject = NULL)
	{
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
				return; 
		}		
		

		// sending mail
		$this->_engine->getMailer()->sendmail($mail, $this->_from, $to, $overrideSubject ? $overrideSubject : $this->_subject);
	}
}

?>