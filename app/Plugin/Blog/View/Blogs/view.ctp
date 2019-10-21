<?php
$blogHelper = MooCore::getInstance()->getHelper('Blog_Blog');
?>

<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires'=>array('jquery', 'mooBlog', 'hideshare'),'object'=>array('$', 'mooBlog'))); ?>
mooBlog.initOnView();
$(".sharethis").hideshare({media: '<?php echo $blogHelper->getImage($blog, array('prefix' => '300_square'));?>', linkedin: false});
<?php $this->Html->scriptEnd(); ?>

<?php $this->setNotEmpty('west');?>
<?php $this->start('west'); ?>
        <div class="box2">
            <div class="blog-owner">
                <?php echo $this->Moo->getImage(array('User' => $blog['User']), array("prefix" => "50_square", "alt"=>$blog['User']['name'])); ?>
                <div class="menu">
                    <ul>
                        <li>
                            <?php echo $this->Moo->getName($blog['User'], true); ?>
                        </li>
                        <li>
                            <span class="extra_info">
				<?php echo __n( '%s friend', '%s friends', $blog['User']['friend_count'], $blog['User']['friend_count'] )?> .
				<?php echo __n( '%s photo', '%s photos', $blog['User']['photo_count'], $blog['User']['photo_count'] )?>
                            </span>
                        </li>
                        <?php if ( isset($friends_request) && in_array($blog['User']['id'], $friends_request) && $blog['User']['id'] != $uid): ?>
                            <li>
                                <a href="<?php echo $this->request->base?>/friends/ajax_cancel/<?php echo $blog['User']['id']?>" id="blogCancelFriend" class="" title="<?php __('Cancel a friend request');?>">
                                    <i class="material-icons">person_add</i><?php echo __('Cancel Request')?>
                                </a>
                            </li>
                        <?php elseif ( !empty($respond) && in_array($blog['User']['id'], $respond ) && $blog['User']['id'] != $uid): ?>
                        <li>
                            <div class="dropdown" style="" >
                                <a href="#" id="respond" data-target="#" data-toggle="dropdown" aria-haspopup="true" role="button" aria-expanded="false" class="" title="<?php __('Respond to Friend Request');?>">
                                    <i class="material-icons">person_add</i> <?php echo __('Respond to Friend Request')?>
                                </a>

                                <ul class="dropdown-menu" role="menu" aria-labelledby="respond">
                                    <li><a data-id="<?php echo  $request_id[$blog['User']['id']]; ?>" data-status="1" class="respondRequest" href="javascript:void(0)"><?php echo  __('Accept'); ?></a></li>
                                    <li><a data-id="<?php echo  $request_id[$blog['User']['id']]; ?>" data-status="0" class="respondRequest" href="javascript:void(0)"><?php echo  __('Delete'); ?></a></li>
                                </ul>
                            </div>
                        </li>
                        
                        <?php elseif ( !empty($uid) && ($uid != $blog['User']['id']) && !$areFriends ): ?>
                            <li>
                                <?php
                                    $this->MooPopup->tag(array(
                                        'id'=>'blogAddFriend',
                                        'href'=>$this->Html->url(array(
                                            "controller" => "friends",
                                            "action" => "ajax_add",
                                            "plugin" => false,
                                            $blog['User']['id'],
                                        )),
                                        'title' => sprintf( __( 'Send %s a friend request'), $blog['User']['name'] ),
                                        'innerHtml'=>'<i class="material-icons">person_add</i>'.__( 'Add as Friend'),
                                    ));
                                ?>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
	
	<?php if(!empty($other_entries)): ?>
        <div class="box2">
            <h3><?php echo __( 'Other Entries')?></h3>
            <div class="box_content">
                <?php echo $this->element('blocks/blogs_block', array('blogs' => $other_entries)); ?>
            </div>
        </div>
        <?php endif; ?>
        <?php if(!empty($tags)): ?>
	<div class="box2">
            <h3><?php echo __( 'Tags')?></h3>
            <div class="box_content">
                <?php echo $this->element( 'blocks/tags_item_block' ); ?>
            </div>
	</div>
	<?php endif; ?>

		
<?php $this->end(); ?>

<div class="bar-content full_content p_m_10">
    <div class="content_center">
	<div class="post_body">
        <div class="mo_breadcrumb">
            <h1><?php echo $blog['Blog']['title']?></h1>
            <?php if(!empty($uid)): ?>
            <div class="list_option">
                <div class="dropdown">
                    <button id="dropdown-edit" data-target="#" data-toggle="dropdown">
                        <i class="material-icons">more_vert</i>
                    </button>

                    <ul role="menu" class="dropdown-menu" aria-labelledby="dropdown-edit">
                        
                        <?php if ($blog['User']['id'] == $uid || ( !empty($cuser) && $cuser['Role']['is_admin'] )): ?>
                            <li><a href="<?php echo $this->request->base?>/blogs/create/<?php echo $blog['Blog']['id']?>"> <?php echo __( 'Edit Entry')?></a></li>
                        <?php endif; ?>
                        <?php if ( ($blog['Blog']['user_id'] == $uid ) || ( !empty( $blog['Blog']['id'] ) && !empty($cuser) && $cuser['Role']['is_admin'] ) ): ?>
                            <li><a href="javascript:void(0)" data-id="<?php echo $blog['Blog']['id']?>" class="deleteBlog"> <?php echo __( 'Delete Entry')?></a></li>
                            <li class="seperate"></li>
                        <?php endif; ?>
                        <li>
                            <?php
                            $this->MooPopup->tag(array(

                                'href'=>$this->Html->url(array(
                                    "controller" => "reports",
                                    "action" => "ajax_create",
                                    "plugin" => false,
                                    'Blog_Blog',
                                    $blog['Blog']['id'],
                                )),
                                'title' => __( 'Report Blog'),
                                'innerHtml'=>__( 'Report Blog'),
                            ));
                            ?>
                        </li>
                        <?php if ($blog['Blog']['privacy'] != PRIVACY_ME): ?>
                        
                        <?php endif; ?>
                        
                    </ul>
                </div>
            </div>
            <?php endif; ?>
        </div>
            
            <div class="post_content">
	    <?php echo $this->Moo->cleanHtml($this->Text->convert_clickable_links_for_hashtags( $blog['Blog']['body']  , Configure::read('Blog.blog_hashtag_enabled') ))?>
            </div>
	    <div class="extra_info"><?php echo __( 'Posted in')?> <a href="<?php echo $this->request->base?>/blogs/index/<?php echo $blog['Blog']['category_id']?>/<?php echo seoUrl($blog['Category']['name'])?>"><strong><?php echo $blog['Category']['name']?></strong></a> <?php echo $this->Moo->getTime($blog['Blog']['created'], Configure::read('core.date_format'), $utz)?></div>
        <?php $this->Html->rating($blog['Blog']['id'],'blogs','Blog'); ?>
    </div>
    </div>
</div>
<div class="bar-content full_content p_m_10">
    <div class="content_center">
        <?php echo $this->element('likes', array('shareUrl' => $this->Html->url(array(
                                'plugin' => false,
                                'controller' => 'share',
                                'action' => 'ajax_share',
                                'Blog_Blog',
                                'id' => $blog['Blog']['id'],
                                'type' => 'blog_item_detail'
                            ), true), 'item' => $blog['Blog'], 'type' => $blog['Blog']['moo_type'])); ?>
    </div>
</div>
<div class="bar-content full_content p_m_10 blog-comment">
   	<?php echo $this->renderComment();?>
</div>