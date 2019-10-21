

<?php if(Configure::read('Topic.topic_enabled') == 1): ?>
<ul class="topic-content-list">
<?php
$topicHelper = MooCore::getInstance()->getHelper('Topic_Topic');
if (!empty($topics) && count($topics) > 0)
{
    $i = 1;
	foreach ($topics as $topic):
?>
	<li class="full_content p_m_10" <?php if( $i == count($topics) ) echo 'style="border-bottom:0"'; ?>>
        <?php if(!empty( $ajax_view )): ?>
            <a class="ajaxLoadTopicDetail" href="javascript:void(0)" data-url="<?php echo  $this->request->base ?>/topics/ajax_view/<?php echo  $topic['Topic']['id'] ?>">
                <img width="140" src="<?php echo $topicHelper->getImage($topic, array('prefix' => '150_square'))?>" class="topic-thumb" />
            </a>
        <?php else: ?>
            <a href="<?php echo  $this->request->base ?>/topics/view/<?php echo  $topic['Topic']['id'] ?>/<?php echo  seoUrl($topic['Topic']['title']) ?>">
                <img width="140" src="<?php echo $topicHelper->getImage($topic, array('prefix' => '150_square'))?>" class="topic-thumb" />
            </a>
        <?php endif; ?>
        <?php if(!empty($uid) && (($topic['Topic']['user_id'] == $uid ) ||  (!empty($cuser) && $cuser['Role']['is_admin']) ) ): ?>
        <div class="list_option">
                <div class="dropdown">
                    <button id="dLabel" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                      <i class="material-icons">more_vert</i>
                    </button>

                    <ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">
                        <?php if ( ( !empty($cuser) && $cuser['Role']['is_admin'] ) ): ?>
                            <?php if ( !$topic['Topic']['pinned'] ): ?>
                            <li><a href='<?php echo $this->request->base?>/topics/do_pin/<?php echo $topic['Topic']['id']?>'><?php echo __( 'Pin Topic')?></a></li>
                            <?php else: ?>
                            <li><a href='<?php echo $this->request->base?>/topics/do_unpin/<?php echo $topic['Topic']['id']?>'><?php echo __( 'Unpin Topic')?></a></li>
                            <?php endif; ?>

                            <?php if ( !$topic['Topic']['locked'] ): ?>
                            <li><a href='<?php echo $this->request->base?>/topics/do_lock/<?php echo $topic['Topic']['id']?>'><?php echo __( 'Lock Topic')?></a></li>
                            <?php else: ?>
                            <li><a href='<?php echo $this->request->base?>/topics/do_unlock/<?php echo $topic['Topic']['id']?>'><?php echo __( 'Unlock Topic')?></a></li>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if ( ($topic['Topic']['user_id'] == $uid ) || ( !empty($cuser['Role']['is_admin']) ) ): ?>
                        <li><?php echo $this->Html->link(__( 'Edit Topic'), array(
                          'plugin' => 'Topic',
                          'controller' => 'topics',
                          'action' => 'create',
                          $topic['Topic']['id']
                      )); ?></li>
                        <li><a href="javascript:void(0);" class="deleteTopic" data-id="<?php echo $topic['Topic']['id']?>"><?php echo __( 'Delete')?></a></li>
                        <li class="seperate"></li>
                        <?php endif; ?>
                        
                         
                    </ul>
                </div>
            </div>
        <?php endif; ?>
		<div class="topic-info">
            <?php if(!empty( $ajax_view )): ?>
                <a class="ajaxLoadTopicDetail title" href="javascript:void(0)" data-url="<?php echo  $this->request->base ?>/topics/ajax_view/<?php echo  $topic['Topic']['id'] ?>"><?php echo  $topic['Topic']['title'] ?></a>
            <?php else: ?>
                <a class="title" href="<?php echo  $this->request->base ?>/topics/view/<?php echo  $topic['Topic']['id'] ?>/<?php echo  seoUrl($topic['Topic']['title']) ?>"><?php echo $topic['Topic']['title'] ?></a>
            <?php endif; ?>
			
			<?php if ( $topic['Topic']['pinned'] ): ?>
			<i class="material-icons icon-small tip" title="<?php echo __( 'Pinned')?>">offline_pin</i>
			<?php endif; ?>
			<?php if ( $topic['Topic']['attachment'] ): ?>
                        <i class="material-icons icon-small tip" title="<?php echo __( 'Attached files')?>">attach_file</i>
                        <?php endif; ?>
                                    <?php if ( $topic['Topic']['locked'] ): ?>
                        <i class="material-icons icon-small tip" title="<?php echo __( 'Locked')?>">lock</i>
                        <?php endif; ?>
                        <div class="extra_info">
                            <?php echo __( 'Last posted by %s', $this->Moo->getName($topic['LastPoster'], false))?>
                            <?php echo $this->Moo->getTime( $topic['Topic']['last_post'], Configure::read('core.date_format'), $utz )?>
    			</div>
			<div class="topic-description-truncate">
                            <div>
                            <?php echo $this->Text->convert_clickable_links_for_hashtags($this->Text->truncate(strip_tags(str_replace(array('<br>','&nbsp;'), array(' ',''), $topic['Topic']['body'])), 200, array('exact' => false)), Configure::read('Topic.topic_hashtag_enabled')) ?>
                            </div>
                            <div class="like-section">
                                <div class="like-action">
                                    
                                    <a href="<?php echo  $this->request->base ?>/topics/view/<?php echo  $topic['Topic']['id'] ?>/<?php echo seoUrl($topic['Topic']['title'])?>">
                                        <i class='material-icons'>comment</i>
                                    </a>
                                    <a href="<?php echo  $this->request->base ?>/topics/view/<?php echo  $topic['Topic']['id'] ?>/<?php echo seoUrl($topic['Topic']['title'])?>">
                                        <span><?php echo $topic['Topic']['comment_count']?></span>
                                    </a>
<?php $this->getEventManager()->dispatch(new CakeEvent('element.items.renderLikeButton', $this,array('uid' => $uid,'item' => array('id' => $topic['Topic']['id'], 'like_count' => $topic['Topic']['like_count']), 'item_type' => 'Topic_Topic' ))); ?>
<?php $this->getEventManager()->dispatch(new CakeEvent('element.items.renderLikeReview', $this,array('uid' => $uid,'item' => array('id' => $topic['Topic']['id'], 'like_count' => $topic['Topic']['like_count']), 'item_type' => 'Topic_Topic' ))); ?>
<?php if(empty($hide_like)): ?>
                                    <a data-type="Topic_Topic" data-id="<?php echo $topic['Topic']['id']?>" data-status="1" href="javascript:void(0)" class="likeItem <?php if (!empty($uid) && !empty($like[$topic['Topic']['id']])): ?>active<?php endif; ?>">
                                        <i class="material-icons">thumb_up</i>
                                    </a>
                                    <?php
      $this->MooPopup->tag(array(
             'href'=>$this->Html->url(array("controller" => "likes",
                                            "action" => "ajax_show",
                                            "plugin" => false,
                                            'Topic_Topic',
                                            $topic['Topic']['id'],
                                        )),
             'title' => __('People Who Like This'),
             'innerHtml'=> '<span class="likeCount">' . $topic['Topic']['like_count'] . '</span>',
     ));
 ?>
<?php endif; ?>
                                    <?php if(empty($hide_dislike)): ?>
                                    <a data-type="Topic_Topic" data-id="<?php echo $topic['Topic']['id']?>" data-status="0" href="javascript:void(0)" class="likeItem <?php if (!empty($uid) && isset($like[$topic['Topic']['id']]) && $like[$topic['Topic']['id']] == 0): ?>active<?php endif; ?>">
                                        <i class="material-icons">thumb_down</i>
                                    </a>
                                    
                                    <?php
                                    $this->MooPopup->tag(array(
                                             'href'=>$this->Html->url(array("controller" => "likes",
                                                                            "action" => "ajax_show",
                                                                            "plugin" => false,
                                                                            'Topic_Topic',
                                                                            $topic['Topic']['id'], 1
                                                                        )),
                                             'title' => __('People Who DisLike This'),
                                             'innerHtml'=>  '<span class="dislikeCount">' . $topic['Topic']['dislike_count'] . '</span>',
                                    ));
                                    ?>
                                    <?php endif; ?>
 <a href="<?php echo  $this->request->base ?>/topics/view/<?php echo  $topic['Topic']['id'] ?>/<?php echo seoUrl($topic['Topic']['title'])?>">
                                        <i class="material-icons">share</i> <span><?php echo  $topic['Topic']['share_count'] ?></span>
                                    </a>
                                   
                                </div>


                            </div>
                        </div>
            <div class="clear"></div>
            <div class="extra_info">
                <?php $this->Html->rating($topic['Topic']['id'],'topics', 'Topic'); ?>
            </div>
		</div>
	</li>
<?php
    $i++;
	endforeach;
}
else
	echo '<div class="clear text-center no-result-found">' . __( 'No more results found') . '</div>';
?>
<?php if (isset($more_url)&& !empty($more_result)): ?>
    <?php $this->Html->viewMore($more_url) ?>
<?php endif; ?>
</ul>
<?php endif; ?>

<?php if($this->request->is('ajax')): ?>
<script type="text/javascript">
    require(["jquery","mooTopic","mooGroup"], function($,mooTopic,mooGroup) {
        <?php if(!empty( $ajax_view )): ?>
            mooTopic.initOnGroupListing();
            mooGroup.initOnTopicList();
        <?php else: ?>
            mooTopic.initOnListing();
        <?php endif;?>
    });
</script>
<?php else: ?>
<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires'=>array('jquery','mooTopic'), 'object' => array('$', 'mooTopic'))); ?>
mooTopic.initOnListing();
<?php $this->Html->scriptEnd(); ?> 
<?php endif; ?>