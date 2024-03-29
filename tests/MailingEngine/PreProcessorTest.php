<?php 

namespace Nettools\MassMailing\MailingEngine\Tests;



use \Nettools\Mailing\Mailer;
use \Nettools\MassMailing\MailingEngine\Engine;
use \Nettools\MassMailing\MailingEngine\PreProcessor;



class Processor1 implements PreProcessor
{
	function process($txt, $data = NULL)
	{
		return $txt . $txt . $data;
	}	
}


class Processor2 implements PreProcessor
{
	function process($txt, $data = NULL)
	{
		return "*$txt*";
	}	
}




class PreProcessorTest extends \PHPUnit\Framework\TestCase
{
	public function testPreProcessor()
	{
		$ml = new Mailer(new \Nettools\Mailing\MailSenders\Virtual());
		$msh = new Engine($ml, 'To be processed', 'text/plain', 'unit-test@php.com', 'test subject', [ 'preProcessors' => [new Processor1(), new Processor2()] ]);
		$msh->prepareAndSend('user-to@php.com');
		$sent = $ml->getMailerEngine()->getMailSender()->getSent();
		$this->assertCount(1, $sent);
		
		$this->assertStringContainsString('*To be processedTo be processed*', $sent[0]);
	}
}


?>