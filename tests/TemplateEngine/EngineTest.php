<?php 

namespace Nettools\MassMailing\TemplateEngine\Tests;



use \Nettools\MassMailing\TemplateEngine\Engine;
use \Nettools\Mailing\MailBuilder\Builder;




class EngineTest extends \PHPUnit\Framework\TestCase
{
	public function testParams()
	{
		// no processing done
		$e = new Engine('Dummy', 'text/plain');
		$mail = $e->build();
		$this->assertEquals(true, $mail instanceof \Nettools\Mailing\MailBuilder\Multipart);		
		$this->assertStringContainsString('Dummy', $mail->getContent());

		
		// using template
		$e = new Engine('To be processed', 'text/plain', [ 'template' => "Template is slashes around : /" . Builder::TEMPLATE_PLACEHOLDER . "/" ]);
		$mail = $e->build();
		$this->assertEquals(true, $mail instanceof \Nettools\Mailing\MailBuilder\Multipart);
		$this->assertStringContainsString('Template is slashes around : /To be processed/', $mail->getContent());
	}
	
	

	public function testAttachments()
	{
		// using template
		$e = new Engine('To be processed', 'text/plain');
		$e->setAttachments([ $e->attachment('{content of file}', 'text/plain')->asRawContent() ]);
		$mail = $e->build();
		$this->assertEquals(true, $mail instanceof \Nettools\Mailing\MailBuilder\Multipart);
		$this->assertEquals(true, $mail->getPart(1) instanceof \Nettools\Mailing\MailBuilder\Attachment);
		$this->assertStringContainsString("Content-Disposition: attachment;\r\n filename=\"no_name\"", $mail->getContent());
		$this->assertStringContainsString(base64_encode('{content of file}'), $mail->getContent());
	}
	
	

	public function testEmbeddings()
	{
		// using template
		$e = new Engine('To be processed', 'text/plain');
		$e->setEmbeddings([ $e->embedding('{content of file}', 'text/plain', 'cid1')->asRawContent() ]);
		$mail = $e->build();
		$this->assertEquals(true, $mail instanceof \Nettools\Mailing\MailBuilder\Multipart);
		$this->assertEquals(true, $mail->getPart(1) instanceof \Nettools\Mailing\MailBuilder\Embedding);
		$this->assertStringContainsString("Content-Disposition: inline;\r\n filename=\"cid1\"", $mail->getContent());
		$this->assertStringContainsString(base64_encode('{content of file}'), $mail->getContent());
	}
	
	

	public function testReady()
	{
		function __ready($e)
		{
			try
			{
				$e->ready();
				return true;
			}
			catch( \Nettools\MassMailing\TemplateEngine\Exception $e )
			{
				return false;
			}
		}
		
		$e = new Engine(NULL, NULL, NULL, NULL);		
		$this->assertEquals(false, __ready($e));	// no parameter
		
		$e = new Engine('content', 'text/plain');
		$this->assertEquals(true, __ready($e));		// all parameters
		
		$e = new Engine(NULL, 'text/plain');
		$this->assertEquals(false, __ready($e));	// all except content
		
		$e = new Engine('content', NULL);
		$this->assertEquals(false, __ready($e));	// all except contenttype
		
		$e = new Engine('content', 'text/plain', [ 'template' => NULL ]);
		$this->assertEquals(false, __ready($e));	// empty template
	}
}

?>