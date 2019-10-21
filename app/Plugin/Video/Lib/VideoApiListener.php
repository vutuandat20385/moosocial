<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('CakeEventListener', 'Event');

class VideoApiListener implements CakeEventListener
{

    public function implementedEvents()
    {
        return array(
            'ApiHelper.renderAFeed.video_create' => 'exportVideoCreate',
            'ApiHelper.renderAFeed.video_item_detail_share' => 'exportVideoItemDetailShare',
            'ApiHelper.renderAFeed.video_activity' => 'exportVideoActivity',
        );
    }
    function getVideoInfo($id,$v,$uacos){
        $object = MooCore::getInstance()->getItemByType("Video_Video", $id);
        $photoSizes = explode('|', Configure::read('core.photo_image_sizes'));
        $videoHelper = MooCore::getInstance()->getHelper('Video_Video');
        $imageArray = array();
        foreach ($photoSizes as $size) {
            $imageArray[$size] = $v->Video->getImage($object, array('prefix' => $size));
        }
        if (!empty($object['Video']['group_id'])) {
            $url = FULL_BASE_URL . $v->request->base . '/groups/view/' . $object['Video']['group_id'] . '/video_id:' . $object['Video']['id'];
        } else {
            $url = FULL_BASE_URL . $v->request->base . '/videos/view/' . $object['Video']['id'] . '/' . str_replace('?','',mb_convert_encoding(seoUrl($object['Video']['title']), 'UTF-8', 'UTF-8'));
        }
        $videoSource = '';
        if($object['Video']['pc_upload']) {
            $videoSource = $videoHelper->getVideo($object);
        }
        $canView = false;
        if(in_array('video_view',$uacos))
        {
            $canView = true;
        }
        $objectsExport =  array(
            'type' => 'Video_Video',
            'id' => $object["Video"]['id'],
            'url' => $url,
            'title' => $v->Text->truncate($object['Video']['title'], 140, array('exact' => false)),
            'images' => $imageArray,
            'description' => $v->Text->convert_clickable_links_for_hashtags($v->Text->truncate(strip_tags(str_replace(array('<br>', '&nbsp;'), array(' ', ''), $object['Video']['description'])), 200, array('eclipse' => '')), Configure::read('Video.video_hashtag_enabled')),
            'pcUpload'=>$object['Video']['pc_upload'],
            'source'=>$object['Video']['source'],
            'source_id'=>$object['Video']['source_id'],
            'videoSource'=>$videoSource,
            'canView'=>$canView,
        );
        return array($object,$objectsExport);
    }

    function exportVideoCreate($e)
    {
        $data = $e->data['data'];
        $v = $e->subject();
        $actorHtml = $e->data['actorHtml'];
        $uacos = $e->data['uacos'];
        
        list($object,$objectsExport)= $this->getVideoInfo($data['Activity']['item_id'],$v,$uacos) ;
        $subject = MooCore::getInstance()->getItemByType($data['Activity']['type'], $data['Activity']['target_id']);
        $name = key($subject);
        $title = $titleHtml = $target = '';
        if ($data['Activity']['target_id']) {
            list(, $name) = mooPluginSplit($data['Activity']['type']);
            $show_subject = MooCore::getInstance()->checkShowSubjectActivity($subject);
            if ($data['Activity']['type'] == 'Group_Group') {
                if ($show_subject) {
                    $title = $data['User']['name'] . ' > ' . $object[$name]['moo_title'];
                    $titleHtml = $actorHtml . ' > ' . $v->Html->link($object[$name]['moo_title'], FULL_BASE_URL . $object[$name]['moo_href']);
                } else {
                    $title = __('shared a new video');
                    $titleHtml = $actorHtm . ' ' . __('shared a new video');
                }
                $target = array(
                    'url' => FULL_BASE_URL . $object[$name]['moo_href'],
                    'id' => $object[$name]['id'],
                    'name' => $object[$name]['moo_title'],
                    'type' => 'Group',
                );
            }
        } else {
            $title = __('shared a new video');
            $titleHtml = $actorHtml . ' ' . __('shared a new video');
        }


        $e->result['result'] = array(
            'type' => 'create',
            'title' => $title,
            'titleHtml' => $titleHtml,
            'objects' => $objectsExport,
            'target' => $target,
        );
    }

