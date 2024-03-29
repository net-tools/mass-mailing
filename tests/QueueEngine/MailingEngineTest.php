<?php 

namespace Nettools\MassMailing\QueueEngine\Tests;



use \Nettools\Mailing\Mailer;
use \Nettools\MassMailing\QueueEngine\Store;
use \Nettools\MassMailing\QueueEngine\Queue;
use \Nettools\MassMailing\MailingEngine\Engine;
use \org\bovigo\vfs\vfsStream;
use \org\bovigo\vfs\vfsStreamDirectory;




class MailingEngineTest extends \PHPUnit\Framework\TestCase
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
	
	
		
	public function testMSH()
	{
		function __ready($msh)
		{
			try
			{
				$msh->ready();
				return true;
			}
			catch( \Nettools\MassMailing\MailingEngine\Exception $e )
			{
				return false;
			}
		}
		
		
		function __getBoundary($str, $prefix = '')
		{
			// multipart/alternative;\r\n boundary=\"$boundary\"
			
			if ( $prefix )
				$prefix = "$prefix;\r\n";
			
			if ( preg_match('|' . $prefix . ' boundary="([^"]+)"|', $str, $regs) )
				return $regs[1];
			else
				return '';
		}
		
		
		
		$ml = new Mailer(new \Nettools\Mailing\MailSenders\Virtual());
		$msh = new Engine($ml, 'msh content', 'text/plain', 'unit-test@php.com', 'test subject', 
										[
											'template' => 'my template : %content%',
											'queue' => 'queuename',
											'queueParams' => ['root' => $this->_queuePath, 'batchCount' => 10]
										]);
		$ml->setMailSender(new \Nettools\Mailing\MailSenders\Virtual(), NULL);
		$msh->prepareAndSend('user-to@php.com');
		$msh->closeQueue();
		$sent = $ml->getMailerEngine()->getMailSender()->getSent();
		$this->assertCount(0, $sent);								// no mail sent yet, as we use a queue
		
		$msq = Store::read($this->_queuePath, true);
		$queues = $msq->getList(Store::SORT_DATE);
		$this->assertCount(1, $queues);
		$key = key($queues);
		$q = current($queues);
		$this->assertEquals('queuename_' . date("Ymd"), $q->title);
		$this->assertEquals(1, $q->count);
		$this->assertEquals(false, $q->locked);
		$this->assertEquals(0, $q->sendOffset);
		$q->send($ml);
		$sent = $ml->getMailerEngine()->getMailSender()->getSent();
		$this->assertCount(1, $sent);								// one mail from queue sent
		$this->assertEquals(true, is_int(strpos($sent[0], 'my template : msh content')));
		$boundary = __getBoundary($sent[0]);
		$this->assertStringContainsString(
				"Content-Type: multipart/alternative;\r\n boundary=\"$boundary\"\r\n" .
				"From: unit-test@php.com\r\n" .
				"X-MailSenderQueue: " . $q->id . "\r\n",
				$sent[0]);

		$this->assertStringContainsString(
				"To: user-to@php.com\r\n" .
				"Subject: test subject\r\n",
				$sent[0]);
		$this->assertStringContainsString(
				"Delivered-To: user-to@php.com\r\n" .
				"\r\n" .
				"--$boundary\r\n",
				$sent[0]);
		$this->assertMatchesRegularExpression('/Message-ID: <[0-9a-f]+@php.com>/', $sent[0]);
		$this->assertMatchesRegularExpression('/Date: [A-Z][a-z]{2,4}, [0-9]{1,2} [A-Z][a-z]{2,4} 20[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2} .[0-9]{4}/', $sent[0]);
	}
}


?>