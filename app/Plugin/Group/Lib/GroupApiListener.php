<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('CakeEventListener', 'Event');

class GroupApiListener implements CakeEventListener {

    public function implementedEvents() {
        return array(
            'ApiHelper.renderAFeed.group_create' => 'exportGroupCreate',
            'ApiHelper.renderAFeed.group_join' => 'exportGroupJoin',
            'ApiHelper.renderAFeed.group_item_detail_share' => 'exportGroupItemDetailShare',
        );
    }

    function getGroup($id, $v) {
        $group = MooCore::getInstance()->getItemByType('Group_Group', $id);
        $photoSizes = explode('|', Configure::read('core.photo_image_sizes'));
        $imageArray = array();
        foreach ($photoSizes as $size) {
            $imageArray[$size] = $v->Group->getImage($group, array('prefix' => $size));
        }
        return array($group['Group']['id'], //id
            //FULL_BASE_URL . $v->request->base . "/groups/view/" . $group['Group']['id'] . "/" . seoUrl($group['Group']['name']), // url
            FULL_BASE_URL . str_replace('?','',mb_convert_encoding($group['Group']['moo_href'], 'UTF-8', 'UTF-8')), // url
            $v->Text->convert_clickable_links_for_hashtags($v->Text->truncate(strip_tags(str_replace(array('<br>', '&nbsp;'), array(' ', ''), $group['Group']['description'])), 200, array('eclipse' => '')), Configure::read('Group.group_hashtag_enabled')),
            $group['Group']['name'],
            $imageArray,
            $group['User']
        );
    }

    function exportGroupCreate($e) {

        $data = $e->data['data'];
        $actorHtml = $e->data['actorHtml'];

        list($bId, $bUrl, $bDesc, $bTitle, $bImages) = $this->getGroup($data['Activity']['item_id'], $e->subject());
        $e->result['result'] = array(
            'type' => 'create',
            'title' => $data['User']['name'] . ' ' . __('created a new group'),
            'titleHtml' => $actorHtml . ' ' . __('created a new group'),
            'objects' => array(
                'type' => 'Group_Group',
                'id' => $bId,
                'url' => $bUrl,
                'title' => $bTitle,
                'images' => $bImages,
                'description' => $bDesc,
            ),
            'target' => '',
        );
    }

    function exportGroupJoin($e) {

        $data = $e->data['data'];
        $actorHtml = $e->data['actorHtml'];
        $v = $e->subject();

        $ids = explode(',', $data['Activity']['items']);
        $groupModel = MooCore::getInstance()->getModel('Group_Group');
        $groupModel->cacheQueries = true;
        $groups = $groupModel->find('all', array('conditions' => array('Group.id' => $ids),
        ));

        $joined1 = '%s';
        $joined2 = '%s and %s';
        $joined3 = '%s and %s';
        $joined = $joinedHtml = '';
        switch (count($groups)) {
            case 1:
                $joined = sprintf($joined1, $groups[0]['Group']['name']);
                $joinedHtml = sprintf($joined1, $v->Html->link($groups[0]['Group']['name'], FULL_BASE_URL . $groups[0]['Group']['moo_href']));
                break;
            case 2:
                $joined = sprintf($joined2, $groups[0]['Group']['name'], $groups[1]['Group']['name']);
                $joinedHtml = sprintf($joined2, $v->Html->link($groups[0]['Group']['name'], FULL_BASE_URL . $groups[0]['Group']['moo_href']), $v->Html->link($groups[1]['Group']['name'], FULL_BASE_URL . $groups[1]['Group']['moo_href']));
                break;
            case 3:
            default :
                $joined = sprintf($joined3, $groups[0]['Group']['name'], abs(count($groups) - 1) . ' ' . __('others'));
                $joinedHtml = sprintf($joined3, $v->Html->link($groups[0]['Group']['name'], FULL_BASE_URL . $groups[0]['Group']['moo_href']), abs(count($groups) - 1) . ' ' . __('others'));
                break;
        }

        $title = $data['User']['name'] . ' ' . __('joined group') . ' ' . $joined;
        $titleHtml = $actorHtml . ' ' . __('joined group') . ' ' . $joinedHtml;
        $photoSizes = explode('|', Configure::read('core.photo_image_sizes'));

        foreach ($groups as $group):
            $imageArray = array();
            foreach ($photoSizes as $size) {
                $imageArray[$size] = $v->Group->getImage($group, array('prefix' => $size));
            }
            $groupArray[] = array(
                'id' => $group['Group']['id'],
                'url' => FULL_BASE_URL . str_replace('?','',mb_convert_encoding($group['Group']['moo_href'], 'UTF-8', 'UTF-8')),
                'name' => $group['Group']['name'],
                'type' => h($group['Group']['moo_plugin']),
                'userCount' => h($group['Group']['group_user_count']),
                'images' => $imageArray,
            );
        endforeach;



        $e->result['result'] = array(
            'type' => 'join',
            'title' => $title,
            'titleHtml' => $titleHtml,
            'objects' => array(
                'type' => 'Group_Group',
                'groupCount' => count($groupArray),
                'groupArray' => $groupArray,
            ),
            'target' => '',
        );
    }

    function exportGroupItemDetailShare($e) {

        $data = $e->data['data'];
        $actorHtml = $e->data['actorHtml'];

        list($bId, $bUrl, $bDesc, $bTitle, $bImages, $bUser) = $this->getGroup($data['Activity']['parent_id'], $e->subject());
        if (isset($data['Activity']['parent_id']) && $data['Activity']['parent_id']):

            $title = $data['User']['name'] . ' ' . __("shared %s's group", $bUser['name']);
            $titleHtml = $actorHtml . ' ' . __("shared %s's group", $e->subject()->Html->link($bUser['name'], FULL_BASE_URL . $bUser['moo_href']));
            $target = array(
                'url' => FULL_BASE_URL . $bUser['moo_href'],
                'id' => $bUser['id'],
                'name' => $bUser['name'],
                'type' => 'User',
            );
        endif;

        list($title_tmp, $target) = $e->subject()->getActivityTarget($data, $actorHtml, true);
        if (!empty($title_tmp)) {
            $title .= $title_tmp['title'];
            $titleHtml .= $title_tmp['titleHtml'];
        }

        $e->result['result'] = array(
            'type' => 'share',
            'title' => $title,
            'titleHtml' => $titleHtml,
            'objects' => array(
                'type' => 'Group_Group',
                'id' => $bId,
                'url' => $bUrl,
                'title' => $bTitle,
                'images' => $bImages,
                'description' => $bDesc,
            ),
            'target' => $target,
        );
    }

}
