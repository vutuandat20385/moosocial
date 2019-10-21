<?php if($this->request->is('ajax')): ?>
<script type="text/javascript">
    require(["jquery","mooPhoto","mooPhotoTheater","mooComment"], function($, mooPhoto, mooPhotoTheater, mooComment) {
        mooPhoto.initOnPhotoView({
            photo_id : <?php echo $photo['Photo']['id']?>,
            photo_thumb : '<?php echo $photo['Photo']['thumbnail']?>',
            tag_uid : <?php echo isset($this->request->named['uid']) ? $this->request->named['uid'] : 0?>,
            type : '<?php echo $type; ?>',
            target_id : <?php echo $target_id?>,
            album_type : '<?php echo $photo['Photo']['album_type']; ?>',
            album_type_id : <?php echo $photo['Photo']['album_type_id']; ?>
        });
        mooPhotoTheater.initShowPhoto();
        mooComment.initReplyCommentItem();
        mooComment.initCloseComment();
    });
    
</script>
<?php else: ?>
<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires'=>array('jquery','mooPhoto', 'mooPhotoTheater', 'mooComment'), 'object' => array('$', 'mooPhoto', 'mooPhotoTheater', 'mooComment'))); ?>
mooPhoto.initOnPhotoView({
    photo_id : <?php echo $photo['Photo']['id']?>,
    photo_thumb : '<?php echo $photo['Photo']['thumbnail']?>',
    tag_uid : <?php echo isset($this->request->named['uid']) ? $this->request->named['uid'] : 0?>,
    type : '<?php echo $type?>',
    target_id : <?php echo $target_id?>,
    album_type : '<?php echo $photo['Photo']['album_type']; ?>',
    album_type_id : <?php echo $photo['Photo']['album_type_id']; ?>
});
mooPhotoTheater.initShowPhoto();
mooComment.initReplyCommentItem();
<?php $this->Html->scriptEnd(); ?>
<?php endif; ?>


<?php if($this->request->is('ajax')) $this->setCurrentStyle(4) ?>
<?php
$photoHelper = MooCore::getInstance()->getHelper('Photo_Photo');
$nextPhoto = '';
if(!empty($neighbors['next']['Photo']['id'])){
    $nextPhoto = $neighbors['next']['Photo']['id'];
}
else if(empty($neighbors['next']['Photo']['id']) && !empty($neighbors['prev']['Photo']['id'])){
    $nextPhoto = $neighbors['prev']['Photo']['id'];
}

if(( $uid && $cuser['Role']['is_admin'] ) || ( !empty( $admins ) && in_array( $uid, $admins ) )){
    $is_owner = 1;
}else{
    $is_owner = 0;
}
?>

