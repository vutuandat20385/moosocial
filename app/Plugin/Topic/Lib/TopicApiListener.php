<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('CakeEventListener', 'Event');

class TopicApiListener implements CakeEventListener {

    public function implementedEvents() {
        return array(
            'ApiHelper.renderAFeed.topic_create' => 'exportTopicCreate',
            'ApiHelper.renderAFeed.topic_item_detail_share' => 'exportTopicItemDetailShare',
        );
    }

    function getTopic($id, $v) {
        $topic = MooCore::getInstance()->getItemByType('Topic_Topic', $id);
        $photoSizes = explode('|', Configure::read('core.photo_image_sizes'));
        $imageArray = array();
        foreach ($photoSizes as $size) {
            $imageArray[$size] = $v->Topic->getImage($topic, array('prefix' => $size));
        }
        return array($topic['Topic']['id'], //id
            //FULL_BASE_URL . $v->request->base . "/topics/view/" . $topic['Topic']['id'] . "/" . seoUrl($topic['Topic']['title']),
            FULL_BASE_URL . str_replace('?','',mb_convert_encoding($topic['Topic']['moo_href'], 'UTF-8', 'UTF-8')), // url
            $v->Text->convert_clickable_links_for_hashtags($v->Text->truncate(strip_tags(str_replace(array('<br>', '&nbsp;'), array(' ', ''), $topic['Topic']['body'])), 200, array('eclipse' => '')), Configure::read('Topic.topic_hashtag_enabled')),
            $topic['Topic']['title'],
            $imageArray,
            $topic['User'],
            $topic
        );
    }

    function exportTopicCreate($e) {
        $data = $e->data['data'];
        $actorHtml = $e->data['actorHtml'];
        list($tpId, $tpUrl, $tpDesc, $tpTitle, $tpImages, $tpUser, $topic) = $this->getTopic($data['Activity']['item_id'], $e->subject());

        //$subject = MooCore::getInstance()->getItemByType('Topic_Topic', $data['Activity']['target_id']);


        list($title_tmp,$target) = $e->subject()->getActivityTarget($data,$actorHtml);
        if(!empty($title_tmp)){
            $title =  $title_tmp['title'];
            $titleHtml = $title_tmp['titleHtml'];
        }else{
            $title = __('created a new topic');
            $titleHtml = $actorHtml . ' ' . __('created a new topic');
        }
        $e->result['result'] = array(
            'type' => 'create',
            'title' => $title,
            'titleHtml' => $titleHtml,
            'objects' => array(
                'type' => 'Topic_Topic',
                'id' => $tpId,
                'url' => $tpUrl,
                'description' => $tpDesc,
                'title' => $tpTitle,
                'images' => $tpImages,
            ),
            'target' => $target,
        );
    }

    function exportTopicItemDetailShare($e) {
        $data = $e->data['data'];
        $actorHtml = $e->data['actorHtml'];

        list($tpId, $tpUrl, $tpDesc, $tpTitle, $tpImages, $tpUser, $topic) = $this->getTopic($data['Activity']['parent_id'], $e->subject());

        $target = array();

        if (isset($data['Activity']['parent_id']) && $data['Activity']['parent_id']):

            $title = $data['User']['name'] . ' ' . __("shared %s's topic", $tpUser['name']);
            $titleHtml = $actorHtml . ' ' . __("shared %s's topic", $e->subject()->Html->link($tpUser['name'], FULL_BASE_URL . $tpUser['moo_href']));
            $target = array(
                'url' => FULL_BASE_URL . $tpUser['moo_href'],
                'id' => $tpUser['id'],
                'name' => $tpUser['name'],
                'type' => 'User',
            );
        endif;
        list($title_tmp,$target) = $e->subject()->getActivityTarget($data,$actorHtml,true);
        if(!empty($title_tmp)){
            $title .=  $title_tmp['title'];
            $titleHtml .= $title_tmp['titleHtml'];
        }
        

        $e->result['result'] = array(
            'type' => 'share',
            'title' => $title,
            'titleHtml' => $titleHtml,
            'objects' => array(
                'type' => 'Topic_Topic',
                'id' => $tpId,
                'url' => $tpUrl,
                'title' => $tpTitle,
                'images' => $tpImages,
                'description'=>$tpDesc,
            ),
            'target' => $target,
        );
    }

}