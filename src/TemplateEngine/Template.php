<?php

// namespace
namespace Nettools\MassMailing\TemplateEngine;


// clauses use
use \Nettools\Mailing\FluentEngine\Content;





/**
 * Helper class to build a mail template text with fluent design
 */
class Template extends Content
{
	protected $preProcessors = [];
	protected $preProcessorsData = NULL;
	

	
	/**
	 * Preprocess the mail content through an array of PreProcessor objects
	 * 
	 * @return string
	 */
	protected function _preProcess()
	{
		$txt = $this->_content;
				
		foreach ( $this->preProcessors as $p )
			$txt = $p->process($txt, $this->preProcessorsData);
			
		return $txt;	
	}


		
	/**
	 * Define(add) a new preprocessor
	 *
	 * @param PreProcessor $preprocessor ; add a new PreProcessor object to the list of preprocessors
	 * @return Template Returns $this for chaining calls
	 */
	function preProcessor(PreProcessor $preprocessor)
	{
		$this->preProcessors[] = $preprocessor;
		return $this;
	}
	
		
		
	/**
	 * Add preprocessors array
	 *
	 * @param PreProcessor[] $preprocessors ; add an array of PreProcessor objects to the list of preprocessors
	 * @return Template Returns $this for chaining calls
	 */
	function preProcessors(array $preprocessors)
	{
		$this->preProcessors = array_merge($this->preProcessors, $preprocessors);
		return $this;
	}
	
	
	
	/**
	 * Set preprocess data
	 *
	 * @param mixed $data
	 * @return Template Returns $this for chaining calls
	 */
	function withData($data)
	{
		$this->preProcessorsData = $data;
		return $this;
	}
	
		
		
	/**
	 * Update content string before creating Nettools\Mailing\MailBuilder\Content object
	 *
	 * This is here that we pre-process the string template
	 *
	 * @return string
	 */
	function updateContentString()
	{
		return $this->_preProcess();
	}
	
	
	
	/**
	 * Build the email with any customization required, thanks to preprocessors objects array 
	 *
	 * @param mixed $data Data that may be required during rendering process
	 * @return \Nettools\Mailing\MailBuilder\Content Return the email built
	 * @throws \Nettools\MassMailing\MailingEngine\Exception
	 */
	public function build()
	{
		return $this->create();
	}
	
}

?>