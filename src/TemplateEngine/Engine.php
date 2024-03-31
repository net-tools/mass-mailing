<?php

// namespace
namespace Nettools\MassMailing\TemplateEngine;


// clauses use
use \Nettools\Mailing\FluentEngine\ContentEngine;





/**
 * Main class with fluent design for creating mail templates
 *
 * Useful when mass-mailing to customize mail content depending on recipients
 */
class Engine extends ContentEngine
{
	/** 
	 * Create Template object with fluent design
	 *
	 * @return Template
	 */
	function template()
	{
		return new Template($this);
	}
		
}

?>