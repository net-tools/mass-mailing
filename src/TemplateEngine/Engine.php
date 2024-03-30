<?php

// namespace
namespace Nettools\MassMailing\TemplateEngine;


// clauses use
use \Nettools\Mailing\MailBuilder\Builder;
use \Nettools\Mailing\MailBuilder\Content;
use \Nettools\Mailing\FluentEngine\Attachment;
use \Nettools\Mailing\FluentEngine\Embedding;





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
	protected $attachments = [];
	protected $embeddings = [];
	

	
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
				$mail = Builder::addTextHtmlFromText($mail, $this->template);
				break;
				
			case 'text/html': 
				$mail = Builder::addTextHtmlFromHtml($mail, $this->template);
				break;
				
			default:
				throw new \Nettools\MassMailing\TemplateEngine\Exception('Unsupported content-type : ' . $this->mailContentType);
		}
		
		
		
		// create attachments and embeddings objects
		$atts = [];
		foreach ( $this->attachments as $att )
			$atts[] = $att->create();

		$embeds = [];
		foreach ( $this->embeddings as $emb )
			$embeds[] = $emb->create();
		
		
		// now add embeddings and attachements objects to mail object
		if ( count($embeds) )
			$mail = Builder::addEmbeddingObjects($mail, $embeds);

		if ( count($atts) )
			$mail = Builder::addAttachmentObjects($mail, $atts);
		
		
		return $mail;	
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
	function __construct($mail, $mailContentType, ?array $params = [])
	{
		// paramètres
		$this->mail = $mail;
		$this->mailContentType = $mailContentType;

		
		// optionnal parameters
		if ( is_array($params) )
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
	 * Set attachments array
	 *
	 * @param \Nettools\Mailing\FluentEngine\Attachment[] $attachments Array of \Nettools\Mailing\FluentEngine\Attachment objects created with Engine::attachment fluent calls
	 * @return Engine Returns $this for chaining calls
	 */
	function setAttachments(array $attachments)
	{
		$this->attachments = $attachments;
		return $this;
	}
	
	
		
	/**
	 * Set embeddings array
	 *
	 * @param \Nettools\Mailing\FluentEngine\Embedding[] $embeddings Array of \Nettools\Mailing\FluentEngine\Embedding objects created with Engine::embedding fluent calls
	 * @return Engine Returns $this for chaining calls
	 */
	function setEmbeddings(array $embeddings)
	{
		$this->embeddings = $embeddings;
		return $this;
	}
	
	
	
	/**
	 * Create attachment fluent description
	 *
	 * @param string $content Attachment filepath or content (if $isFile = false)
	 * @param string $ctype Mime type
	 * @return \Nettools\Mailing\FluentEngine\Attachment
	 */	
	static function attachment($content, $ctype)
	{
		return new Attachment($content, $ctype);
	}
	
	
		
	/**
	 * Create embedding fluent description
	 *
	 * @param string $content Embedding filepath or content (if $isFile = false)
	 * @param string $ctype Mime type
	 * @param string $cid Content-Id
	 * @return \Nettools\Mailing\FluentEngine\Embedding
	 */	
	static function embedding($content, $ctype, $cid)
	{
		return new Embedding($content, $ctype, $cid);
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