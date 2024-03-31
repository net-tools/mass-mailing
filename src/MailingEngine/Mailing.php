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
	
	/*protected $_queue = NULL;
	protected $_queueParams = NULL;
	
	protected $_testMode = NULL;
	protected $_testRecipients = NULL;*/

	
	
	
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
	 * Fluent function to set UserDefinedHeaders value
	 *
	 * @param string $headers Associative array of headers header_name => header_value ; previous value of userDefinedHeaders is lost
	 * @return Engine Returns $this for chaining calls
	 */
	function withHeaders(array $headers)
	{
		$this->_userDefinedHeaders = $headers;
		return $this;
	}
	
	
	
	/**
	 * Prepare email before sending it
	 *
	 * @param \Nettools\Mailing\MailBuilder\Content $mail
	 */
	function setHeaders(Content $mail)
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
		$this->setHeaders($mail);

		// sending mail
		$this->_engine->getMailer()->sendmail($mail, $this->_from, $to, $overrideSubject ? $overrideSubject : $this->_subject);
	}
}

?>