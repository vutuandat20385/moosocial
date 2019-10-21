<?php if($this->request->is('ajax')): ?>
<script type="text/javascript">
    require(["jquery","mooGroup"], function($,mooGroup) {
        mooGroup.initOnAjaxGroupVideo();
    });
</script>
<?php else: ?>
<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires'=>array('jquery','mooGroup'), 'object' => array('$', 'mooGroup'))); ?>
mooGroup.initOnAjaxGroupVideo();
<?php $this->Html->scriptEnd(); ?>
<?php endif; ?>

<div class="bar-content">
    <div class="content_center">
        <div class="mo_breadcrumb">
            <h1 class="visible-xs visible-sm"><?php echo $groupname; ?></h1>
            <?php if (!empty($is_member)): ?>
                <a id="share-new" data-target="#videoModal" data-toggle="modal" data-id="<?php echo $group_id?>" data-url="<?php echo $this->request->base ?>/videos/group_create" class="topButton button button-action button-mobi-top"><?php echo __('Share New Video') ?></a>

                <!-- Hook for video upload -->
                <?php $this->getEventManager()->dispatch(new CakeEvent('Video.View.Elements.groupUploadVideo', $this, array('group_id' => $group_id))); ?>
                <!-- Hook for video upload -->
            <?php endif; ?>
        </div>
        <div class="clear"></div>
        <ul class="video-content-list <?php if (!empty($is_member)): ?>p_top_15<?php endif; ?>" id="list-content">
            <?php echo $this->element('lists/videos_list'); ?>
        </ul>
    </div>
</div>
<section aria-hidden="true" aria-labelledby="myModalLabel" role="basic" id="videoModal" class="modal fade in" >
    <div class="modal-dialog">
        <div class="modal-content">
        </div>
    </div>
</section>