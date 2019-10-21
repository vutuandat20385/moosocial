<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class HistoriesController extends AppController {

    public function ajax_show($type, $target_id) {
        $this->loadModel('CommentHistory');
        $page = (!empty($this->request->named['page'])) ? $this->request->named['page'] : 1;

        $histories = $this->CommentHistory->getHistory($type, $target_id, $page);
        $this->set('page', $page);
        $this->set('histories', $histories);
        $this->set('historiesCount', $this->CommentHistory->getHistoryCount($type, $target_id));
        $this->set('more_url', '/histories/ajax_show/' . $type . '/' . $target_id . '/page:' . ( $page + 1 ));
    }

}

?>