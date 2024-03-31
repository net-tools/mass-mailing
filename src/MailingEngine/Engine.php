<?php

// namespace
namespace Nettools\MassMailing\MailingEngine;


// clauses use
use \Nettools\Mailing\Mailer;





/**
 * Engine class to send several emails with customized content, queue system and test recipients, with fluent design
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
	 * Prepare queue target
	 *
	 * @param string $name Queue name
	 * @param string $root Path to root directory for queue storage
	 */
	static function queue($name, $root)
	{
		return new Queue($name, $root);
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
	 * Destruct object
	 */
	public function destroy()
	{
		if ( $this->_mailer )
			$this->_mailer->destroy();
	}
	
}

?>