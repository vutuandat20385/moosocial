<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('CakeEventListener', 'Event');

class PhotoApiListener implements CakeEventListener {

    public function implementedEvents() {
        return array(
            'ApiHelper.renderAFeed.photos_add' => 'exportPhotoAdd',
            'ApiHelper.renderAFeed.album_item_detail_share' => 'exportAlbumItemDetailShare',
            'ApiHelper.renderAFeed.photo_item_detail_share' => 'exportPhotoItemDetailShare',
            'ApiHelper.renderAFeed.photos_tag' => 'exportPhotosTag',
            'ApiHelper.renderAFeed.comment_add_photo' => 'exportCommentAddPhoto',
        );
    }

    function exportPhotoAdd($e) {
        $data = $e->data['data'];
        $objectPlugin = $e->data['objectPlugin'];
        $actorHtml = $e->data['actorHtml'];
        
        
        $objects = $target = array();

        $imageArray = $e->subject()->getImages(explode(',', $data['Activity']['items']));


        list($title_tmp, $target_tmp) = $e->subject()->getActivityTarget($data, $actorHtml);
        if (!empty($title_tmp)) {
            $title = $title_tmp['title'];
            $titleHtml = $title_tmp['titleHtml'];
            $target = $target_tmp;
            $url = FULL_BASE_URL . $e->subject()->Html->url(array(
                        'plugin' => false,
                        'controller' => 'activities',
                        'action' => 'view',
                        $data['Activity']['id']
                    ));
            //echo '<pre>';print_r($data);die;
            //if(count($imageArray) == 1) {
                //$url = FULL_BASE_URL . $e->subject()->request->base . "/activities/view/" . $data['Activity']['id'] . "?targetPhotoId=" . $data['Activity']['items'];
            //}
            $objects = array(
                'type' => (empty($data['Activity']['params'])) ? 'activity' : $data['Activity']['item_type'],
                'id' => $data['Activity']['id'],
                //'url' => FULL_BASE_URL . $e->subject()->request->base . "/albums/view/" . $subject['Album']['id'] . "/" . seoUrl($subject['Album']['title']),
                'url' => $url,
                'images' => $imageArray,
                'photoCount' => count($imageArray),
            );
        } else {
            if($data['Activity']['item_type'] != 'Photo_Album') {
                if($data['Activity']['type'] != 'Photo_Album') {
                    $subject = MooCore::getInstance()->getItemByType($data['Activity']['type'], $data['Activity']['target_id']);
                }
                else {
                    $subject = MooCore::getInstance()->getItemByType($data['Activity']['item_type'], $data['Activity']['target_id']);
                }
            }
            else {
                $subject = MooCore::getInstance()->getItemByType('Photo_Album', $data['Activity']['item_id']);
            }

            $name = key($subject);
            $number = count(explode(',', $data['Activity']['items']));
            if($data['Activity']['item_type'] != 'Photo_Album') {
                $title = ($number > 1) ? __('added %s new photos', $number) : __('added %s new photo', $number);
            }
            else {
                $title = ($number > 1) ? __('added %s new photos to album', $number) : __('added %s new photo to album', $number);
            }
            
            if (isset($objectPlugin) && $objectPlugin):		
                $title .= __(' <a href="%1$s">%2$s</a>', $objectPlugin[$name]['moo_href'], h($objectPlugin[$name]['moo_title']));
            endif;

            //$titleHtml = $actorHtml . ' ' . $title . ' ' . $e->subject()->Html->link(h($subject[$name]['moo_title']), FULL_BASE_URL . str_replace('?','',mb_convert_encoding($subject[$name]['moo_href'], 'UTF-8', 'UTF-8')));
            $titleHtml = $actorHtml . ' ' . $title ;
            //$title .= ' ' . h($subject[$name]['moo_title']);

            if($name == 'Group') {
                $target = array(
                    'id' => $subject[$name]['id'],
                    'url' => FULL_BASE_URL . str_replace('?','',mb_convert_encoding($subject[$name]['moo_href'], 'UTF-8', 'UTF-8')), // url
                    'type' => $subject[$name]['moo_type'],
                    'name' => $subject[$name]['moo_title'],
                );
                $objects = array(
                    'type' => $subject[$name]['moo_type'],
                    'id' => $subject[$name]['id'],
                    'url' => FULL_BASE_URL . str_replace('?','',mb_convert_encoding($subject[$name]['moo_href'], 'UTF-8', 'UTF-8')), // url
                    'images' => $imageArray,
                );
                
            }
            else {
                $target = array(
                    'id' => $subject["Album"]['id'],
                    'url' => FULL_BASE_URL . $e->subject()->request->base . "/albums/view/" . $subject['Album']['id'] . "/" . seoUrl($subject['Album']['title']),
                    'type' => "Album",
                    'name' => $subject['Album']['title'],
                );
                $objects = array(
                    'type' => 'Photo_Album',
                    'id' => $subject['Album']['id'],
                    'url' => FULL_BASE_URL . $e->subject()->request->base . "/albums/view/" . $subject['Album']['id'] . "/" . seoUrl($subject['Album']['title']),
                    'images' => $imageArray,
                );
            }
        }
        

        $e->result['result'] = array(
            'type' => 'add',
            'title' => $title,
            'titleHtml' => $titleHtml,
            'objects' => $objects,
            'target' => $target,
        );//die;
    }