<div data-nextphoto="<?php echo $nextPhoto; ?>" data-taguserid="<?php if ( !empty( $this->request->query['uid'])) echo $this->request->query['uid']; else echo 0;?>" data-thumbfull="<?php echo FULL_BASE_URL . $this->request->webroot .'uploads/photos/thumbnail/'. $photo['Photo']['id']. '/' .$photo['Photo']['thumbnail'];?>" data-taguid="<?php if ( !empty( $this->request->named['uid'])) echo $this->request->named['uid']; else echo 0;?>" data-photoid="<?php echo $photo['Photo']['id']?>" data-photocount="<?php echo $photosAlbumCount;?>" data-page="<?php echo $page;?>" id="photo_wrapper" >
    <div class="info">
    	<?php if (!empty($photos)): ?>        
        <?php echo __('Photo') ?> <span><?php echo "<span id='photo_position'>" . $photo_position . "</span>" . __(' of ') . $total_photos; ?></span>
        <?php endif;?>
        <ul class="theater-photo-option">
        	<?php if ($is_show_full_photo):?>
	            <?php if ($uid == $photo['Photo']['user_id'] && $this->Storage->isLocalStorage()): ?>
	                            <li>
	                                <a href="#" id="rotate_left" data-id="<?php echo $photo['Photo']['id']?>" data-mode="left" aria-haspopup="true" role="button" aria-expanded="false" class="rotate_img" title="<?php echo __('Rotate Left');?>">
	                                   <i class="material-icons">rotate_left</i>
	                                </a>
	                                      </li>
	                                      <li>
	                                <a href="#" id="rotate_right" data-id="<?php echo $photo['Photo']['id']?>" data-mode="right" aria-haspopup="true" role="button" aria-expanded="false" class="rotate_img" title="<?php echo __('Rotate Right');?>">
	                                    <i class="material-icons">rotate_right</i>
	                                </a>
	                            </li>
	            <?php endif; ?>
	            <li>
	                <div class="dropdown">
	                    <button id="dLabel" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
	                      <i class="material-icons">more_vert</i>
	                    </button>
	                    <ul class="dropdown-menu">
	                        <?php if ( $can_tag ): ?>
	                            <li id="tagPhoto"><a href="javascript:void(0)"><?php echo __( 'Tag Photo')?></a></li>
	                        <?php endif; ?>
	                        <?php if ($uid == $photo['Photo']['user_id']): ?>
	                        <li><a href="javascript:void(0);" id="set_photo_cover" class="set_cover"><?php echo __( 'Set as cover')?></a><span id="set_cover"></span></li>
	                        <li><a href="javascript:void(0);" id="set_profile_picture" class="set_avatar"><?php echo __( 'Set as profile picture')?></a><span id="set_avatar"></span></li>
	                        <?php endif; ?>
	
	                        <?php if ( $is_owner ): ?>
	
	                            <li><a data-dismiss="modal" id="delete_photo" href="javascript:void(0)"><?php echo __( 'Delete Photo')?></a></li>
	                        <?php endif; ?>
	                            <?php if ( !empty( $photo['Photo']['original'] ) ): ?>
	                            <li><a href="<?php echo $this->request->webroot?><?php echo $photo['Photo']['original']?>" target="_blank"><i class="material-icons">file_download</i> <?php echo __( 'Download Hi-res')?></a></li>
	                            <?php endif; ?>
	                            <li>
	                                <?php
	                                $this->MooPopup->tag(array(
	                                       'href'=>$this->Html->url(array("controller" => "reports",
	                                                                      "action" => "ajax_create",
	                                                                      "plugin" => false,
	                                                                      'photo_photo',
	                                                                      $photo['Photo']['id'],
	                                                                  )),
	                                       'title' => __( 'Report Photo'),
	                                       'innerHtml'=> __( 'Report Photo'),
	                                    'data-dismiss' => 'modal'
	                               ));
	                           ?>
	                            </li>
	                            
	                            <?php if (!empty($photo['Album']['moo_privacy']) && $photo['Album']['moo_privacy'] != PRIVACY_ME): ?>
	                            <li>
	                                <a href="javascript:void(0);" share-url="<?php echo $this->Html->url(array(
	                                      'plugin' => false,
	                                      'controller' => 'share',
	                                      'action' => 'ajax_share',
	                                      'Photo_Photo',
	                                      'id' => $photo['Photo']['id'],
	                                      'type' => 'photo_item_detail'
	                                  ), true); ?>" class="shareFeedBtn"><?php echo __('Share'); ?></a>
	                            </li>
	                            <?php elseif (!empty($photo['Group']['moo_privacy']) && $photo['Group']['moo_privacy'] != PRIVACY_RESTRICTED && $photo['Group']['moo_privacy'] != PRIVACY_PRIVATE): ?>
	                            <li>
	                                <a href="javascript:void(0);" share-url="<?php echo $this->Html->url(array(
	                                      'plugin' => false,
	                                      'controller' => 'share',
	                                      'action' => 'ajax_share',
	                                      'Photo_Photo',
	                                      'id' => $photo['Photo']['id'],
	                                      'type' => 'photo_item_detail'
	                                  ), true); ?>" class="shareFeedBtn"><?php echo __('Share'); ?></a>
	                            </li>
	                            <?php endif; ?>
	                    </ul>
	                </div>   
	            </li>
			<?php else:?>
			<li>
				<a href="" data-dismiss="modal"><i style="color:#fff" class="material-icons icon-2x topButton">clear</i></a>
			</li>
			<?php endif;?>
        </ul>
    </div>
    <?php /*if (!empty($photos)): ?>
    <div id="photo_thumbs">
        <ul id="thumb_list_popup" >
            <?php echo $this->element('theater/photo_thumbs'); ?>
        </ul>    
    </div>
    <?php endif;*/?>
    <div id="tag-wrapper">
        <div class="photo_img">
    	<?php if (!empty($neighbors['next']['Photo']['id'])): ?>
    	<a href="javascript:void(0)" data-id="<?php echo $neighbors['next']['Photo']['id']?>" data-thumb="1" id="photo_left_arrow_lg" class="showPhotoTheater lb_icon">
            <i class="material-icons icon-4x">chevron_left</i>
        </a>
        <?php endif;?>
        <?php if (!empty($neighbors['prev']['Photo']['id'])): ?>
        <a href="javascript:void(0)" data-id="<?php echo $neighbors['prev']['Photo']['id']?>" data-thumb="1" id="photo_right_arro_lgw" class="showPhotoTheater lb_icon">
            <i class="material-icons icon-4x">chevron_right</i>
        </a>
        <?php endif;?>
        <img style="visibility:hidden" data-size="<?php if (!$is_redirect && $photo['Photo']['size']) echo $photo['Photo']['size'];?>" src="<?php if ($is_redirect) echo $this->Storage->getImage('/photo/img/noimage/privacy.png'); else echo $photoHelper->getImage($photo, array('prefix' => '1500')) . '?' . time();?>" id="photo_src">
        <div id="tag-target"></div>
        <div id="tag-input">
            <?php echo __( "Enter person's name")?>
            <input type="text" id="tag-name">
            <?php echo __( 'Or select a friend')?>
            <div id="friends_list" class="tag_friends_list"></div>
            <a href="#" id="tag-submit" class="button button-action"><?php echo __( 'Submit')?></a>
            <a href="#" id="tag-cancel" class="button"><?php echo __( 'Cancel')?></a>
        </div>
        <?php 
        foreach ( $photo_tags as $tag ): 
        ?>
        <div style="<?php echo $tag['PhotoTag']['style']?>" class="hotspot" id="hotspot-0-<?php echo $tag['PhotoTag']['id']?>"><span>
            <?php
            if ( $tag['PhotoTag']['user_id'] ){
                $tag['User']['no_tooltip'] = true;
                echo $this->Moo->getName( $tag['User'], false );
            }else{
                echo h($tag['PhotoTag']['value']);
            }
            ?>
        </span></div>
        <?php
        endforeach;
        ?>   
        </div>
    </div>
    <?php if ($is_show_full_photo):?>
    <div id="lb_description">
        <?php if ( $photo['Photo']['type'] == 'Group_Group' ): ?>
        <a href="<?php echo $this->request->base?>/groups/view/<?php echo $photo['Photo']['target_id']?>/<?php echo seoUrl($photo['Group']['name'])?>"><?php echo __( 'Photos of %s', $photo['Group']['name'])?></a>
        <?php else: ?>
        <a href="<?php echo $this->request->base?>/albums/view/<?php echo $photo['Photo']['target_id']?>/<?php echo seoUrl($photo['Album']['moo_title'])?>"><?php echo h($photo['Album']['moo_title'])?></a>
        <?php endif; ?> 
    </div>
    <?php else:?>
    	<div id="lb_description">	       
    		<a href="#"><?php echo __("You can't view or interact with this image because of view privacy.");?></a>	        
	    </div>
    <?php endif;?>
