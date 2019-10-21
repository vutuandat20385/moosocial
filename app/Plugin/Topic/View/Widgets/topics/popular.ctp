<?php
if(Configure::read('Topic.topic_enabled') == 1):
if(empty($title)) $title = "Popular Topics";
if(empty($num_item_show)) $num_item_show = 10;
if(isset($title_enable)&&($title_enable)=== "") $title_enable = false; else $title_enable = true;
$topicHelper = MooCore::getInstance()->getHelper('Topic_Topic');

?>
<?php if (!empty($popular_topics)): ?>
<div class="box2">
    <?php if($title_enable): ?>
    <h3><?php echo $title; ?></h3>
    <?php endif; ?>
    <div class="box_content">

        <?php
        if (!empty($popular_topics)):
            ?>
            <ul class="topic-block">
                <?php foreach ($popular_topics as $topic): ?>
                    <li>
                        <a href=<?php if ( !empty( $ajax_view ) ): ?>"javascript:void(0)" onclick="loadPage('topics', '<?php echo $this->request->base?>/topics/ajax_view/<?php echo $topic['Topic']['id']?>')"<?php else: ?>"<?php echo $this->request->base?>/topics/view/<?php echo $topic['Topic']['id']?>/<?php echo seoUrl($topic['Topic']['title'])?>"<?php endif; ?>>
                            <img width="70" src="<?php echo $topicHelper->getImage($topic, array('prefix' => '75_square'))?>" class="img_wrapper2 user_list" />
                         </a>
                        <div class="topic_info">
                            <div class="title">
                                <a href="<?php echo $this->request->base?>/topics/view/<?php echo $topic['Topic']['id']?>/<?php echo seoUrl($topic['Topic']['title'])?>">
                                    <?php echo $topic['Topic']['title']?>
                                </a>
                            </div>
                            <div class="like_count">
                                <?php echo __n('%s reply', '%s replies', $topic['Topic']['comment_count'], $topic['Topic']['comment_count'] )?>,
                                <?php echo __n('%s like', '%s likes', $topic['Topic']['like_count'], $topic['Topic']['like_count'] )?>
                            </div>
                        </div>
                            </li>
                <?php endforeach; ?>
            </ul>
        <?php
        else:
            echo __('Nothing found');
        endif;
        ?>
    </div>
</div>
<?php endif;endif; ?>