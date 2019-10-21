<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('CakeEventListener', 'Event');

class MooApiListener implements CakeEventListener
{

    public function implementedEvents()
    {
        return array(
            'ApiHelper.renderAFeed.wall_post' => 'renderAFeed',
            'ApiHelper.renderAFeed.wall_post_link' => 'renderAFeed',
            'ApiHelper.renderAFeed.user_create' => 'renderAFeed',
            'ApiHelper.renderAFeed.user_avatar' => 'renderAFeed',
            
        );
    }


    function exportUserCreate($data, $actorHtml)
    {
        return array('join',
            'Activity',
            $data['User']['name'] . ' ' . __('joined %s', Configure::read('core.site_name')),
            $actorHtml . ' ' . __('joined %s', Configure::read('core.site_name')));
    }

    function exportUserAvatar($data, $actorHtml)
    {
        switch ($data['User']['gender']) {
            case 'Female':
                $title = __('changed her profile picture');
                break;
            case 'Male':
                $title = __('changed his profile picture');
                break;
            default:
                $title = __('changed their profile picture');
        }
        return array('update',
            'Activity',
            $title,
            $actorHtml . ' ' . $title);
    }
         
    function exportWallPost($v, $data, $actorHtml)
    {   
        $objectType = 'Activity';
        $target = $tagUser = $title = '';
        $imageArray = array();
        $activityText = $data['Activity']['content'];
        $activityTextHtml = nl2br($v->Text->autoLink($v->Moo->parseSmilies($data['Activity']['content']),array_merge(array('target' => '_blank', 'rel' => 'nofollow', 'escape' => false),array('no_replace_ssl' => 1))));
        //$activityTextHtml = $v->Moo->parseSmilies($activityTextHtml);
        if (!empty($data['UserTagging']['users_taggings'])) :
                $activityText = array();
                $activityText = $data['Activity']['content'] . $v->MooPeople->getWithNotUrl($data['UserTagging']['id'], $data['UserTagging']['users_taggings'], false);
                $activityTextHtml = nl2br($v->Text->autoLink($v->Moo->parseSmilies($data['Activity']['content']),array_merge(array('target' => '_blank', 'rel' => 'nofollow', 'escape' => false),array('no_replace_ssl' => 1)))) . $v->MooPeople->with($data['UserTagging']['id'], $data['UserTagging']['users_taggings'], false);
                $tagUser = $v->MooPeople->getUserTagged($data['UserTagging']['users_taggings']);
        endif;

        if ($data['Activity']['item_type'] == 'Photo_Album') {
            $imageArray = $v->getImages(explode(',', $data['Activity']['items']));
            /*
            $photoModel = MooCore::getInstance()->getModel('Photo_Photo');
            if (!empty($data['Activity']['items'])) :

                $ids = explode(',', $data['Activity']['items']);
                $photos_total = $photoModel->find('all', array('conditions' => array('Photo.id' => $ids)));
                $photos = $photoModel->find('all', array('conditions' => array('Photo.id' => $ids),
                    //'limit' => 4
                ));

                $imageArray = array();
                $photoSizes = explode('|', Configure::read('core.photo_image_sizes'));
                $i=0;
                foreach ($photos as $photo):
                    foreach ($photoSizes as $size) {
                        $imageArray[$i][$size] = $v->Photo->getImage($photo, array('prefix' => $size));
                    }
                    $i++;
                endforeach;
            elseif ($data['Activity']['action'] == 'photo_item_detail_share')  :
                $photos_total = 1;
                $photo = $photoModel->findById($data['Activity']['parent_id']);
                $imageArray = $v->Photo->getImage($photo, array('prefix' => '850'));
            endif;
            */
        }
        
        return array('post',
            $objectType,
            $title,
            $actorHtml,
            $target,
            $activityText,
            $activityTextHtml,
            $imageArray,
            $tagUser);
    }


    function exportWallPostLink($v, $data, $actorHtml)
    {

        list(, , $title, , $target, $activityText,$activityTextHtml, , $tagUser) = $this->exportWallPost($v, $data, $actorHtml);

        $link = unserialize($data['Activity']['params']);
        $url = (isset($link['url']) ? $link['url'] : $data['Activity']['content']);
        if (!empty($link['image'])) {
            if (strpos($link['image'], 'http') === false) {
                $feedLink['image'] = $v->request->webroot . 'uploads/links/' . $link['image'];
            } else {
                $feedLink['image'] = $link['image'];
            }
        }
        $feedLink['title'] = isset($link['title']) ? h($link['title']) : '';
        $feedLink['link_url'] = $url;
        if (!empty($link['description'])) {
            $feedLink['description'] = ($v->Text->truncate($link['description'], 150, array('exact' => false)));
        }

        $titleHtml = $actorHtml;
        return array('post',
            'Activity_Link',
            $title,
            $titleHtml,
            $target,
            $activityText,
            $activityTextHtml,
            $feedLink,
            $tagUser);
    }

    function renderAFeed($e)
    {
        $v = $e->subject();
        $data = $e->data['data'];

        $actorHtml = $e->data['actorHtml'];
        $activityId = $data['Activity']['id'];
        $tagUser = $title = $target = $type = $objecttType = '';
        $activityText = $activityTextHtml = $title = '';
        $feedLink = $imageArray = array();
        switch ($data['Activity']['action']) {
            case 'user_create':
                list($type, $objectType, $title, $titleHtml) = $this->exportUserCreate($data, $actorHtml);
                break;
            case 'user_avatar':
                list($type, $objectType, $title, $titleHtml) = $this->exportUserAvatar($data, $actorHtml);
                break;
            case 'wall_post':
                list($type, $objectType, $title, $titleHtml, $target, $activityText,$activityTextHtml, $imageArray, $tagUser) = $this->exportWallPost($v, $data, $actorHtml);
                break;
            case 'wall_post_link':
                list($type, $objectType, $title, $titleHtml, $target, $activityText,$activityTextHtml, $feedLink, $tagUser) = $this->exportWallPostLink($v, $data, $actorHtml);
                break;
        }
        list($title_tmp,$target) = $v->getActivityTarget($data,$actorHtml);
        if(!empty($title_tmp)){
            $title =  $title_tmp['title'];
            $titleHtml = $title_tmp['titleHtml'];
        }
        $items = array(
            'type' => $objectType,
            'id' => $activityId,
            'url' => FULL_BASE_URL . $v->Html->url(array(
                    'plugin' => false,
                    'controller' => 'activities',
                    'action' => 'view',
                    $activityId
                )),
            'content' => $activityText ? $activityText : '',
            'contentHtml' => $activityTextHtml ? $activityTextHtml : '',
            'link' => $feedLink ? $feedLink : '',
            'images' => $imageArray ? $imageArray : '',
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