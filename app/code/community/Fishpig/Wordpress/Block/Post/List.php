<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Block_Post_List extends Fishpig_Wordpress_Block_Post_Abstract
{
	/**
	 * Cache for post collection
	 *
	 * @var Fishpig_Wordpress_Model_Resource_Post_Collection
	 */
	protected $_postCollection = null;
	
	/**
	 * Returns the collection of posts
	 *
	 * @return Fishpig_Wordpress_Model_Mysql4_Post_Collection
	 */
	public function getPosts()
	{
		return $this->_getPostCollection();
	}
	
	/**
	 * Generates and returns the collection of posts
	 *
	 * @return Fishpig_Wordpress_Model_Mysql4_Post_Collection
	 */
	protected function _getPostCollection()
	{
		if (is_null($this->_postCollection) && $this->getWrapperBlock()) {
			$this->_postCollection = $this->getWrapperBlock()->getPostCollection();
			
			if ($this->_postCollection) {
				if ($this->getPostType()) {
					//die("post");
					$this->_postCollection->addPostTypeFilter($this->getPostType());
				}
				
				if ($this->getPagerBlock()) {
					//die("pager");
					$this->getPagerBlock()->setCollection($this->_postCollection);
				}
			}
		}
		
		return $this->_postCollection;
	}
	
	/**
	 * Sets the parent block of this block
	 * This block can be used to auto generate the post list
	 *
	 * @param Fishpig_Wordpress_Block_Post_List_Wrapper_Abstract $wrapper
	 * @return $this
	 */
	public function setWrapperBlock(Fishpig_Wordpress_Block_Post_List_Wrapper_Abstract $wrapper)
	{
		return $this->setData('wrapper_block', $wrapper);
	}
	
	/**
	 * Get the pager block
	 * If the block isn't set in the layout XML, it will be created and will use the default template
	 *
	 * @return Fishpig_Wordpress_Post_List_Pager
	 */
	public function getPagerBlock()
	{
		if (!$this->hasPagerBlock()) {
			$this->setPagerBlock(false);
			
			if ($pager = $this->getChild('pager')) {
				$this->setPagerBlock(
					$pager->setPostListBlock($this)
				);
			}
		}
		
		return $this->_getData('pager_block');
	}
	
	/**
	 * Get the HTML for the pager block
	 *
	 * @return string
	 */
	public function getPagerHtml()
	{
		return $this->getChildHtml('pager');
	}
	
	/**
	 * Retrieve the correct renderer and template for $post
	 *
	 * @param Fishpig_Wordpress_Model_Post_Abstract $post
	 * @return Fishpig_Wordpress_Block_Post_List_Renderer
	 */
	public function getPostRenderer(Fishpig_Wordpress_Model_Post_Abstract $post)
	{
		if (!$this->hasPostRenderer()) {
			$this->setPostRenderer(
				$this->getLayout()->createBlock('wordpress/post_list_renderer')
					->setParentBlock($this)
					->setExcerptSize($this->getExcerptSize())
			);
		}

		return $this->_getData('post_renderer')
			->setPost($post)
			->setTemplate(
				$post->getPostListTemplate() ? $post->getPostListTemplate() : $this->getPostRendererTemplate()
			);
	}
	
	/**
	 * Get the post renderer template
	 *
	 * @return string
	 */
	public function getPostRendererTemplate()
	{
		if (!$this->hasPostRendererTemplate()) {
			$this->setPostRendererTemplate('wordpress/post/list/renderer/default.phtml');
		}
		
		return $this->_getData('post_renderer_template');
	}

	/**
	 * Ensure that the post list handle is set (adds the pager)
	 *
	 * @return $this
	 */
	protected function _prepareLayout()
	{
		$this->getLayout()
			->getUpdate()
			->addHandle('wordpress_post_list');

		return parent::_prepareLayout();
	}
	
	/**
	 * Ensure a valid template is set
	 *
	 * @return $this
	 */
	protected function _beforeToHtml()
	{
		if (!$this->getTemplate()) {
			$this->setTemplate('wordpress/post/list.phtml');
		}
		
		return parent::_beforeToHtml();
	}
}
