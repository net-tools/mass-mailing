<?php

// namespace
namespace Nettools\MassMailing\TemplateEngine;



// Simple preprocessor class to update mail text replacing placeholders with values
class PreProcessor_SearchReplace implements PreProcessor {
	
	/** 
	 * Process a string and update its content, then return the updated string
	 * 
	 * @param string $txt The text content to process
	 * @param string[] $data Associative array describing text to look for (keys) and replacement values
	 * @return string
	 */
	function process($txt, $data = NULL)
	{
		if ( !is_array($data) )
			throw new \Nettools\MassMailing\TemplateEngine\Exception("TemplateEngine\\PreProcessor_SearchReplace::\$data argument is not an array");
		
		foreach ( $data as $search => $replace )
			$txt = str_replace($search, $replace, $txt);		
		
		return $txt;
	}
}


?>