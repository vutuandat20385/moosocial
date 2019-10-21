<?php if (Configure::read('Blog.blog_enabled') == 1): ?>
    <?php if (!empty($blogs)): ?>
       <div class="box2">
            <div class="box_content">
                <?php
                $blogHelper = MooCore::getInstance()->getHelper('Blog_Blog');
                ?>
                <ul class="blog-block">
                    <?php foreach ($blogs as $blog): ?>
                        <li>
                            <a href="<?php echo  $this->request->base ?>/blogs/view/<?php echo  $blog['Blog']['id'] ?>/<?php echo  seoUrl($blog['Blog']['title']) ?>">
                                <img width="70" src="<?php echo  $blogHelper->getImage($blog, array('prefix' => '75_square')) ?>" class="img_wrapper2 user_list">
                            </a>
                            <div class="blog_detail">
                                <div class="title-list">
                                    <a href="<?php echo  $this->request->base ?>/blogs/view/<?php echo  $blog['Blog']['id'] ?>/<?php echo  seoUrl($blog['Blog']['title']) ?>">
                                        <?php echo $blog['Blog']['title']; ?>
                                    </a>
                                </div>
                                <div class="like_count">
                                    <?php echo  __n('%s comment', '%s comments', $blog['Blog']['comment_count'], $blog['Blog']['comment_count']) ?> .
                                    <?php echo  __n('%s like', '%s likes', $blog['Blog']['like_count'], $blog['Blog']['like_count']) ?>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
       </div>
    <?php endif; ?>
<?php endif; ?>