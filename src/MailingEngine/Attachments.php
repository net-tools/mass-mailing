<?php

// namespace
namespace Nettools\MassMailing\MailingEngine;


// clauses use
use \Nettools\Mailing\MailBuilder\Builder;




/** 
 * Helper class to deal with attachments
 */
class Attachments extends MixedRelated
{
	/**
	 * Factory method to create an attachment
	 * 
	 * @return \Nettools\Mailing\MailBuilder\Attachment
	 */
	function _poolFactoryMethod()
	{
		return Builder::createAttachment('', '', '');
	}

	
	
	/**
	 * Set the amount of attachments
	 * 
	 * @param int $c
	 */
	public function setAttachmentsCount($c)
	{
		$this->setItemsCount($c);
	}
	
	
	
	/**
	 * Setting an attachment details
	 *
	 * @param string $f File path to content to attach
	 * @param string $fname Filename of attachment
	 * @param string $ftype Content-type
	 * @param int $index Index of mail attachment to set
	 * @param bool $ignoreCache
	 * @return Attachements
	 */
	public function setAttachment($f, $fname, $ftype, $index = 0, $ignoreCache = false)
	{
		if ( $pj = $this->getItem($index) )
		{
			$pj->setFile($f);
			$pj->setFileName($fname);
			$pj->setContentType($ftype);
			$pj->setIgnoreCache($ignoreCache);
		}
		
		return $this; // chaining
	}

	
	
	/** 
	 * Set all attachements in on call
	 * 
	 * @param array $attachments Array of associative arrays with keys : file, filename, contentType, ignoreCache
	 */
	public function setAttachments(array $attachments)
	{
		$this->setAttachmentsCount(count($attachments));
		
		for ( $i = 0 ; $i < count($attachments) ; $i++ )
		{
			$a = $attachments[$i];
			$this->setAttachment($a['file'], $a['filename'], $a['contentType'], $i, !empty($a['ignoreCache']) ? $a['ignoreCache'] : false);
		}
		
		return $this; // chaining
	}

	
	
	/**
	 * Render email 
	 *
	 * @param mixed $data
	 * @return \Nettools\Mailing\MailBuilder\Multipart
	 */
	protected function _render($data)
	{
		// get a Content object and add on top an Attachment object
		return Builder::addAttachmentObjects(parent::_render($data), $this->items);
	}
}

?>