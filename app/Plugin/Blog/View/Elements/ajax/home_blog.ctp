<div class="content_center_home">
    <div class="mo_breadcrumb">
        <h1><?php echo __('My Blogs')?></h1>
        <a href="<?php echo $this->Html->url(array(
            'plugin' => 'blog',
            'controller' => 'blogs',
            'action' => 'create'
        )); ?>" class="topButton button button-action button-mobi-top"><?php echo __('Write New Entry')?></a>
    </div>
    <ul class="list6 comment_wrapper list-mobile" id="list-content">
            <?php echo $this->element( 'lists/blogs_list', array('user_blog' => true) ); ?>
    </ul> 
</div>