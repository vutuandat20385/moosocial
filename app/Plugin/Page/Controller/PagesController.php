<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('PageAppController', 'Page.Controller');

class PagesController extends PageAppController {

    public $paginate = array('limit' => RESULTS_LIMIT);

    public function display() {
        $this->loadModel('Page.Page');
        $path = func_get_args();

        $count = count($path);
        if (!$count) {
            $this->redirect('/');
        }
        if (file_exists(APP . 'Plugin/Page/View' . DS . 'Pages' . DS . $path[0] . '.ctp')) {
            $page = $subpage = $title_for_layout = null;
            if (!empty($path[0])) {
                $page = $path[0];
            }
            if (!empty($path[1])) {
                $subpage = $path[1];
            }
            if (!empty($path[$count - 1])) {
                $title_for_layout = Inflector::humanize($path[$count - 1]);
            }
            $this->set(compact('page', 'subpage', 'title_for_layout'));
            if($path[0] == 'no-permission') {
                $this->set('title_for_layout', __('No Permission'));
            }
            //$this->render(implode('/', $path));
            $this->render($path[0]);
        } else {
            $language = Configure::read('Config.language');
            $alias = $path[$count - 1];
            $page = Cache::read('page.page_display_' . $alias . '_' . $language, 'page');
            if (empty($page)) {
                $page = $this->Page->findByAlias($alias);
                Cache::write('page.page_display_' . $alias . '_' . $language, $page, 'page');
            }
            $this->_checkExistence($page);

            // check permission
            if ($page['Page']['permission'] !== '') {
                $permissions = explode(',', $page['Page']['permission']);
                $cuser = $this->_getUser();
                $role_id = $this->_getUserRoleId();
                if (!in_array($role_id, $permissions) && $role_id != 1) {
                    $this->redirect('/pages/no-permission');
                    exit;
                }
            }

            $params = unserialize($page['Page']['params']);
            $page_num = (!empty($this->request->named['page'])) ? $this->request->named['page'] : 1;
            if (!empty($params['comments'])) {
                $data = array();
                $cond= array();

                if (!empty( $this->request->named['comment_id'] )) {
                    $cond['Comment.id'] = $this->request->named['comment_id'];
                    $data['cmt_id'] = $this->request->named['comment_id'];
                }

                $this->loadModel('Comment');
                $comments = $this->Comment->getComments($page['Page']['id'], APP_PAGE, $page_num, $cond);

                if(!empty( $this->request->named['reply_id']) && !empty($comments[0])){
                    $this->loadModel('Like');
                    $reply = $this->Comment->find('all', array(
                        'conditions' => array(
                            'Comment.id' => $this->request->named['reply_id'],
                        )
                    ));
                    $replies_count = $this->Comment->getCommentsCount( $comments[0]['Comment']['id'], 'comment' );
                    $comment_likes = $this->Like->getCommentLikes( $reply, $this->Auth->user('id') );

                    $comments[0]['Replies'] = $reply;
                    $comments[0]['RepliesIsLoadMore'] = ($replies_count - 1) > 0 ? true : false;
                    $comments[0]['RepliesCommentLikes'] = $comment_likes;
                }
                //$this->set('more_comments', '/comments/ajax_browse/page/' . $page['Page']['id'] . '/page:'. ($page_num + 1));
                // $this->set('comments', $comments);
                $comment_count = $this->Comment->getCommentsCount($page['Page']['id'], APP_PAGE);
                $data['bIsCommentloadMore'] = $comment_count - $page_num * RESULTS_LIMIT;
                $data['more_comments'] = '/comments/browse/page/' . $page['Page']['id'] . '/page:' . ($page_num + 1);
                $data['comments'] = $comments;

                $this->set('data', $data);
            }

            $this->set('page', $page);
            $this->set('params', $params);
            $this->set('title_for_layout', $page['Page']['title']);
            $this->render('view');
        }
    }

    public function currentUri($aUri = null) {
        //var_dump($this->params['pass']);
        $uri = empty($this->params['controller']) ? "" : $this->params['controller'];
        $uri .= empty($this->params['action']) ? "" : "." . $this->params['action'];

        if ($uri == 'pages.display') {
            //$uri.= empty($this->params['pass'][0])?"":".".$this->params['pass'][0];
            $uri = 'pages';
            $uri .= empty($this->params['pass'][0]) ? "display" : "." . $this->params['pass'][0];
        }
        //var_dump($uri);die();
        return $uri;
    }

    public function error() {
        $error_msg = '';
        if ($this->request->is('ajax')) {
            $error_msg = __('Item does not exist');
        }
        $this->set('title_for_layout', __('Error'));
        $this->set(compact('error_msg'));
    }

}
