
<?php if (Configure::read('Video.video_enabled') == 1): ?>
    <?php if (count($videos) > 0): ?>
        <div class="box2">
            <h3><?php echo  __('Videos') ?></h3>
            <div class="box_content">
                <?php
                $videoHelper = MooCore::getInstance()->getHelper('Video_Video');
                ?>
                <ul class="video_block">
                    <?php foreach ($videos as $video): ?>
                        <li class="col-md-4">
                            <div class="item-content">
                                <a class="video_cover" href="<?php echo  $this->request->base ?>/videos/view/<?php echo  $video['Video']['id'] ?>/<?php echo  seoUrl($video['Video']['title']) ?>">
                                    <div>
                                    <img src="<?php echo  $videoHelper->getImage($video, array('prefix' => '250')) ?>" class="img_wrapper2">
                                    </div>
                                    </a>
                                <div class="video_info">
                                    <a href="<?php echo  $this->request->base ?>/videos/view/<?php echo  $video['Video']['id'] ?>/<?php echo  seoUrl($video['Video']['title']) ?>">
                                        <?php echo $this->Text->truncate($video['Video']['title'], 40, array('exact' => false)) ?>
                                    </a>
                                    <div class="like_count">
                                        <?php echo  __n('%s like', '%s likes', $video['Video']['like_count'], $video['Video']['like_count']) ?>
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>