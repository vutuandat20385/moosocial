<?php 
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class BlogPluginsController extends BlogAppController{
    public function admin_index()
    {
        $this->loadModel('Blog.Blog');

        $cond = array();
        if ( !empty( $this->request->data['keyword'] ) )
            $cond['MATCH(Blog.title) AGAINST(? IN BOOLEAN MODE)'] = $this->request->data['keyword'];
        
        $this->loadModel('Category');
        $categories = $this->Category->getCategoriesListItem('Blog');
        $blogs = $this->paginate( 'Blog', $cond );

        $this->set('blogs', $blogs);
        $this->set('categories', $categories);
        $this->set('title_for_layout', __('Blogs Manager'));

    }
    public function admin_delete()
    {
        $this->loadModel('Blog.Blog');
        $this->_checkPermission(array('super_admin' => 1));

        if ( !empty( $_POST['blogs'] ) )
        {
            $blogs = $this->Blog->findAllById($_POST['blogs']);
            
            foreach ( $blogs as $blog ){
                
                $this->Blog->deleteBlog( $blog );
                
                $cakeEvent = new CakeEvent('Plugin.Controller.Blog.afterDeleteBlog', $this, array('item' => $blog));
                $this->getEventManager()->dispatch($cakeEvent);
            }

            $this->Session->setFlash( __('Blogs have been deleted') , 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
        }

        $this->redirect( array(
            'plugin' => 'blog',
            'controller' => 'blog_plugins',
            'action' => 'admin_index'
        ) );
    }
    public function admin_move() {
        if (!empty($_POST['blogs']) && !empty($this->request->data['category'])) {
            $this->loadModel('Blog.Blog');
            foreach ($_POST['blogs'] as $blog_id) {
                $blog = $this->Blog->findById($blog_id);
                if (!empty($blog)) {
                    if (!empty($blog['Blog']['group_id'])) {
                        $this->Session->setFlash( __( 'Cannot move blogs in groups, please un-check those blogs'), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ) );
                        $this->redirect( $this->referer() );
                    }
                }
                $this->Blog->id = $blog_id;
                $this->Blog->save(array('category_id' => $this->request->data['category']));
            }

            $this->Session->setFlash( __( 'Blog has been moved'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ) );
        }

        $this->redirect($this->referer());
    }
}