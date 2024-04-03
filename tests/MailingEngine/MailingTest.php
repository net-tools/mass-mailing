<?php 

namespace Nettools\MassMailing\MailingEngine\Tests;



use \Nettools\Mailing\Mailer;
use \Nettools\MassMailing\MailingEngine\Engine as MailingEngine;
use \Nettools\MassMailing\TemplateEngine\Engine;
use \Nettools\MassMailing\QueueEngine\Store;
use \Nettools\MassMailing\QueueEngine\Queue;
use \org\bovigo\vfs\vfsStream;
use \org\bovigo\vfs\vfsStreamDirectory;




class MailingTest extends \PHPUnit\Framework\TestCase
{
	protected $_queuePath = NULL;
	protected $_vfs = NULL;
	

	public function setUp() :void
	{
		$this->_vfs = vfsStream::setup('root');
		
		// temp file
		$tmpdir = uniqid() . 'msh';
		vfsStream::newDirectory($tmpdir)->at($this->_vfs);
		
		$this->_queuePath = vfsStream::url("root/$tmpdir/");
	}
	

	
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
	
	
	
	public function testMailingBatch()
	{
		$ml = new Mailer(new \Nettools\Mailing\MailSenders\Virtual());

		// create mail from template system
		$mail = (new Engine())->template()->text('dummy content')->noAlternatePart()->build();
		
		// send mail
		$m = (new MailingEngine($ml))
				->mailing([ 'from' => 'unit-test@php.com' ])
					->about('test subject')
					->batchSend($mail, array('recipient1@domain.at', 'recipient2@domain.at'));

		$sent = $ml->getMailerEngine()->getMailSender()->getSent();
		$this->assertCount(2, $sent);	
		$this->assertStringContainsString('From: unit-test@php.com', $sent[0]);
		$this->assertStringContainsString('Subject: test subject', $sent[0]);
		$this->assertStringContainsString('To: recipient1@domain.at', $sent[0]);
		$this->assertStringContainsString('Delivered-To: recipient1@domain.at', $sent[0]);
		$this->assertStringContainsString('dummy content', $sent[0]);
		$this->assertStringContainsString('From: unit-test@php.com', $sent[1]);
		$this->assertStringContainsString('Subject: test subject', $sent[1]);
		$this->assertStringContainsString('To: recipient2@domain.at', $sent[1]);
		$this->assertStringContainsString('Delivered-To: recipient2@domain.at', $sent[1]);
		$this->assertStringContainsString('dummy content', $sent[1]);
		
		
		
		$ml = new Mailer(new \Nettools\Mailing\MailSenders\Virtual());

		// create mail from template system
		$mail = (new Engine())->template()->text('dummy content')->noAlternatePart()->build();
		
		// send mail
		$m = (new MailingEngine($ml))
				->mailing([ 'from' => 'unit-test@php.com' ])
					->about('test subject')
					->toQueue(MailingEngine::queue('qname', $this->_queuePath)
							  		->batchCount(10))
					->batchSend($mail, array('recipient1@domain.at', 'recipient2@domain.at'));

		
		$this->assertCount(0, $sent);	// no mail sent, as we use a queue

		// commit queue to storage
		$m->done();
		
		// sending queue
		$q->send($ml);
		
		$sent = $ml->getMailerEngine()->getMailSender()->getSent();
		$this->assertCount(2, $sent);				// 2 emails from queue sent
		$this->assertStringContainsString('From: unit-test@php.com', $sent[0]);
		$this->assertStringContainsString('Subject: test subject', $sent[0]);
		$this->assertStringContainsString('To: recipient1@domain.at', $sent[0]);
		$this->assertStringContainsString('Delivered-To: recipient1@domain.at', $sent[0]);
		$this->assertStringContainsString('dummy content', $sent[0]);
		$this->assertStringContainsString('From: unit-test@php.com', $sent[1]);
		$this->assertStringContainsString('Subject: test subject', $sent[1]);
		$this->assertStringContainsString('To: recipient2@domain.at', $sent[1]);
		$this->assertStringContainsString('Delivered-To: recipient2@domain.at', $sent[1]);
		$this->assertStringContainsString('dummy content', $sent[1]);
	}
	
	
	
	public function testReady1()
	{
		$ml = new Mailer(new \Nettools\Mailing\MailSenders\Virtual());

		// create mail from template system
		$mail = (new Engine())->template()->text('dummy content')->noAlternatePart()->build();
		
		// send will fail because From is missing
		$this->expectException(\Nettools\MassMailing\MailingEngine\Exception::class);
		$m = (new MailingEngine($ml))
				->mailing([ /*'from' => 'unit-test@php.com'*/ ])
					->header('X-Reference', 'header value')
					->about('test subject')
					->send($mail, 'recipient@domain.at');
	}
	
	
	
