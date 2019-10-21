<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('CakeEventListener', 'Event');

class BlogApiListener implements CakeEventListener
{

    public function implementedEvents()
    {
        return array(
            'ApiHelper.renderAFeed.blog_create' => 'exportBlogCreate',
            'ApiHelper.renderAFeed.blog_item_detail_share' => 'exportBlogItemDetailShare',
        );
    }

    function getBlog($id, $v)
    {
        $blog = MooCore::getInstance()->getItemByType('Blog_Blog', $id);
        $photoSizes = explode('|', Configure::read('core.photo_image_sizes'));
        $imageArray = array();
        foreach ($photoSizes as $size) {
            $imageArray[$size] = $v->Blog->getImage($blog, array('prefix' => $size));
        }
        return array($blog['Blog']['id'], //id
            //FULL_BASE_URL . $v->request->base . "/blogs/view/" . $blog['Blog']['id'] . "/" . seoUrl($blog['Blog']['title']), // url
            FULL_BASE_URL . str_replace('?','',mb_convert_encoding($blog['Blog']['moo_href'], 'UTF-8', 'UTF-8')), // url
            $v->Text->convert_clickable_links_for_hashtags($v->Text->truncate(strip_tags(str_replace(array('<br>', '&nbsp;'), array(' ', ''), $blog['Blog']['body'])), 200, array('eclipse' => '')), Configure::read('Blog.blog_hashtag_enabled')), // desc
            $blog['Blog']['title'],
            $imageArray,
            $blog['User']
        );
    }

    function exportBlogCreate($e)
    { 
        $data = $e->data['data'];
        $actorHtml = $e->data['actorHtml'];

        list($bId, $bUrl, $bDesc, $bTitle, $bImages) = $this->getBlog($data['Activity']['item_id'], $e->subject());

        $e->result['result'] = array(
            'type' => 'create',
            'title' => $data['User']['name'] . ' ' . __('created a new blog entry'),
            'titleHtml' => $actorHtml . ' ' . __('created a new blog entry'),
            'objects' => array(
                'type' => 'Blog_Blog',
                'id' => $bId,
                'url' => $bUrl,
                'title' => $bTitle,
                'images' => $bImages,
                'description'=>$bDesc,
            ),
            'target' => '',
        );
    }

    function exportBlogItemDetailShare($e)
    {
        $data = $e->data['data'];
        $actorHtml = $e->data['actorHtml'];

        list($bId, $bUrl, $bDesc, $bTitle, $bImages, $bUser) = $this->getBlog($data['Activity']['parent_id'], $e->subject());
        $target = array(
            'url' => FULL_BASE_URL . $bUser['moo_href'],
            'id' => $bUser['id'],
            'name' => $bUser['name'],
            'type' => 'User',
        );
        $title = $bUser['name'] . ' ' . __("shared %s's blog", $bUser['name']);
        $titleHtml = $actorHtml . ' ' . __("shared %s's blog", $e->subject()->Html->link($bUser['name'], FULL_BASE_URL . $bUser['moo_href']));

        list($title_tmp,$target) = $e->subject()->getActivityTarget($data,$actorHtml);
        if(!empty($title_tmp)){
            $title =  $title_tmp['title'];
            $titleHtml = $title_tmp['titleHtml'];
        }
        
        $e->result['result']= array(
            'type' => 'share',
            'title' => $title,
            'titleHtml' => $titleHtml,
            'objects' => array(
                'type' => 'Blog_Blog',
                'id' => $bId,
                'url' => $bUrl,
                'title' => $bTitle,
                'images' => $bImages,
                'description'=>$bDesc,
            ),
            'target' => $target,
        );
    }

}
