<?php 

namespace Nettools\MassMailing\MailingEngine\Tests;



use \Nettools\Mailing\Mailer;
use \Nettools\MassMailing\MailingEngine\Engine as MailingEngine;
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
		$m = (new MailingEngine($ml))
				->mailing([ 'from' => 'unit-test@php.com' ])
					->header('X-Reference', 'header value')
					->about('test subject')
					->send($mail, 'recipient@domain.at');

		$sent = $ml->getMailerEngine()->getMailSender()->getSent();
		$this->assertCount(1, $sent);	
		$this->assertStringContainsString('X-Reference: header value', $sent[0]);
		$this->assertStringContainsString('From: unit-test@php.com', $sent[0]);
		$this->assertStringContainsString('Subject: test subject', $sent[0]);
		$this->assertStringContainsString('To: recipient@domain.at', $sent[0]);
		$this->assertStringContainsString('Delivered-To: recipient@domain.at', $sent[0]);
	}
	
	
	
	public function testModeTest()
	{
		$ml = new Mailer(new \Nettools\Mailing\MailSenders\Virtual());

		// create mail from template system
		$mail = (new Engine())->template()->text('dummy content')->noAlternatePart()->build();
		
		// send mail
		$m = (new MailingEngine($ml))
				->mailing([ 'from' => 'unit-test@php.com' ])
					->about('test subject')
					->toTestRecipients(['test1@me.com', 'test2@me.com']);
		
		$m->send($mail, 'recipient@domain.at');
		$m->send($mail, 'recipient2@domain.at');

		$sent = $ml->getMailerEngine()->getMailSender()->getSent();
		$this->assertCount(2, $sent);	
		$this->assertStringContainsString('From: unit-test@php.com', $sent[0]);
		$this->assertStringContainsString('Subject: test subject', $sent[0]);
		$this->assertStringContainsString('To: test1@me.com', $sent[0]);
		$this->assertStringContainsString('Delivered-To: test1@me.com', $sent[0]);
		$this->assertStringContainsString('To: test2@me.com', $sent[1]);
		$this->assertStringContainsString('Delivered-To: test2@me.com', $sent[1]);
	}
}


?>