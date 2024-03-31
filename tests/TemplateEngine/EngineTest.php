<?php 

namespace Nettools\MassMailing\TemplateEngine\Tests;



use \Nettools\MassMailing\TemplateEngine\Engine;
use \Nettools\MassMailing\TemplateEngine\PreProcessor;
use \Nettools\Mailing\MailBuilder\Builder;



class Processor1 implements PreProcessor
{
	function process($txt, $data = NULL)
	{
		return "<p>$txt</p>";
	}	
}





class EngineTest extends \PHPUnit\Framework\TestCase
{
	public function testParams()
	{
		// no processing done
		$e = (new Engine())->template()->text('Dummy')->noAlternatePart();
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
		$e = (new Engine())->template()
					->text('To be processed')
					->noAlternatePart()
					->attachSome([
									Engine::attachment('{content1 of file}', 'text/plain')->asRawContent(),
									Engine::attachment('{content2 of file}', 'text/plain')->asRawContent()->withFileName('MyFile.txt')
								]);
		$mail = $e->build();
		$this->assertEquals(true, $mail instanceof \Nettools\Mailing\MailBuilder\Multipart);
		$this->assertEquals(true, $mail->getPart(1) instanceof \Nettools\Mailing\MailBuilder\Attachment);
		$this->assertEquals(true, $mail->getPart(2) instanceof \Nettools\Mailing\MailBuilder\Attachment);
		$this->assertStringContainsString("Content-Disposition: attachment;\r\n filename=\"no_name\"", $mail->getPart(1)->toString());
		$this->assertStringContainsString("Content-Disposition: attachment;\r\n filename=\"MyFile.txt\"", $mail->getPart(2)->toString());
		$this->assertStringContainsString(base64_encode('{content1 of file}'), $mail->getPart(1)->getContent());
		$this->assertStringContainsString(base64_encode('{content2 of file}'), $mail->getPart(2)->getContent());
	}
	


	public function testEmbeddings()
	{
		// using template
		$e = (new Engine())->template()
					->text('To be processed')
					->noAlternatePart()
					->embedSome([
									Engine::embedding('{content1 of file}', 'text/plain', 'cid1')->asRawContent(),
									Engine::embedding('{content2 of file}', 'text/plain', 'cid2')->asRawContent()
								]);

		$mail = $e->build();
		$this->assertEquals(true, $mail instanceof \Nettools\Mailing\MailBuilder\Multipart);
		$this->assertEquals(true, $mail->getPart(1) instanceof \Nettools\Mailing\MailBuilder\Embedding);
		$this->assertEquals(true, $mail->getPart(2) instanceof \Nettools\Mailing\MailBuilder\Embedding);
		$this->assertStringContainsString("Content-Disposition: inline;\r\n filename=\"cid1\"", $mail->getPart(1)->toString());
		$this->assertStringContainsString("Content-Disposition: inline;\r\n filename=\"cid2\"", $mail->getPart(2)->toString());
		$this->assertStringContainsString(base64_encode('{content1 of file}'), $mail->getPart(1)->getContent());
		$this->assertStringContainsString(base64_encode('{content2 of file}'), $mail->getPart(2)->getContent());
	}
	
	
	
	public function testPreProcessor()
	{
		$e = (new Engine())->template()
					->text('To be processed')
					->noAlternatePart()
					->preProcessor(new Processor1())
					->preProcessors([new PreProcessor_SearchReplace()])
					->withData([ '%placeholder%' => 'here_is_the_value' ]);

		$mail = $e->build();
		$this->assertEquals(true, $mail instanceof \Nettools\Mailing\MailBuilder\Multipart);
		$this->assertStringContainsString('<p>To be processed : [here_is_the_value]</p>', $mail->getContent());
	}
	

}

?>