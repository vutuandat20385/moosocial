
<ul class="blog-content-list">

<?php
$blogHelper = MooCore::getInstance()->getHelper('Blog_Blog');
if (!empty($blogs) && count($blogs) > 0)
{
	$i = 1;
	foreach ($blogs as $blog):
?>
        <li class="full_content p_m_10">
            <a href="<?php echo $this->request->base?>/blogs/view/<?php echo $blog['Blog']['id']?>/<?php echo seoUrl($blog['Blog']['title'])?>">
            <img width="140" src="<?php echo $blogHelper->getImage($blog, array('prefix' => '150_square'))?>" class="img_wrapper2 user_list thumb_mobile">
            </a>
            <div class="blog-info">
          
                <a class="title" href="<?php echo $this->request->base?>/blogs/view/<?php echo $blog['Blog']['id']?>/<?php echo seoUrl($blog['Blog']['title'])?>">
                    <?php echo $blog['Blog']['title'] ?>
                </a>


            <?php if( !empty($uid) && (($blog['Blog']['user_id'] == $uid ) || ( !empty($cuser) && $cuser['Role']['is_admin'] ) ) ): ?>
                <div class="list_option">
                    <div class="dropdown">
                        <button id="dropdown-edit" data-target="#" data-toggle="dropdown" >
                            <i class="material-icons">more_vert</i>
                        </button>
                        <ul role="menu" class="dropdown-menu" aria-labelledby="dropdown-edit" style="float: right;">
                            
                            <?php if ($blog['User']['id'] == $uid || ( !empty($cuser) && $cuser['Role']['is_admin'] )): ?>
                                <li><a href="<?php echo $this->request->base?>/blogs/create/<?php echo $blog['Blog']['id']?>"> <?php echo __( 'Edit Entry')?></a></li>
                            <?php endif; ?>
                            <?php if ( ($blog['Blog']['user_id'] == $uid ) || ( !empty( $blog['Blog']['id'] ) && !empty($cuser) && $cuser['Role']['is_admin'] ) ): ?>
                                <li><a href="javascript:void(0)" data-id="<?php echo $blog['Blog']['id']?>" class="deleteBlog" > <?php echo __( 'Delete Entry')?></a></li>
                                <li class="seperate"></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <div class="extra_info">
                    <?php echo __( 'Posted by')?> <?php echo $this->Moo->getName($blog['User'], false)?>
                    <?php echo $this->Moo->getTime( $blog['Blog']['created'], Configure::read('core.date_format'), $utz )?> &nbsp;
                    <?php
                        switch($blog['Blog']['privacy']){
                            case 1:
                                $icon_class = 'public';
                                $tooltip = 'Shared with: Everyone';
                                break;
                            case 2:
                                $icon_class = 'people';
                                $tooltip = 'Shared with: Friends Only';
                                break;
                            case 3:
                                $icon_class = 'lock';
                                $tooltip = 'Shared with: Only Me';
                                break;
                        }
                    ?>
                    <a style="color:#888;" class="tip" href="javascript:void(0);" original-title="<?php echo  $tooltip ?>"> <i class="material-icons"><?php echo  $icon_class ?></i></a>
                </div>
           
			<div class="blog-description-truncate">
                            <div>
				<?php 
                                echo $this->Text->convert_clickable_links_for_hashtags($this->Text->truncate(strip_tags(str_replace(array('<br>','&nbsp;'), array(' ',''), $blog['Blog']['body'])), 200, array('eclipse' => '')), Configure::read('Blog.blog_hashtag_enabled'));
				?>
                            </div>
                            <div class="like-section">
                                <div class="like-action">
                                    <a href="<?php echo  $this->request->base ?>/blogs/view/<?php echo  $blog['Blog']['id'] ?>/<?php echo seoUrl($blog['Blog']['title'])?>">
                                        <i class='material-icons'>comment</i>
                                    </a>
                                    <a href="<?php echo  $this->request->base ?>/blogs/view/<?php echo  $blog['Blog']['id'] ?>/<?php echo seoUrl($blog['Blog']['title'])?>">
                                        <span id="comment_count"><?php echo $blog['Blog']['comment_count']?></span>
                                    </a>
<?php $this->getEventManager()->dispatch(new CakeEvent('element.items.renderLikeButton', $this,array('uid' => $uid,'item' => array('id' => $blog['Blog']['id'], 'like_count' => $blog['Blog']['like_count']), 'item_type' => 'Blog_Blog' ))); ?>
<?php $this->getEventManager()->dispatch(new CakeEvent('element.items.renderLikeReview', $this,array('uid' => $uid,'item' => array('id' => $blog['Blog']['id'], 'like_count' => $blog['Blog']['like_count']), 'item_type' => 'Blog_Blog' ))); ?>
<?php if(empty($hide_like)): ?>
                                    <a data-type="Blog_Blog" data-id="<?php echo $blog['Blog']['id']?>" data-status="1" href="javascript:void(0)" class="likeItem <?php if (!empty($uid) && !empty($like[$blog['Blog']['id']])): ?>active<?php endif; ?>">
                                        <i class="material-icons">thumb_up</i>
                                    </a>
                                    <?php
                                    $this->MooPopup->tag(array(
                                        'href'=>$this->Html->url(array(
                                            "controller" => "likes",
                                            "action" => "ajax_show",
                                            "plugin" => false,
                                            'Blog_Blog',
                                            $blog['Blog']['id'],
                                        )),
                                        'title' => __('People Who Like This'),
                                        'innerHtml'=>'<span class="likeCount">'.$blog['Blog']['like_count'].'</span>',
                                    ));
                                    ?>
<?php endif; ?>
                                    <?php if(empty($hide_dislike)): ?>
                                    <a data-type="Blog_Blog" data-id="<?php echo $blog['Blog']['id']?>" data-status="0" href="javascript:void(0)" class="likeItem <?php if (!empty($uid) && isset($like[$blog['Blog']['id']]) && $like[$blog['Blog']['id']] == 0): ?>active<?php endif; ?>">
                                        <i class="material-icons">thumb_down</i>
                                    </a>
                                    <?php
                                    $this->MooPopup->tag(array(
                                        'href'=>$this->Html->url(array(
                                            "controller" => "likes",
                                            "action" => "ajax_show",
                                            "plugin" => false,
                                            'Blog_Blog',
                                            $blog['Blog']['id'],
                                            '1',
                                        )),
                                        'title' => __('People Who DisLike This'),
                                        'innerHtml'=>'<span class="dislikeCount">'.$blog['Blog']['dislike_count'].'</span>',
                                    ));
                                    ?>
                                    <?php endif; ?>
                                    <a href="<?php echo  $this->request->base ?>/blogs/view/<?php echo  $blog['Blog']['id'] ?>/<?php echo seoUrl($blog['Blog']['title'])?>">
                                        <i class="material-icons">share</i> <span><?php echo  $blog['Blog']['share_count'] ?></span>
                                    </a>
                                </div>
                            </div>
			</div>
                <div class="clear"></div>
                <div class="extra_info">
                    <?php $this->Html->rating($blog['Blog']['id'],'blogs', 'Blog'); ?>
                </div>
        </div>
	</li>
<?php
    $i++;
	endforeach;
}
else
	echo '<div class="clear no-result-found" align="center">' . __( 'No more results found') . '</div>';
?>
<?php if (isset($more_url)&& !empty($more_result)): ?>
    <?php $this->Html->viewMore($more_url) ?>
<?php endif; ?>
</ul>
<?php if($this->request->is('ajax')): ?>
<script type="text/javascript">
    require(["jquery","mooBlog"], function($,mooBlog) {
        mooBlog.initOnListing();
    });
</script>
<?php else: ?>
<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires'=>array('jquery','mooBlog'), 'object' => array('$', 'mooBlog'))); ?>
mooBlog.initOnListing();
<?php $this->Html->scriptEnd(); ?> 
<?php endif; ?>
