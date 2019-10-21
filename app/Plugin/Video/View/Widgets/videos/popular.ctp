<?php
if(Configure::read('Video.video_enabled') == 1):
    if(empty($title)) $title = "Popular Videos";
    if(isset($title_enable)&&($title_enable)=== "") $title_enable = false; else $title_enable = true;

    $videoHelper = MooCore::getInstance()->getHelper('Video_Video');
    ?>
    <?php if (!empty($popular_videos)): ?>
    <div class="box2">
        <?php if($title_enable): ?>
        <h3><?php echo $title?></h3>
        <?php endif; ?>
        <div class="box_content">
            <?php
            if (!empty($popular_videos)):
                ?>
                <ul class="video_block">
                    <?php foreach ($popular_videos as $video): ?>
                        <li class="col-md-4">
                            <div class="item-content">
                                <a class="video_cover" href="<?php echo $this->request->base?>/videos/view/<?php echo $video['Video']['id']?>/<?php echo seoUrl($video['Video']['title'])?>">
                                    <div>
                                    <img src='<?php echo $videoHelper->getImage($video, array('prefix' => '250'))?>' />
                                    </div>
                                </a>
                                <div class="video_info">
                                    <a href=<?php if ( !empty( $ajax_view ) ): ?>"javascript:void(0)" onclick="loadPage('videos', '<?php echo $this->request->base?>/videos/ajax_view/<?php echo $video['Video']['id']?>', true)"<?php else: ?>"<?php echo $this->request->base?>/videos/view/<?php echo $video['Video']['id']?>/<?php echo seoUrl($video['Video']['title'])?>"<?php endif; ?>><?php echo $this->Text->truncate( $video['Video']['title'], 100 )?></a>
                                    <div class="extra_info">
                                        <?php echo __n('%s like','%s likes',$video['Video']['like_count'],$video['Video']['like_count']); ?>
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                        <div class="clear"></div>
                </ul>
            <?php
            else:
                echo __( 'Nothing found');
            endif;
            ?>
        </div>
    </div>
    <?php endif; ?>
<?php endif; ?>