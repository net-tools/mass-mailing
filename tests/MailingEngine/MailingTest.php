<?php 

namespace Nettools\MassMailing\MailingEngine\Tests;



use \Nettools\Mailing\Mailer;
use \Nettools\MassMailing\MailingEngine\Mailing;
use \Nettools\MassMailing\TemplateEngine\Engine;
use \org\bovigo\vfs\vfsStream;
use \org\bovigo\vfs\vfsStreamDirectory;




class MailingTest extends \PHPUnit\Framework\TestCase
{
	protected $_queuePath = NULL;
	

	public function testMailing()
	{
		$ml = new Mailer(new \Nettools\Mailing\MailSenders\Virtual());

		// create mail from template system
		$mail = (new Engine())->template()->text('dummy content')->noAlternatePart()->build();
		
		// send mail
		$m = (new Mailing($ml))
				->from('unit-test@php.com')
				->about('test subject')
				->send($mail, 'recipient@domain.at');

		$sent = $ml->getMailerEngine()->getMailSender()->getSent();
		$this->assertCount(1, $sent);	
		$this->assertStringContainsString('From: unit-test@php.com', $sent[0]);
		$this->assertStringContainsString('Subject: test subject', $sent[0]);
		$this->assertStringContainsString('Delivered-To: recipient@domain.at', $sent[0]);
	}
}


?>