    function exportVideoItemDetailShare($e)
    {
        $data = $e->data['data'];
        $actorHtml = $e->data['actorHtml'];
        $v = $e->subject();
        $title = $titleHtml = $target = '';

        $type = 'share';
        $uacos = $e->data['uacos'];

        list($video,$objectsExport)= $this->getVideoInfo($data['Activity']['parent_id'],$v,$uacos) ;
        if (isset($data['Activity']['parent_id']) && $data['Activity']['parent_id']):
            $videoModel = MooCore::getInstance()->getModel('Video_Video');
            $video = $videoModel->findById($data['Activity']['parent_id']);

            $title = $data['User']['name'] . ' ' . __("shared %s's video", $video['User']['name']);
            $titleHtml = $actorHtml . ' ' . __("shared %s's video", $v->Html->link($video['User']['name'], FULL_BASE_URL . $video['User']['moo_href']));
            $target = array(
                'url' => FULL_BASE_URL . $video['User']['moo_href'],
                'id' => $video['User']['id'],
                'name' => $video['User']['name'],
                'type' => 'User',
            );
            $videoArray = array(
                'type' => 'Video_Video',
                'id' => $video["Video"]['id'],
                'url' => FULL_BASE_URL . $video['Video']['moo_href'],
            );
        endif;
        if ($data['Activity']['target_id']) {
            $subject = MooCore::getInstance()->getItemByType($data['Activity']['type'], $data['Activity']['target_id']);

            list(, $name) = mooPluginSplit($data['Activity']['type']);
            $show_subject = MooCore::getInstance()->checkShowSubjectActivity($subject);

            if ($show_subject) {
                $type = 'share_user';
                $title .= ' > ' . $subject[$name]['moo_title'];
                $titleHtml .= ' > ' . $v->Html->link($subject[$name]['moo_title'], FULL_BASE_URL . $subject[$name]['moo_href']);
                $target = array(
                    'url' => FULL_BASE_URL . $subject[$name]['moo_href'],
                    'id' => $subject[$name]['id'],
                    'name' => $subject[$name]['name'],
                    'type' => $subject[$name]['moo_plugin'] ? $subject[$name]['moo_plugin'] : $subject[$name]['moo_type'],
                );
                $videoMore = array(
                    'user_url' => FULL_BASE_URL . $video['User']['moo_href'],
                    'user_id' => $video['User']['id'],
                    'user_name' => $video['User']['name'],
                );
            }
        }


        $items = $videoArray ? $videoArray : '';
        if ($type == 'share_user') $items = array_merge($items, $videoMore);

        $e->result['result'] = array(
            'type' => 'share',
            'title' => $title,
            'titleHtml' => $titleHtml,
            'objects' => $objectsExport,
            'target' => $target,
        );
    }

    function exportVideoActivity($e)
    {
        $data = $e->data['data'];
        $v = $e->subject();
        $actorHtml = $e->data['actorHtml'];
        $uacos = $e->data['uacos'];
        
        $objectType = 'Activity_Link';
        $tagUser = $title = $target = $type = $objecttType = '';
        $activityText = $activityTextHtml = $title = '';

        $activityText = $data['Activity']['content'];
        $activityTextHtml = nl2br($v->Text->autoLink($v->Moo->parseSmilies($data['Activity']['content']), array_merge(array('target' => '_blank', 'rel' => 'nofollow', 'escape' => false),array('no_replace_ssl' => 1))));
        if (!empty($data['UserTagging']['users_taggings'])) :
                $activityText = array();
                $activityText = $data['Activity']['content'] . $v->MooPeople->getWithNotUrl($data['UserTagging']['id'], $data['UserTagging']['users_taggings'], false);
                $activityTextHtml = nl2br($v->Text->autoLink($v->Moo->parseSmilies($data['Activity']['content']), array_merge(array('target' => '_blank', 'rel' => 'nofollow', 'escape' => false),array('no_replace_ssl' => 1)))) . $v->MooPeople->with($data['UserTagging']['id'], $data['UserTagging']['users_taggings'], false);
                $tagUser = $v->MooPeople->getUserTagged($data['UserTagging']['users_taggings']);
        endif;
        $title = $data['User']['name'];
        $titleHtml = $actorHtml;
        list($title_tmp, $target) = $e->subject()->getActivityTarget($data, $actorHtml);
        if (!empty($title_tmp)) {
            $title = isset($title_tmp['name']) ? $title_tmp['name'] : $title_tmp['title'] ;
            $titleHtml = $title_tmp['titleHtml'];
        }
        $video = json_decode($data['Activity']['params'],true);
        $videoObject = array (
            'source' => $video['source'],
            'source_id' => $video['source_id'],
            'thumb' => $video['thumb'],
            'title' => $video['title'],
            'description' => h($v->Text->truncate($v->Text->convert_clickable_links_for_hashtags($video['description'], Configure::read('Video.video_hashtag_enabled') ), 200, array('exact' => false))),
        );

        $items = array(
            'type' => $objectType,
            'id' => $data['Activity']['id'],
            'url' => FULL_BASE_URL . $v->Html->url(array(
                    'plugin' => false,
                    'controller' => 'activities',
                    'action' => 'view',
                    $data['Activity']['id'],
                )),
            'content' => $activityText ? $activityText : '',
            'contentHtml' => $activityTextHtml ? $activityTextHtml : '',
            'videoObject' => $videoObject ? $videoObject : '',
            'tagUser' => $tagUser ? $tagUser : '',
        );

        $e->result['result'] = array(
            'title' => $title,
            'titleHtml' => isset($titleHtml) ? $titleHtml : '',
            'type' => $type,
            'objects' => $items,
            'target' => $target,
        );
    }
}