</div>
<div class="photo_comments" <?php if (!$is_show_full_photo):?>style="display:none;"<?php endif;?>>
	<?php if ($is_show_full_photo):?>
    <a href="" data-dismiss="modal" id="photo_close_icon" class="lb_icon"><i class="material-icons icon-2x topButton">clear</i></a>
    <div class="photo_right">
            <div class="owner-photo">
                <?php echo $this->Moo->getImage(array('User' => $photo['User']), array('prefix' => '50_square')); ?>
                <div class="owner-info">
                    <?php echo $this->Moo->getName($photo['User']); ?>
                    <div><?php echo $this->Moo->getTime( $photo['Photo']['created'], Configure::read('core.date_format'), $utz )?></div>
                    <?php $this->Html->rating($photo['Photo']['id'],'photos','Photo'); ?>
                </div>
            </div>
                <div class="comment_message" style="margin:4px 0">
                    <?php echo $this->Moo->formatText( $photo['Photo']['caption'], false, true, array('no_replace_ssl' => 1) )?>
                </div>
                <div id="tags" style="margin:5px 0;">
                    <?php if(count($photo_tags)) : ?>
                    <span class="photo_view_info"><?php echo __( 'In this photo')?>: </span>
                    <?php 
                    $count = 0;
                    foreach ( $photo_tags as $tag ): 
                    ?>
                    <span class="photoDetailTags" data-tag-id="<?php echo $tag['PhotoTag']['id']?>" id="hotspot-item-0-<?php echo $tag['PhotoTag']['id']?>">
                        <?php
                        if ( $tag['PhotoTag']['user_id'] )
                            echo $this->Moo->getName( $tag['User'], false );
                        else
                            echo h($tag['PhotoTag']['value']);

                        if (( $uid && $cuser['Role']['is_admin'] ) || $uid == $tag['PhotoTag']['tagger_id'] || $uid == $tag['PhotoTag']['user_id'] ):
                        ?><a class="photoDetailRemoveTags" data-id="<?php echo $tag['PhotoTag']['id']?>" href="javascript:void(0)"><i class="material-icons cross-icon-sm">clear</i></a>
                        <?php
                        endif;
                        ?>
                    </span>
                    <?php
                        $count++; 
                    endforeach; 
                    ?>
                    <?php endif; ?>
                </div>
    </div>
    <div class="photo_left">
