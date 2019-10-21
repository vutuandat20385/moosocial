<div class="content_center_home">
    <div class="mo_breadcrumb">
        <h1><?php echo __('My Topics') ?></h1>
        <a href="<?php echo $this->request->base ?>/topics/create" class="topButton button button-action button-mobi-top"><?php echo __('Create New Topic') ?></a>

    </div>
    <ul class="list6 comment_wrapper" id="list-content">
        <?php echo $this->element('lists/topics_list'); ?>
    </ul>
</div>