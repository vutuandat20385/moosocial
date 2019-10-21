<?php
App::uses('AppHelper', 'View/Helper');

class TopicHelper extends AppHelper
{
    public $helpers = array('Storage.Storage');

    public function getTagUnionsTopic($topicids)
    {
        return "SELECT i.id, i.title, i.body, i.like_count, i.created, 'Topic_Topic' as moo_type, 0 as privacy, i.user_id
						 FROM " . Configure::read('core.prefix') . "topics i
						 WHERE i.id IN (" . implode(',', $topicids) . ")";
    }

    public function getEnable()
    {
        return Configure::read('Topic.topic_enabled');
    }

    public function getItemSitemMap($name, $limit, $offset)
    {
        if (!MooCore::getInstance()->checkPermission(null, 'topic_view'))
            return null;

        $topicModel = MooCore::getInstance()->getModel("Topic.Topic");
        $topics = $topicModel->find('all', array(
            'conditions' => array('Topic.group_id' => 0),
            'limit' => $limit,
            'offset' => $offset
        ));

        $urls = array();
        foreach ($topics as $topic) {
            $urls[] = FULL_BASE_URL . $topic['Topic']['moo_href'];
        }

        return $urls;
    }

    public function getImage($item, $options)
    {
        $prefix = (isset($options['prefix'])) ? $options['prefix'] . '_' : '';
        return $this->Storage->getUrl($item[key($item)]['id'], $prefix, $item[key($item)]['thumbnail'], "topics");

    }

    public function checkPostStatus($topic, $uid)
    {
        $cuser = MooCore::getInstance()->getViewer();

        if (isset($cuser) && $cuser['Role']['is_admin']) {
            return true;
        }

        if (isset($topic['Topic']['locked']) && $topic['Topic']['locked']) {
            return false;
        }

        return true;
    }

    public function checkSeeComment($topic, $uid)
    {
        return true;
    }

}
