<?php 

namespace Nettools\MassMailing\QueueEngine\Tests;



use \Nettools\MassMailing\QueueEngine\Queue;
use \Nettools\MassMailing\QueueEngine\Store;
use \org\bovigo\vfs\vfsStream;




class StoreTest extends \PHPUnit\Framework\TestCase
{
	protected $_vfs = NULL;
	

	public function setUp() :void
	{
		$this->_vfs = vfsStream::setup('root');
	}
	
	
	
	public function testEmptyStoreException()
	{
		$root = $this->_vfs->url();
		$this->expectException(\Nettools\Mailing\Exception::class);
		
		// store does not exist, exception thrown
		Store::read($root);
	}
	
	
	
	public function testEmptyStoreCreated()
	{
		$root = $this->_vfs->url();
		
		// store does not exist, exception thrown
		$s = Store::read($root, true);
		$this->assertEquals(0, count($s->queues));
		$this->assertEquals(true, $this->_vfs->hasChild('store.serialized'));
	}
	
	
	
	public function testStore()
	{
		$root = $this->_vfs->url();
		
		// creating empty store
		$s = new Store($root);
		$this->assertEquals(0, count($s->queues));
		
		
		// saving to disk
		$s->commit();
		$this->assertEquals(true, $this->_vfs->hasChild('store.serialized'));
		$size1 = filesize("$root/store.serialized");

		
		// creating a queue
		$q = $s->createQueue('qtitle');
		$this->assertEquals(1, count($s->queues));
		clearstatcache();
		$size2 = filesize("$root/store.serialized");
		$this->assertEquals(true, $size1 != $size2);
		
			
		// getting a queue
		$this->assertEquals($s->getQueue($q->id), $q);

						
		// unserializing store
		$s2 = Store::read($root);
		$this->assertEquals($s->queues, $s2->queues);
		
		
		// removing queue
		$s->removeQueue($q);
		$this->assertEquals(0, count($s->queues));
		clearstatcache();
		$size2 = filesize("$root/store.serialized");
		$this->assertEquals(true, $size1 == $size2);
	}
	
	
	public function testListClear()
	{
		$root = $this->_vfs->url();
		
		// creating empty store
		$s = new Store($root);
		$this->assertEquals(0, count($s->queues));
		$s->commit();
		$size1 = filesize("$root/store.serialized");
		
				
		// creating queues
		$q1 = $s->createQueue('qtitle');
		$q2 = $s->createQueue('qtitle2');
		$this->assertEquals(2, count($s->queues));
		
		
		// listing queue
		$l = $s->getList(Store::SORT_TITLE);
		$this->assertEquals(2, count($l));
		$this->assertEquals([$q1->id => $q1, $q2->id => $q2], $l);
		
			
		// clearing queues
		$s->clear();
		$this->assertEquals(0, count($s->queues));
		clearstatcache();
		$size2 = filesize("$root/store.serialized");
		$this->assertEquals(true, $size1 == $size2);
	}
}


?>