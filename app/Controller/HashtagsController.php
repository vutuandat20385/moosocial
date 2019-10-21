<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class HashtagsController extends AppController
{
    public function getHashtag()
    {
        $conditions = null;
        if($this->request->named['item_table'] != 'all' )
        {
            $item_table = $this->request->named['item_table'];
            if($item_table == 'activities')
                $item_table = array('activities','activity_comments');
            $conditions = array('conditions' => array('Hashtag.item_table' => $item_table ) );
        }
        return $this->Hashtag->find('all',$conditions);
    }
}