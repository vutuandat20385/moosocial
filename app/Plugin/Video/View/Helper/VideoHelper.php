<?php
App::uses('AppHelper', 'View/Helper');

class VideoHelper extends AppHelper
{
    public $helpers = array('Storage.Storage');

    public function getTagUnionsVideo($videoids)
    {
        return "SELECT i.id, i.title, i.description as body, i.like_count, i.created, 'Video_Video' as moo_type, i.privacy, i.user_id
						 FROM " . Configure::read('core.prefix') . "videos i						 
						 WHERE i.id IN (" . implode(',', $videoids) . ")";// AND i.privacy = ".PRIVACY_EVERYONE
    }

    public function getEnable()
    {
        return Configure::read('Video.video_enabled');
    }

    public function checkPostStatus($video, $uid)
    {
        if (!$uid)
            return false;
        $friendModel = MooCore::getInstance()->getModel('Friend');
        if ($uid == $video['Video']['user_id'])
            return true;

        if ($video['Video']['privacy'] == PRIVACY_EVERYONE) {
            return true;
        }

        if ($video['Video']['privacy'] == PRIVACY_FRIENDS) {
            $areFriends = $friendModel->areFriends($uid, $video['Video']['user_id']);
            if ($areFriends)
                return true;
        }


        return false;
    }

    public function checkSeeComment($video, $uid)
    {
        if ($video['Video']['privacy'] == PRIVACY_EVERYONE) {
            return true;
        }

        return $this->checkPostStatus($video, $uid);
    }

    public function getItemSitemMap($name, $limit, $offset)
    {
        if (!MooCore::getInstance()->checkPermission(null, 'video_view'))
            return null;

        $videoModel = MooCore::getInstance()->getModel("Video.Video");
        $videos = $videoModel->find('all', array(
            'conditions' => array(
                'Video.group_id' => 0,
                'Video.privacy' => PRIVACY_EVERYONE
            ),
            'limit' => $limit,
            'offset' => $offset
        ));

        $urls = array();
        foreach ($videos as $video) {
            $urls[] = FULL_BASE_URL . $video['Video']['moo_href'];
        }

        return $urls;
    }

    public function getImage($item, $options)
    {
        $prefix = (isset($options['prefix'])) ? $options['prefix'] . '_' : '';
        return $this->Storage->getUrl($item[key($item)]['id'], $prefix, $item[key($item)]['thumb'], "videos");
    }
    
    public function getVideo($aVideo)
    {
        $sPath = 'uploads/videos/thumb/' . $aVideo['Video']['id'] . '/' . $aVideo['Video']['destination'];
        return $this->Storage->getUrl($aVideo['Video']['id'], 0, 0, "uploadVideo", array('path' => $sPath));
    }

}