<?php $this->getEventManager()->dispatch(new CakeEvent('element.photos.renderLikeReview', $this,array('uid' => $uid,'photo' => $photo, 'item_type' => 'Photo_Photo' ))); ?>
<?php $this->getEventManager()->dispatch(new CakeEvent('element.photos.renderLikeButton', $this,array('uid' => $uid,'photo' => $photo, 'item_type' => 'Photo_Photo' ))); ?>
                <ul class="photo-theater-comment">
                    <li class="pull-left"><i class="material-icons">comment</i> <?php echo __('Comment') ?></li>
                     <?php if ( !empty($uid) ): ?>
                         <?php if(empty($hide_dislike)): ?>
                            <li class="pull-right">
                                    <a href="javascript:void(0)" id="photo_dislike_count" data-thumb-up="0" data-id="<?php echo $photo['Photo']['id']?>" class="likePhoto <?php if ( !empty( $uid ) && isset( $like['Like']['thumb_up'] ) && $like['Like']['thumb_up'] == 0 ): ?>active<?php endif; ?>"><i class="material-icons">thumb_down</i></a>
                                    <?php
                  $this->MooPopup->tag(array(
                         'href'=>$this->Html->url(array("controller" => "likes",
                                                        "action" => "ajax_show",
                                                        "plugin" => false,
                                                        'Photo_Photo',
                                                        $photo['Photo']['id'],
                                                        1
                                                    )),
                         'title' => __( 'People Who Dislike This'),
                         'innerHtml'=> '<span id="photo_dislike_count2">' . $photo['Photo']['dislike_count'] . '</span>',
                         'data-dismiss' => 'modal'
                  ));
             ?>

                                </li>
                             <?php endif; ?>
                    <li class="pull-right">
