<?php

// namespace
namespace Nettools\MassMailing\TemplateEngine;


// clauses use
use \Nettools\Mailing\MailBuilder\Builder;
use \Nettools\Mailing\MailBuilder\Content;
use \Nettools\Mailing\Mailer;





/**
 * Helper class to build a mail template text
 *
 * Useful when mass-mailing to customize mail content depending of recipients
 */
class Engine 
{
	protected $mail = NULL;
	protected $mailContentType = NULL;
	protected $template = Builder::DEFAULT_TEMPLATE;
	protected $preProcessors = [];
	

	
	/**
	 * Preprocess the mail content through an array of PreProcessor objects
	 * 
	 * @param mixed $data Any data required by pre-processor
	 * @return string
	 */
	protected function _preProcess($data)
	{
		$txt = $this->mail;
				
		foreach ( $this->preProcessors as $p )
			$txt = $p->process($txt, $data);
			
		return $txt;	
	}


	
	/**
	 * Create \Nettools\Mailing\MailBuilder\Content object 
	 *
	 * @param string $mail Mail as text, already customized through preprocessors and dynamic `$data` argument of `build` method
	 * @return \Nettools\Mailing\MailBuilder\Content
	 * @throws \Nettools\MassMailing\TemplateEngine\Exception
	 */
	protected function _createContent($mail)
	{
		switch ( $this->mailContentType )
		{
			case 'text/plain' : 
				return Builder::addTextHtmlFromText($mail, $this->template);
				
			case 'text/html': 
				return Builder::addTextHtmlFromHtml($mail, $this->template);
				
			default:
				throw new \Nettools\MassMailing\TemplateEngine\Exception('Unsupported content-type : ' . $this->mailContentType);
		}
	}	
	
	
	
	/**
	 * Constructor
	 *
	 * Optionnal parameters for `$params` are :
	 *   - template : template string used for email content ; if set, it must include a `%content%` string that will be replaced (call to `render` method) by the actual mail content (arg `$mail`)
	 *   - preProcessors : an array of PreProcessor objects that will update mail content
	 *
	 * @param string $mail Mail content as a string
	 * @param string $mailContentType May be 'text/plain' or 'text/html'
	 * @param string[] $params Associative array with optionnal parameters ; corresponding fluent methods will be called to set parameters
	 */
	function __construct($mail, $mailContentType, array $params = [])
	{
		// paramètres
		$this->mail = $mail;
		$this->mailContentType = $mailContentType;

		
		// optionnal parameters
		foreach ( $params as $k => $v )
			if ( method_exists($this, "set$k") )
				call_user_func([$this, "set$k"], $v);
	}
	
	
	
	/**
	 * Set template value 
	 *
	 * @param string $template
	 * @return Engine Returns $this for chaining calls
	 */
	function setTemplate($template)
	{
		$this->template = $template;
		return $this;
	}
	
	
		
	/**
	 * Set processors array
	 *
	 * @param PreProcessor[] $preprocessors ; existing values or discarded
	 * @return Engine Returns $this for chaining calls
	 */
	function setPreProcessors(array $preprocessors)
	{
		$this->preProcessors = $preprocessors;
		return $this;
	}
	
	
		
	/** 
	 * Testing that required parameters are set
	 *
	 * @throws \Nettools\MassMailing\TemplateEngine\Exception
	 */
	public function ready()
	{
		if ( empty($this->mail) )
            throw new \Nettools\MassMailing\TemplateEngine\Exception("TemplateEngine\\Engine::mail is not defined");
        
		if ( empty($this->template) )
            throw new \Nettools\MassMailing\TemplateEngine\Exception("TemplateEngine\\Engine::template is not defined");
        
		if ( empty($this->mailContentType) )
        	throw new \Nettools\MassMailing\TemplateEngine\Exception("TemplateEngine\\Engine::mailContentType is not defined");
	}
	
	
	
	/**
	 * Build the email with any customization required, thanks to preprocessors objects array 
	 *
	 * @param mixed $data Data that may be required during rendering process
	 * @return \Nettools\Mailing\MailBuilder\Content Return the email built
	 * @throws \Nettools\MassMailing\MailingEngine\Exception
	 */
	public function build($data = NULL)
	{
		// test requied parameters
		$this->ready();
		
		// process string through preprocessors array
		$mtxt = $this->_preProcess($data);
		
		// now create the Nettools\Mailing\MailBuilder\Content object
		return $this->_createContent($mtxt);		
	}
	
}

?>