<?php 

namespace Nettools\MassMailing\TemplateEngine\Tests;



use \Nettools\MassMailing\TemplateEngine\Engine;
use \Nettools\MassMailing\TemplateEngine\PreProcessor;
use \Nettools\MassMailing\TemplateEngine\PreProcessor_SearchReplace;



class Processor1 implements PreProcessor
{
	function process($txt, $data = NULL)
	{
		return "<p>$txt</p>";
	}	
}





class PreProcessorTest extends \PHPUnit\Framework\TestCase
{
	public function testPreProcessor()
	{
		$e = new Engine('To be processed : [%placeholder%]', 'text/plain', [ 'preProcessors' => [new Processor1(), new PreProcessor_SearchReplace()] ]);
		$mail = $e->build([ '%placeholder%' => 'here_is_the_value' ]);
		$this->assertEquals(true, $mail instanceof \Nettools\Mailing\MailBuilder\Multipart);		
		$this->assertStringContainsString('<p>To be processed : [here_is_the_value]</p>', $mail->getContent());
	}
}


?>