<?php if($this->request->is('ajax')): ?>
<script type="text/javascript">
    require(["jquery","mooGroup"], function($, mooGroup) {
        mooGroup.initOnTopicList();
    });
</script>
<?php else: ?>
<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires'=>array('jquery', 'mooGroup'), 'object' => array('$', 'mooGroup'))); ?>
mooGroup.initOnTopicList();
<?php $this->Html->scriptEnd(); ?>
<?php endif; ?>

<div class="bar-content">
    <div class="content_center">
         <div class="mo_breadcrumb">
            <h1 class="visible-xs visible-sm"><?php echo $groupname?></h1>
            <?php if ( !empty( $is_member ) ): ?> 
            <div class="groupId" data-id="<?php echo $group_id; ?>"></div>
            <a href="javascript:void(0)" class="createGroupTopic topButton button button-action button-mobi-top"><?php echo __( 'Create New Topic')?></a>
            <div class="clear"></div>
            <?php endif; ?>
        </div>
        <ul class="topic-content-list" id="list-content">
                <?php echo $this->element( 'group/topics_list' ); ?>
        </ul>
    </div>
</div>