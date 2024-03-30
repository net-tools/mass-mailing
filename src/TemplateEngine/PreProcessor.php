<?php

// namespace
namespace Nettools\MassMailing\TemplateEngine;



// Preprocessor class to update mail text before it's handled in TemplateEngine\Engine
interface PreProcessor {
	
	/** 
	 * Process a string and update its content, then return the updated string
	 * 
	 * @param string $txt The text content to process
	 * @param mixed $data Any data required to update the text content
	 * @return string
	 */
	function process($txt, $data = NULL);
}


?>