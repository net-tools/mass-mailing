<?php

// namespace
namespace Nettools\MassMailing\MailingEngine;





/**
 * Subclass of Engine to deal with attachments and embeddings
 */
abstract class MixedRelated extends Engine
{
	protected $component = NULL;
	protected $itemsPool = NULL;
	protected $items = NULL;


	
	/**
	 * Do some init stuff after object is constructed
	 */
	protected function _initialize()
	{		
		// create pool to deal smartly with instances between many call of render method
		$this->itemsPool = new \Nettools\Core\Containers\Pool(array($this, '_poolFactoryMethod'));
		$this->items = array();
	}
	
	
	
	/**
	 * Factory method
	 * 
	 * @return \Nettools\Mailing\MailBuilder\MixedRelated
	 */
	abstract function _poolFactoryMethod();
	
	
		
	/** 
	 * Testing required parameters
	 *
	 * @throws \Nettools\MassMailing\MailingEngine\Exception
	 */
	public function ready()
	{
		if ( empty($this->itemsPool) ) 
			throw new \Nettools\MassMailing\MailingEngine\Exception('Items pool not initialized');
			
		
		// call underlying object ready method, which will throw an exception if something wrong
		parent::ready();
	}
	

	
	/**
	 * Set attachements count, and update pool as necessary
	 *
	 * @param int $c
	 */
	public function setItemsCount($c)
	{
		// if we ask for more items that we have in pool, increasing it
		if ( count($this->items) < $c )
		{
			for ( $i = count($this->items) ; $i < $c ; $i++ )
				// ask for an object thanks to pool->get
				$this->items[] = $this->itemsPool->get();
		}
		
		// if we ask for less items that we have in pool, replacing those unneeded back in pool
		elseif ( $c < count($this->items) )
		{
			$attcount = count($this->items);
			for ( $i = $c ; $i < $attcount ; $i++ )
				$this->itemsPool->release($this->items[$i]);
				
			// trimming array
			array_splice($this->items, 0, $c);
		}
	}
	
	
	
	/**
	 * Get an item
	 * 
	 * @param int $index
	 * @return \Nettools\Mailing\MailBuilder\MixedRelated
	 * @throws \Nettools\MassMailing\MailingEngine\Exception
	 */
	public function getItem($index = 0)
	{
		if ( $index < count($this->items) )
			return $this->items[$index];
		else
			throw new \Nettools\MassMailing\MailingEngine\Exception("Index value is incorrect ($index)");
	}
}

?>