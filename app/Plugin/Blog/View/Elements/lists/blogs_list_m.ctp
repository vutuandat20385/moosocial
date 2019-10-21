<div class="content_center_home">
    <div class="mo_breadcrumb">
        <h1><?php echo __('My Blog') ?></h1>
        <a href="<?php echo $this->request->base ?>/blogs/create" class="topButton button button-action button-mobi-top"><?php echo __('Write New Entry') ?></a>
    </div>
    <ul class="list6 comment_wrapper list-mobile" id="list-content">
        <?php echo $this->element('lists/blogs_list', array('user_blog' => true)); ?>
    </ul> 
</div>