<?php if(empty($hide_like)): ?>
                        <a href="javascript:void(0)" id="photo_like_count" data-thumb-up="1" data-id="<?php echo $photo['Photo']['id']?>" class="likePhoto <?php if ( !empty( $uid ) && !empty( $like['Like']['thumb_up'] ) ): ?>active<?php endif; ?>"><i class="material-icons">thumb_up</i></a>
                        <?php
      $this->MooPopup->tag(array(
             'href'=>$this->Html->url(array("controller" => "likes",
                                            "action" => "ajax_show",
                                            "plugin" => false,
                                            'Photo_Photo',
                                            $photo['Photo']['id'],
                                        )),
             'title' => __( 'People Who Like This'),
             'innerHtml'=> '<span id="photo_like_count2">' . $photo['Photo']['like_count'] . '</span>',
          'data-dismiss' => 'modal'
     ));
 ?>
<?php endif; ?>
                    </li>
                    
                    <?php endif; ?>

                    <!-- New hook -->
                    <?php $this->getEventManager()->dispatch(new CakeEvent('photo.element.theater.afterRenderMenuAction', $this,array('photo'=>$photo))); ?>
                    <!-- New hook -->
                </ul>

            <?php if ($is_owner ): ?>
                <a class="closeComment" data-id="<?php echo $photo['Photo']['id']?>" data-type="<?php echo $photo['Photo']['moo_type']?>" data-close="<?php echo $is_close_comment;?>" href="javascript:void(0)" >
                    <?php echo $title; ?>
                </a>
            <?php endif; ?>

                <?php if (Configure::read('core.comment_sort_style') == COMMENT_RECENT): ?>
        
                <?php
                if($is_close_comment && !$is_owner){
                    echo __('%s turn off commenting for this post', $this->Moo->getName($item_close_comment['User']));
                } else {
                    if (!isset($is_member) || $is_member)
                        echo $this->element('comment_form', array('commentFormId' => 'theaterPhotoCommentForm', 'commentFormTextId' => 'theaterPhotoComment', 'target_id' => $photo['Photo']['id'], 'type' => 'Photo_Photo', 'class' => 'commentForm'));
                    else
                        echo __('This a group photo. Only group members can leave comment');
                }
                ?>
                <div class="clear"></div>
                <ul class="list6 comment_wrapper" id="theaterComments">
                <?php echo $this->element('comments', array('blockCommentId' => 'theaterComments','data'=>array_merge($data,array('comment_likes'=>$comment_likes))));?>
                </ul>
                
                <?php elseif(Configure::read('core.comment_sort_style') == COMMENT_CHRONOLOGICAL): ?>
                
                
                <ul class="list6 comment_wrapper" id="theaterComments">
                <?php echo $this->element('comments_chrono', array('blockCommentId' => 'theaterComments','data'=>array_merge($data,array('comment_likes'=>$comment_likes)))) ;?>
                </ul>
                <div class="clear"></div>
                <?php
                if($is_close_comment && !$is_owner){
                    echo __('%s turn off commenting for this post', $this->Moo->getName($item_close_comment['User']));
                } else {
                    if (!isset($is_member) || $is_member)
                        echo $this->element('comment_form', array('commentFormId' => 'theaterPhotoCommentForm', 'commentFormTextId' => 'theaterPhotoComment', 'target_id' => $photo['Photo']['id'], 'type' => 'Photo_Photo', 'class' => 'commentForm'));
                    else
                        echo __('This a group photo. Only group members can leave comment');
                }
                ?>
                
                <?php endif; ?>
    </div>
    <?php endif;?>
</div>
