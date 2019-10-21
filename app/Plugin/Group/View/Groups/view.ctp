<?php
$groupHelper = MooCore::getInstance()->getHelper('Group_Group');
$topic_id = !empty( $this->request->named['topic_id'] ) ? $this->request->named['topic_id'] : 0;
$video_id = !empty( $this->request->named['video_id'] ) ? $this->request->named['video_id'] : 0;
$comment_id = !empty( $this->request->named['comment_id'] ) ? $this->request->named['comment_id'] : 0;
$reply_id = !empty( $this->request->named['reply_id'] ) ? $this->request->named['reply_id'] : 0;
$tab = !empty( $tab ) ? $tab : '';
$is_edit = !empty( $this->request->named['edit'] ) ? $this->request->named['edit'] : 0;
?>

<?php if($this->request->is('ajax')): ?>
<script>
    require(["jquery","mooGroup", "hideshare"], function($,mooGroup) {
        mooGroup.initOnView();
        $(".sharethis").hideshare({media: '<?php echo $groupHelper->getImage($group,array('prefix' => '300_square'))?>', linkedin: false});
    });
</script>
<?php else: ?>
    <?php $this->Html->scriptStart(array('inline' => false,'requires'=>array('jquery','mooGroup', 'hideshare'),'object'=>array('$','mooGroup'))); ?>
    mooGroup.initOnView();
    $(".sharethis").hideshare({media: '<?php echo $groupHelper->getImage($group,array('prefix' => '300_square'))?>', linkedin: false});
    <?php $this->Html->scriptEnd(); ?>
<?php endif; ?>

<?php $this->setNotEmpty('west');?>
<?php $this->start('west'); ?>
    <?php
        $display = true;
        if ($group['Group']['type'] == PRIVACY_PRIVATE) {
            if (empty($is_member)) {
                $display = false;
                if(!empty($cuser) && $cuser['Role']['is_admin'])
                    $display = true;
            }
        }
    ?>
    
    <?php if($display): ?>
    <div class="left-right-menu">
        <img src="<?php echo $groupHelper->getImage($group, array('prefix' => '300_square'))?>" class="page-avatar" id="av-img">
            <h1 class="info-home-name"><?php echo $group['Group']['name']?></h1>
            <div class="menu block-body menu_top_list">
            <ul class="list2" id="browse" style="margin-bottom: 10px">
                <li class="current">
                            <a class="no-ajax" href="<?php echo $this->request->base?>/groups/view/<?php echo $group['Group']['id']?>"><i class="material-icons">library_books</i> <?php echo __( 'Details')?></a>
                    </li>		
                    <li><a data-url="<?php echo $this->request->base?>/groups/members/<?php echo $group['Group']['id']?>" rel="profile-content" id="teams" href="<?php echo $this->request->base?>/groups/view/<?php echo $group['Group']['id']?>/tab:teams"><i class="material-icons">people</i>
                            <?php echo __( 'Members')?> <span id="group_user_count" class="badge_counter"><?php echo $group['Group']['group_user_count']?></span></a>
                    </li>
                    <li><a data-url="<?php echo $this->request->base?>/photos/ajax_browse/group_group/<?php echo $group['Group']['id']?>" rel="profile-content" id="photos" href="<?php echo $this->request->base?>/groups/view/<?php echo $group['Group']['id']?>/tab:photos"><i class="material-icons">collections</i>
                        <?php echo __('Photos')?> <span id="group_photo_count" class="badge_counter"><?php echo $group['Group']['photo_count'];?></span></a>
                    </li>
                <?php foreach ($group_menu as $item): ?>
                <li><a data-url="<?php echo $item['dataUrl']?>" rel="profile-content" id="<?php echo $item['id']?>" href="<?php echo $item['href']?>"><i class="material-icons"><?php echo $item['icon-class']?></i>
                    <?php echo $item['name']?> <span id="<?php echo $item['id_count']?>" class="badge_counter"><?php echo $item['item_count']?></span></a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>
    
<?php $this->end(); ?>

	<div id="profile-content" class="group-detail">
            <div class="groupId" data-id="<?php echo $group['Group']['id']; ?>"></div>
            <div class="topicId" data-id="<?php echo $topic_id; ?>"></div>
            <div class="videoId" data-id="<?php echo $video_id; ?>"></div>
            <div class="commentId" data-id="<?php echo $comment_id; ?>"></div>
            <div class="replyId" data-id="<?php echo $reply_id; ?>"></div>
            <div class="tab" data-id="<?php echo $tab; ?>"></div>
            <div class="isEdit" data-id="<?php echo $is_edit; ?>"></div>
        <?php if ( empty( $tab ) ): ?>
		<?php 
		if ( !empty( $this->request->named['topic_id'] ) || !empty( $this->request->named['video_id'] ) )
			echo __( 'Loading...');
		else
			echo $this->element('ajax/group_detail');
		?>
	    <?php else: ?>
            <?php echo __( 'Loading...')?>
        <?php endif; ?>
    </div>
