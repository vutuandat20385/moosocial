<?php
$likeModel =  MooCore::getInstance()->getModel('Like');
$item['like_count'] = $likeModel->getBlockLikeCount($item['id'],$item['moo_type']);
$item['dislike_count'] = $likeModel->getBlockLikeCount($item['id'],$item['moo_type'],0);
if($this->request->is('ajax')): ?>
<script type="text/javascript">
    require(["jquery","mooLike"], function($,mooLike) {
        mooLike.initLikeItem();
    });
</script>
<?php else: ?>
<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true,'requires'=>array('jquery', 'mooLike'), 'object' => array('$', 'mooLike'))); ?>
mooLike.initLikeItem();
<?php $this->Html->scriptEnd();  ?>
<?php endif; ?>

<?php
if ( empty( $hide_container ) ):
?>
<div class="like-section">
<div class="like-action">
  
<?php $this->getEventManager()->dispatch(new CakeEvent('element.items.renderLikeButton', $this,array('uid' => $uid,'item' => array('id' => $item['id'], 'like_count' => $item['like_count']), 'item_type' => $type ))); ?>
<?php if(empty($hide_like)): ?>
   <a href="javascript:void(0)" data-type="<?php echo $type?>" data-id="<?php echo $item['id']?>" data-status="1" class="likeItem <?php if ( !empty($uid) && !empty( $like['Like']['thumb_up'] ) ): ?>active<?php endif; ?>">
    <i class="material-icons">thumb_up</i>

    </a>
    <?php
      $this->MooPopup->tag(array(
             'href'=>$this->Html->url(array("controller" => "likes",
                                            "action" => "ajax_show",
                                            "plugin" => false,
                                            $type,
                                            $item['id']
                                        )),
             'title' => __('People Who Like This'),
             'innerHtml'=> '<span id="like_count"> ' . $item['like_count'] . '</span>',
          'data-dismiss'=> 'modal'
     ));
 ?>
<?php endif; ?>

    <?php if(empty($hide_dislike)): ?>
    <a href="javascript:void(0)" data-type="<?php echo $type?>" data-id="<?php echo $item['id']?>" data-status="0" class="likeItem <?php if ( !empty($uid) && isset( $like['Like']['thumb_up'] ) && $like['Like']['thumb_up'] == 0 ): ?>active<?php endif; ?>">
        <i class="material-icons">thumb_down</i>

    </a>
    <?php
      $this->MooPopup->tag(array(
             'href'=>$this->Html->url(array("controller" => "likes",
                                            "action" => "ajax_show",
                                            "plugin" => false,
                                            $type,
                                            $item['id'],1
                                        )),
             'title' => __('People Who DisLike This'),
             'innerHtml'=> '<span id="dislike_count">' . $item['dislike_count'] . '</span>',
          'data-dismiss' => 'modal'
     ));
 ?>
    <?php endif; ?>

    <?php if (isset($shareUrl) && !isset($doNotShare)): ?>
    <a href="javascript:void(0);" share-url="<?php echo $shareUrl ?>" class="shareFeedBtn"><i class="material-icons">share</i> <?php echo __('Share') ?></a>
    <?php endif; ?>

    <!-- New hook -->
    <?php $this->getEventManager()->dispatch(new CakeEvent('element.likes.afterRenderShareButton', $this,array('item'=> $item, 'type' => $type))); ?>
    <!-- New hook -->
    
	</div>
        <div class="likes" >
<?php $this->getEventManager()->dispatch(new CakeEvent('element.items.renderLikeReview', $this,array('uid' => $uid,'item' => array('id' => $item['id'], 'like_count' => $item['like_count']), 'item_type' => $type ))); ?>
<?php if(empty($hide_like)): ?>
        <?php
            $total_users = count($likes);
            $new_likes = array();
            if($total_users > 6):
                for($i = 0; $i <6; $i++):
                    $new_likes[] = $likes[$i];
                endfor;
            else:
                $new_likes = $likes;
            endif;
        ?>
            <span id="like_count2"><?php echo $total_users ?></span> <?php echo __('people liked this') ?>
	    <?php echo $this->element( 'blocks/users_block', array( 'users' => $new_likes ) ); ?>
<?php endif; ?>
<?php
endif;
?>
	    
<?php
if ( empty( $hide_container ) ):
?>
	   </div>
  
</div>


<?php
endif;
?>