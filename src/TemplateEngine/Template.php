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
	protected $_preProcessors = [];
	protected $_preProcessorsData = NULL;
	

	
	/**
	 * Preprocess the mail content through an array of PreProcessor objects
	 * 
	 * @return string
	 */
	protected function _preProcess()
	{
		$txt = $this->_content;
				
		foreach ( $this->_preProcessors as $p )
			$txt = $p->process($txt, $this->_preProcessorsData);
			
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
		$this->_preProcessors[] = $preprocessor;
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
		$this->_preProcessors = array_merge($this->_preProcessors, $preprocessors);
		return $this;
	}
	
	
	
	/**
	 * Set preprocessors data
	 *
	 * @param mixed $data Preprocessors data
	 * @return Template Returns $this for chaining calls
	 */
	function withData($data)
	{
		$this->_preProcessorsData = $data;
		return $this;
	}
	
		
		
	/**
	 * Update content string before creating Nettools\Mailing\MailBuilder\Content object
	 *
	 * This is here that we pre-process the string template
	 *
	 * @return string
	 */
	function returnProcessedContentString()
	{
		return $this->_preProcess();
	}
	
	
	
	/**
	 * Build the email with any customization required, thanks to preprocessors objects array 
	 *
	 * @return \Nettools\Mailing\MailBuilder\Content Return the email built
	 */
	public function build()
	{
		return $this->create();
	}
	
	
	
	/**
	 * Build the email with any customization required, thanks to preprocessors objects array, with preprocessors data as argument
	 * Preprocessors data can also be set by calling `withData` before `build` function.
	 *
	 * @param mixed $data Data that may be required during rendering process
	 * @return \Nettools\Mailing\MailBuilder\Content Return the email built
	 */
	public function buildWithData($data)
	{
		return $this->withData($data)->build();
	}
	
}

?>