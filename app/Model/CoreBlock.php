<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class CoreBlock extends AppModel{    

    
    public function getCoreBlockById($id)
    {
        $core_block = $this->findById($id);
        if(empty($core_block))
        {
            $this->locale = $this->default_locale;
            $core_block = $this->findById($id);
        }
        return $core_block;
    }
    
    public function getBlocks()
    {
    	if ($this->_blocks)
    	{
    		return $this->_blocks;
    	}
    	
    	$blocks = Cache::read("core.blocks");
    	if (!$blocks)
    	{
    		$blocks = $this->find('all',
    			array(
            		'conditions' => array('CoreBlock.is_active' => '1'))
    			);    		
    		if (count($blocks))
    		{
    			$temp = array();
    			foreach ($blocks as $block)
    			{
    				$temp[$block['CoreBlock']['id']] = $block;
    			}
    			$blocks = $temp;
    		}
    		Cache::write("core.blocks", $blocks);
    	}
    	$this->_blocks = $blocks;
    	return $blocks;
    }
}