	public function testReady2()
	{
		$ml = new Mailer(new \Nettools\Mailing\MailSenders\Virtual());

		// create mail from template system
		$mail = (new Engine())->template()->text('dummy content')->noAlternatePart()->build();
		
		
		// send will succeed because Subject is set in send arg
		$m = (new MailingEngine($ml))
				->mailing([ 'from' => 'unit-test@php.com' ])
					->header('X-Reference', 'header value')
					/*->about('test subject')*/
					->send($mail, 'recipient@domain.at', 'new subject');
		$sent = $ml->getMailerEngine()->getMailSender()->getSent();
		$this->assertStringContainsString('Subject: new subject', $sent[0]);

		
		// send will fail because Subject is missing
		$this->expectException(\Nettools\MassMailing\MailingEngine\Exception::class);
		$m = (new MailingEngine($ml))
				->mailing([ 'from' => 'unit-test@php.com' ])
					->header('X-Reference', 'header value')
					/*->about('test subject')*/
					->send($mail, 'recipient@domain.at');
	}
	
	
	
	public function testReady3()
	{
		$ml = new Mailer(new \Nettools\Mailing\MailSenders\Virtual());

		// create mail from template system
		$mail = (new Engine())->template()->text('dummy content')->noAlternatePart()->build();
		
		// send will fail because To is missing
		$this->expectException(\Nettools\MassMailing\MailingEngine\Exception::class);
		$m = (new MailingEngine($ml))
				->mailing([ 'from' => 'unit-test@php.com' ])
					->header('X-Reference', 'header value')
					->about('test subject')
					->send($mail, /*'recipient@domain.at'*/'');
	}
	
	
	
	public function testWhen()
	{
		$ml = new Mailer(new \Nettools\Mailing\MailSenders\Virtual());

		// create mail from template system
		$mail = (new Engine())->template()->text('dummy content')->noAlternatePart()->build();
		
		// send mail
		$m = (new MailingEngine($ml))
				->mailing([ 'from' => 'unit-test@php.com' ])
					->about('ignored')
					->when(true, function($m) { $m->about('test subject'); } )
					->send($mail, 'recipient@domain.at');

		$sent = $ml->getMailerEngine()->getMailSender()->getSent();
		$this->assertCount(1, $sent);	
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
	
	
	
	public function testQueue()
	{
		$ml = new Mailer(new \Nettools\Mailing\MailSenders\Virtual());

		// create mail from template system
		$mail = (new Engine())->template()->text('dummy content')->noAlternatePart()->build();
		
		// send mail
		$m = (new MailingEngine($ml))
				->mailing([ 'from' => 'unit-test@php.com' ])
					->about('test subject')
					->toQueue(MailingEngine::queue('qname', $this->_queuePath)
							  		->batchCount(10));
		
		$m->send($mail, 'recipient@domain.at');
		$m->send($mail, 'recipient2@domain.at');

		$sent = $ml->getMailerEngine()->getMailSender()->getSent();
		$this->assertCount(0, $sent);	// no mail sent, as we use a queue

		// commit queue to storage
		$m->done();
		
		
		
		// testing queue content
		$msq = Store::read($this->_queuePath, true);
		$queues = $msq->getList(Store::SORT_DATE);
		$this->assertCount(1, $queues);
		$key = key($queues);
		$q = current($queues);
		$this->assertEquals('qname_' . date("Ymd"), $q->title);
		$this->assertEquals(2, $q->count);
		$this->assertEquals(false, $q->locked);
		$this->assertEquals(0, $q->sendOffset);		// no mail sent from queue, yet
		
		
		// send 1 mail through queue
		$ml = new Mailer(new \Nettools\Mailing\MailSenders\Virtual());
		$q->send($ml);
		$sent = $ml->getMailerEngine()->getMailSender()->getSent();
		$this->assertCount(2, $sent);				// 2 emails from queue sent
		$this->assertStringContainsString('dummy content', $sent[0]);
		$this->assertStringContainsString("From: unit-test@php.com\r\n", $sent[0]);
		$this->assertStringContainsString("X-MailSenderQueue: " . $q->id, $sent[0]);
		$this->assertStringContainsString("To: recipient@domain.at", $sent[0]);

		$this->assertStringContainsString("To: recipient2@domain.at", $sent[1]);
	}
}


?>