    function exportPhotoItemDetailShare($e) {
        $v = $e->subject();
        $data = $e->data['data'];
        $actorHtml = $e->data['actorHtml'];
        $subject = MooCore::getInstance()->getItemByType('Photo_Photo', $data['Activity']['parent_id']);
        $tpUser = $subject['User'];
        $title = $data['User']['name'] . ' ' . __("shared %s's album", $tpUser['name']);
        $titleHtml = $actorHtml . ' ' . __('shared %1$s\'s <a href="%2$s">photo</a>', $v->Moo->getName($subject['User'], false), $subject['Photo']['moo_href']);

        $target = array(
            'url' => FULL_BASE_URL . $tpUser['moo_href'],
            'id' => $tpUser['id'],
            'name' => $tpUser['name'],
            'type' => 'User',
        );


        list($title_tmp, $target_tmp) = $v->getActivityTarget($data, $actorHtml);
        if (!empty($title_tmp)) {
            $title = $title_tmp['title'];
            $titleHtml = $title_tmp['titleHtml'];
            $target = $target_tmp;
        }
        $photoSizes = explode('|', Configure::read('core.photo_image_sizes'));
        $imageArray = array();
        $photoModel = MooCore::getInstance()->getModel('Photo_Photo');
        $photo = $photoModel->findById($data['Activity']['parent_id']);
        foreach ($photoSizes as $size) {
            $imageArray[$size] = $v->Photo->getImage($photo, array('prefix' => $size));
        }
        $imageArray['idPhoto'] = $photo['Photo']['id'];
        $imageArray['idAlbum'] = $photo['Album']['id'];
        $imageArray['type'] = $photo['Photo']['type'];
        $imageArray['albumType'] = $photo['Photo']['album_type'];
        $imageArray['albumTypeId'] = $photo['Photo']['album_type_id'];
        $e->result['result'] = array(
            'type' => 'share',
            'title' => $title,
            'titleHtml' => $titleHtml,
            'objects' => array(
                'type' => 'Album',
                'id' => $data['Activity']['parent_id'],
                'url' => FULL_BASE_URL . $v->request->base . "/albums/view/" . $subject['Album']['id'] . "/" . seoUrl($subject['Album']['title']),
                'title' => $subject['Album']['title'],
                'description' => $subject['Album']['description'],
                'images' => $imageArray,
            ),
            'target' => $target,
        );
    }

    function exportAlbumItemDetailShare($e) {
        $v = $e->subject();
        $imageArray = $photos = array();

        $data = $e->data['data'];
        $actorHtml = $e->data['actorHtml'];
        $subject = MooCore::getInstance()->getItemByType('Photo_Album', $data['Activity']['parent_id']);
        $tpUser = $subject['User'];

        $title = $data['User']['name'] . ' ' . __("shared %s's album", $tpUser['name']);
        $titleHtml = $actorHtml . ' ' . __('shared %1$s\'s <a href="%2$s">album</a>', $v->Moo->getName($subject['User'], false), $subject['Album']['moo_href']);

        $target = array(
            'url' => FULL_BASE_URL . $tpUser['moo_href'],
            'id' => $tpUser['id'],
            'name' => $tpUser['name'],
            'type' => 'User',
        );


        list($title_tmp, $target_tmp) = $v->getActivityTarget($data, $actorHtml);
        if (!empty($title_tmp)) {
            $title = $title_tmp['title'];
            $titleHtml = $title_tmp['titleHtml'];
            $target = $target_tmp;
        }
        $photoModel = MooCore::getInstance()->getModel('Photo_Photo');
        $photos = $photoModel->find('all', array('conditions' => array('Photo.type' => 'Photo_Album', 'Photo.target_id' => $data['Activity']['parent_id']),
            //'limit' => 4
        ));
        $imageArray = array();
        $photoSizes = explode('|', Configure::read('core.photo_image_sizes'));
        $i = 0;
        foreach ($photos as $photo) {
            $imageArray[$i]['idPhoto'] = $photo['Photo']['id'];
            $imageArray[$i]['idAlbum'] = $photo['Album']['id'];
            $imageArray[$i]['type'] = $photo['Photo']['type'];
            $imageArray[$i]['albumType'] = $photo['Photo']['album_type'] == '' ? 'Photo_Album' : $photo['Photo']['album_type'];
            $imageArray[$i]['albumTypeId'] = $photo['Photo']['album_type'] == '' ? $photo['Photo']['target_id'] : $photo['Photo']['album_type_id'];

            foreach ($photoSizes as $size) {
                $imageArray[$i][$size] = $v->Photo->getImage($photo, array('prefix' => $size));
            }
            $i++;
        }
        

        $e->result['result'] = array(
            'type' => 'share',
            'title' => $title,
            'titleHtml' => $titleHtml,
            'objects' => array(
                'type' => 'Photo_Album',
                'id' => $data['Activity']['parent_id'],
                'url' => FULL_BASE_URL . $v->request->base . "/albums/view/" . $subject['Album']['id'] . "/" . seoUrl($subject['Album']['title']),
                'title' => $subject['Album']['title'],
                'description' => $subject['Album']['description'],
                'imageCount' => count($photos),
                'images' => $imageArray,
            ),
            'target' => $target,
        );
    }

