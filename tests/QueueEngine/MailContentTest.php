<?php 

namespace Nettools\MassMailing\QueueEngine\Tests;



use \Nettools\MassMailing\QueueEngine\MailContent;
use \Nettools\MassMailing\QueueEngine\Queue;
use \org\bovigo\vfs\vfsStream;



class MailContentTest extends \PHPUnit\Framework\TestCase
{
	protected $_vfs = NULL;
	

	public function setUp() :void
	{
		$this->_vfs = vfsStream::setup('root');
	}
	
	
		
	public function testMSQNoFile()
	{
		$params = ['root'=>$this->_vfs->url()];
		$q = Queue::create('qname', $params);

		// reading inexistant file
		$this->expectException(\Nettools\Mailing\Exception::class);
		$d = MailContent::read($q, 0, true);
	}
	
	
	public function testMSQNoFileNoThrow()
	{
		$params = ['root'=>$this->_vfs->url()];
		$q = Queue::create('qname', $params);

		// reading inexistant file
		$d = MailContent::read($q, 0, false);
		$this->assertEquals(null, $d);
	}
	
	
	public function testMSQCreate()
	{
		$params = ['root'=>$this->_vfs->url()];
		$q = Queue::create('qname', $params);

		$content = 'my email';
		$m = new MailContent($q, 0);
		$m->from($content);
		
		$this->assertEquals($m->content, $content);
		
		$m->write();
		$qid = $q->id;
		$this->assertEquals(true, file_exists($params['root'] . "/$qid/$qid.0.mail"));
		
		
		$m2 = MailContent::read($q, 0, true);
		$this->assertEquals($m2->content, $m->content);
	}
}


?>