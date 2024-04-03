<?php 

namespace Nettools\MassMailing\QueueEngine\Tests;



use \Nettools\Mailing\Mailer;
use \Nettools\Mailing\MailBuilder\Builder;
use \Nettools\MassMailing\QueueEngine\Data;
use \Nettools\MassMailing\QueueEngine\Store;
use \Nettools\MassMailing\QueueEngine\Queue;
use \org\bovigo\vfs\vfsStream;




class QueueTest extends \PHPUnit\Framework\TestCase
{
	protected $_vfs = NULL;
	

	public function setUp() :void
	{
		$this->_vfs = vfsStream::setup('root');
	}
	
	
		
	public function testMSQCreate()
	{
		$store = $this->createMock(Store::class);
		$store->expects($this->exactly(2))->method('commit');		// only 2 calls from Queue, since queue creation is not done through the store and since Delete does not call commit but removeQueue
		$store->expects($this->once())->method('removeQueue');	
		$params = ['root'=>$this->_vfs->url(), 'store'=>$store];
		$q = Queue::create('qname', $params);

		$this->assertEquals(0, $q->count);
		$this->assertEquals(50, $q->batchCount);
		$this->assertEquals(0, $q->sendOffset);
		$this->assertEquals(NULL, $q->lastBatchDate);
		$this->assertEquals([], $q->sendLog);
		$this->assertEquals('qname', $q->title);
		$this->assertEquals(false, $q->locked);
		$this->assertEquals(0, $q->volume);
		
		
		// asserting directory of queue has been created
		$this->assertEquals(true, $this->_vfs->hasChild($q->id));
		
		
		$q->rename('newqname');
		$this->assertEquals('newqname', $q->title);
		
		$q->locked = true;
		$q->unlock();
		$this->assertEquals(false, $q->locked);
		
		
		// asserting directory of queue has been created
		$q->delete();
		$this->assertEquals(false, $this->_vfs->hasChild($q->id));
	}
	
	
	
	public function testPush()
	{
		$store = $this->createMock(Store::class);
		$store->expects($this->exactly(3))->method('commit');		// recipientError, newQueueFromErrors, clearLog
		$params = ['root'=>$this->_vfs->url(), 'store'=>$store];
		$q = Queue::create('qname', $params);

		$mail = Builder::createText('mail content here');
		$q->push($mail, 'sender@home.com', 'recipient@here.com', 'Subject here');
		
		$this->assertEquals(1, $q->count);
		$this->assertEquals(strlen('mail content here'), $q->volume);
		
			
		// testing data files
		$qid = $q->id;
		$this->assertEquals(true, $this->_vfs->hasChild("$qid/$qid.0.data"));
		$this->assertEquals(true, $this->_vfs->hasChild("$qid/$qid.0.mail"));
		
		$d = Data::read($q, 0, true);
		$this->assertEquals('Subject here', $d->subject);
		$this->assertEquals('recipient@here.com', $d->to);
		$this->assertStringContainsString("From: sender@home.com", $d->headers);
		$this->assertEquals(Data::STATUS_TOSEND, $d->status);
		
		
		
		$mail = Builder::createText('another mail content here');
		$q->push($mail, 'sender@home.com', 'recipient2@here.com', 'Other subject here');
		$this->assertEquals(true, $this->_vfs->hasChild("$qid/$qid.1.data"));
		$this->assertEquals(true, $this->_vfs->hasChild("$qid/$qid.1.mail"));
		$this->assertEquals(2, $q->count);
		
		$d = Data::read($q, 1, true);
		$this->assertEquals('Other subject here', $d->subject);
		$this->assertEquals('recipient2@here.com', $d->to);
		$this->assertStringContainsString("From: sender@home.com", $d->headers);
		$this->assertEquals(Data::STATUS_TOSEND, $d->status);
			
		
		// recipients
		$this->assertEquals([
				(object)['to'=>'recipient@here.com', 'index'=>0, 'status'=>Data::STATUS_TOSEND],
				(object)['to'=>'recipient2@here.com', 'index'=>1, 'status'=>Data::STATUS_TOSEND]
			],
					 
			$q->recipients());
		
		
		// set a recipient as an error
		$this->assertEquals(0, count($q->sendLog));
		$q->recipientError(1);
		$derr = Data::read($q, 1, true);
		$this->assertEquals(Data::STATUS_ERROR, $derr->status);
		$this->assertEquals(1, count($q->sendLog));
		
		
		// creating new queue with errors
		$q2 = Queue::create('qerr', $params, 50);
		$store->method('createQueue')->willReturn($q2);
		
		$q2 = $q->newQueueFromErrors('qerr');
		$q2id = $q2->id;
		$this->assertEquals(1, $q2->count);
		$this->assertEquals(true, $this->_vfs->hasChild("$q2id/$q2id.0.data"));
		$this->assertEquals(true, $this->_vfs->hasChild("$q2id/$q2id.0.mail"));
		
		// error sending in first queue still here (error data is copied, not moved)
		$this->assertEquals(2, $q->count);

		// reading in error queue
		$d2 = Data::read($q2, 0, true);
		$this->assertEquals($d2->subject, $derr->subject);
		$this->assertEquals($d2->to, $derr->to);
		
		// removing 'X-MailSenderQueue' in order to compare
		$h1 = preg_replace('/X-MailSenderQueue: [0-9a-fA-F]+/', '', $d2->headers);
		$h2 = preg_replace('/X-MailSenderQueue: [0-9a-fA-F]+/', '', $derr->headers);
		
		$this->assertEquals($h1, $h2);
		$this->assertEquals(Data::STATUS_TOSEND, $d2->status);
		
		
		// reading eml content
		$eml = $q->emlAt(0);
		$this->assertStringContainsString("From: sender@home.com\r\n", $eml);
		$this->assertStringContainsString("To: recipient@here.com\r\n", $eml);
		$this->assertStringContainsString("Subject: Subject here\r\n", $eml);
		$this->assertStringContainsString('mail content here', $eml);
		
		
		
		// searching recipient
		$this->assertEquals(false, $q->search('who@home.com'));
		$this->assertEquals(1, $q->search('recipient2@here.com'));		
		
		
		// clearing log
		$q->clearLog();
		$this->assertEquals(0, count($q->sendLog));
	}
	
	
	