    function exportPhotoAddShare($e) {
        $v = $e->subject();

        $photoModel = MooCore::getInstance()->getModel('Photo_Photo');
        $imageArray = $photos = array();

        $data = $e->data['data'];
        $actorHtml = $e->data['actorHtml'];
        $activityModel = MooCore::getInstance()->getModel('Activity');
        $parentFeed = $activityModel->findById($data['Activity']['parent_id']);

        $title = __("shared %s's post", $parentFeed['User']['name']);
        $titleHtml = $actorHtml . ' ' . __('shared %1$s\'s <a href="%2$s">post</a>', $v->Html->link($parentFeed['User']['name'], FULL_BASE_URL . $parentFeed['User']['moo_href']), FULL_BASE_URL . $v->Html->url(array(
                            'plugin' => false,
                            'controller' => 'users',
                            'action' => 'view',
                            $parentFeed['User']['id'],
                            'activity_id' => $data['Activity']['parent_id'])));
        $target = array(
            'url' => FULL_BASE_URL . $parentFeed['User']['moo_href'],
            'id' => $parentFeed['User']['id'],
            'type' => 'User',
        );
        $feed = $v->exportActivityShare($data, $actorHtml);

        list($title_tmp, $target_tmp) = $v->getActivityTarget($data, $actorHtml);
        if (!empty($title_tmp)) {
            $title = $title_tmp['title'];
            $titleHtml = $title_tmp['titleHtml'];
            $target = $target_tmp;
        }
        $ids = explode(',', $data['Activity']['items']);
        $imageArray = $v->getImages($ids);






        $e->result['result'] = array(
            'type' => 'share',
            'title' => $title,
            'titleHtml' => $titleHtml,
            'objects' => array(
                'type' => 'Album',
                'id' => $data['Activity']['parent_id'],
                'url' => FULL_BASE_URL . $v->Html->url(array(
                    'plugin' => false,
                    'controller' => 'users',
                    'action' => 'view',
                    $parentFeed['User']['id'],
                    'activity_id' => $data['Activity']['parent_id']
                )),
                'imageCount' => count($photos),
                'images' => $imageArray,
            ),
            'target' => $target,
        );
    }

    function exportPhotosTag($e) {
        $data = $e->data['data'];
        $photoModel = MooCore::getInstance()->getModel('Photo_Photo');
        $actorHtml = $e->data['actorHtml'];
        $title = $data['User']['name'] . ' ' . __('was tagged in a photo');
        $titleHtml = $actorHtml . ' ' . __('was tagged in a photo');
        $photoSizes = explode('|', Configure::read('core.photo_image_sizes'));
        
        $imageArray = $e->subject()->getImages(explode(',', $data['Activity']['items']));

        $e->result['result'] = array(
            'type' => 'tag',
            'title' => $title,
            'titleHtml' => $titleHtml,
            'objects' => array(
                'type' => 'Photo_Photo',
                'images' => $imageArray,
            ),
            'target' => '',
        );
    }

    function exportCommentAddPhoto($e) {
        $data = $e->data['data'];
        $photoModel = MooCore::getInstance()->getModel('Photo_Photo');
        $actorHtml = $e->data['actorHtml'];
        $object = MooCore::getInstance()->getItemByType($data['Activity']['item_type'], $data['Activity']['item_id']);
        $title = $data['User']['name'] . ' ' . __('commented on %s photo', possession($data['User'], $object['User']));
        $titleHtml = $actorHtml . ' ' . __('commented on %s photo', $e->subject()->Moo->getName($object['User'])); 
        $photoSizes = explode('|', Configure::read('core.photo_image_sizes'));
        $photo = $photoModel->findById($data['Activity']['item_id']);
        
        $imageArray = array();
        
        foreach ($photoSizes as $size) {
            $imageArray[$size] = $e->subject()->Photo->getImage($photo, array('prefix' => $size));
        }
        $imageArray['idPhoto'] = $photo['Photo']['id'];
        $imageArray['idAlbum'] = $photo['Album']['id'];
        $imageArray['type'] = $photo['Photo']['type'];
        $imageArray['albumType'] = $photo['Photo']['album_type'];
        $imageArray['albumTypeId'] = $photo['Photo']['album_type_id'];
        $e->result['result'] = array(
            'type' => 'post',
            'title' => $title,
            'titleHtml' => $titleHtml,
            'objects' => array(
                'type' => 'Photo_Photo',
                'images' => $imageArray,
            ),
            'target' => '',
        );
    }

}
