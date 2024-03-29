<?php 

namespace Nettools\MassMailing\QueueEngine\Tests;



use \Nettools\MassMailing\QueueEngine\Data;
use \Nettools\MassMailing\QueueEngine\Queue;
use \org\bovigo\vfs\vfsStream;




class DataTest extends \PHPUnit\Framework\TestCase
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
		$d = Data::read($q, 0, true);
	}
	
	
	public function testMSQNoFileNoThrow()
	{
		$params = ['root'=>$this->_vfs->url()];
		$q = Queue::create('qname', $params);

		// reading inexistant file
		$d = Data::read($q, 0, false);
		$this->assertEquals(null, $d);
	}
	
	
	public function testMSQCreate()
	{
		$params = ['root'=>$this->_vfs->url()];
		$q = Queue::create('qname', $params);
		$this->assertEquals(true, $this->_vfs->hasChild($q->id));

		$data = (object)['to' => 'recipient@domain.tld', 'headers'=>'From: me@home.com', 'subject'=>'Fun subject', 'status'=>Data::STATUS_TOSEND];
		$d = new Data($q, 0);
		$d->from($data);
		
		$this->assertEquals($d->to, $data->to);
		$this->assertEquals($d->headers, $data->headers);
		$this->assertEquals($d->subject, $data->subject);
		$this->assertEquals($d->status, $data->status);
		
		$d->write();
		$qid = $q->id;
		$this->assertEquals(true, file_exists($params['root'] . "/$qid/$qid.0.data"));
		
		
		$d2 = Data::read($q, 0, true);
		$this->assertEquals($d2->to, $d->to);
		$this->assertEquals($d2->headers, $d->headers);
		$this->assertEquals($d2->subject, $d->subject);
		$this->assertEquals($d2->status, $d->status);
	}
}


?>