	public function testPushAsString()
	{
		$store = $this->createMock(Store::class);
		$params = ['root'=>$this->_vfs->url(), 'store'=>$store];
		$q = Queue::create('qname2', $params);

		$mail = Builder::createText('mail content here');
		$mail->headers->from = 'sender@home.com';
		$q->pushAsString($mail->getContent(), $mail->getAllHeaders()->toString(), 'recipient@here.com', 'Subject here');
		
		$this->assertEquals(1, $q->count);
		$this->assertEquals(strlen('mail content here'), $q->volume);
	}
	
		
			
	public function testSend()
	{
		$store = $this->createMock(Store::class);
		$store->expects($this->exactly(3))->method('commit');		// send, send, commit
		$store->expects($this->once())->method('removeQueue');
		$params = ['root'=>$this->_vfs->url(), 'store'=>$store];
		$q = Queue::create('qname', $params, 1);		// batchcount = 1
		$this->assertEquals(1, $q->batchCount);


		// creating content and pushing to queue
		$mail = Builder::createText('mail content here');
		$q->push($mail, 'sender@home.com', 'recipient@here.com', 'Subject here');
		$mail = Builder::createText('other mail content here');
		$q->push($mail, 'sender@home.com', 'recipient2@here.com', 'Subject2 here');

		$this->assertEquals(2, $q->count);
		$this->assertEquals(NULL, $q->lastBatchDate);
		
		$this->assertEquals(true, $this->_vfs->hasChild("$q->id/$q->id.0.data"));
		$this->assertEquals(true, $this->_vfs->hasChild("$q->id/$q->id.1.data"));

		// sending
		$ms = new \Nettools\Mailing\MailSenders\Virtual();
		$mailer = new Mailer($ms);
		$q->send($mailer);
		
		
		// committing
		$q->commit();
		
		
		// only one mail sent (batchCount = 1)
		$this->assertEquals(false, is_null($q->lastBatchDate));
		$this->assertEquals(1, $q->sendOffset);
		$this->assertEquals(false, $q->locked);

		$d = Data::read($q, 0, true);
		$this->assertEquals(Data::STATUS_SENT, $d->status);
		$d = Data::read($q, 1, true);
		$this->assertEquals(Data::STATUS_TOSEND, $d->status);
		
		
		// send again
		$q->send($mailer);		
		$this->assertEquals(2, $q->sendOffset);
		$this->assertEquals(true, $q->locked);
		
		$d = Data::read($q, 0, true);
		$this->assertEquals(Data::STATUS_SENT, $d->status);
		$d = Data::read($q, 1, true);
		$this->assertEquals(Data::STATUS_SENT, $d->status);

		$this->assertEquals(2, count($ms->getSent()));
		$this->assertStringContainsString('recipient@here.com', $ms->getSent()[0]);
		$this->assertStringContainsString('recipient2@here.com', $ms->getSent()[1]);
		
		
		// resend a mail
		$q->resend($mailer, 0, null, 'newrecipient@here.com');
		$this->assertEquals(3, count($ms->getSent()));
		$this->assertStringContainsString('newrecipient@here.com', $ms->getSent()[2]);
		
		
		$q->delete();
		// $this->assertEquals(false, $this->_vfs->hasChild($q->id)); can't test that because Glob cannot be used with vfsStream
	}
	
	
	
	function testSerialize()
	{
		$store = $this->createMock(Store::class);
		$params = ['root'=>$this->_vfs->url(), 'store'=>$store];
		$q = Queue::create('qname', $params);
		$mail = Builder::createText('mail content here');
		$q->push($mail, 'sender@home.com', 'recipient@here.com', 'Subject here');
		
		$ser = serialize($q);
		$qunser = unserialize($ser);
		
		// get properties to serialize
		$items = $q->__sleep();
		foreach ( $items as $p )
			$this->assertEquals($q->$p, $qunser->$p);
		
		// at the moment, root in not defined, as it is not serialized (so that a queue can be moved, it shouldn't store the full path)
		$this->assertEquals(NULL, $qunser->root);
		$qunser->setup($params);
		$this->assertEquals($q->root, $qunser->root);
	}
	
}


?>