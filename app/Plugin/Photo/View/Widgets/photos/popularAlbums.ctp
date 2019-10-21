<?php if (Configure::read('Photo.photo_enabled') == 1): ?>
<?php
$photoHelper = MooCore::getInstance()->getHelper('Photo_Photo');
if(empty($title)) $title = "Popular Albums";
if(empty($num_item_show)) $num_item_show = 10;
if(isset($title_enable)&&($title_enable)=== "") $title_enable = false; else $title_enable = true;

?>
<?php if (!empty($popular_albums)):?>
<div class="box2">
    <?php if($title_enable): ?>
    <h3><?php echo $title; ?></h3>
    <?php endif; ?>
    <div class="box_content popular-album">
        <ul class="album-block">
            <?php foreach ($popular_albums as $album): ?>
                <li>
                    <div>
                    <a class="popular_album_cover" href="<?php echo $this->request->base?>/albums/view/<?php echo $album['Album']['id']?>/<?php echo seoUrl($album['Album']['moo_title'])?>">
                        <img src="<?php echo $photoHelper->getAlbumCover($album['Album']['cover'], array('prefix' => '150_square')) . '?' . time(); ?>" alt="<?php echo ($album['Album']['moo_title'])?>" />
                        <div class="gradient_bg"></div>
                        <div class="album-title">
                            <?php echo ($album['Album']['moo_title'])?>
                        </div>
                    </a>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="clear"></div>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>