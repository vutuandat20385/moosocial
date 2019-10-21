<?php
if(Configure::read('Blog.blog_enabled') == 1):
    if(empty($title)) $title = "Popular Entries";
    if(empty($num_item_show)) $num_item_show = 10;
    if(isset($title_enable)&&($title_enable)=== "") $title_enable = false; else $title_enable = true;
   
    $blogHelper = MooCore::getInstance()->getHelper('Blog_Blog')
    ?>
    <?php if (!empty($popular_blogs)): ?>
    <div class="box2">
        <?php if($title_enable): ?>
        <h3><?php echo h($title) ?></h3>
        <?php endif; ?>
        <div class="box_content">

            <?php
            if (!empty($popular_blogs)):
                ?>
                <ul class="blog-block">
                    <?php foreach ($popular_blogs as $blog): ?>
                        <li class="list-item-inline list-item-inline-text">
                            <a href="<?php echo $this->request->base?>/blogs/view/<?php echo $blog['Blog']['id']?>/<?php echo seoUrl($blog['Blog']['title'])?>">
                                <img width="70" src="<?php echo $blogHelper->getImage($blog, array('prefix' => '75_square'))?>" class="img_wrapper2 user_list">
                            </a>
                            <div class="blog_detail">
                                <div class="title-list">
                                    <a href="<?php echo $this->request->base?>/blogs/view/<?php echo $blog['Blog']['id']?>/<?php echo seoUrl($blog['Blog']['title'])?>"><?php echo $blog['Blog']['title'];?></a>

                                </div>
                                <div class="like_count">
                                        <?php echo __n('%s comment', '%s comments', $blog['Blog']['comment_count'], $blog['Blog']['comment_count'] )?> .
                                         <?php echo __n('%s like', '%s likes', $blog['Blog']['like_count'], $blog['Blog']['like_count'] )?>
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
    <?php endif; ?>
<?php endif; ?>