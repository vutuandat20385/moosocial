<?php
if(empty($title)) $title = "Friends";
if(empty($num_item_show)) $num_item_show = 10;
if(isset($title_enable)&&($title_enable)=== "") $title_enable = false; else $title_enable = true;

$friends = $friendCoreWidget;
?>

<?php if ( !empty( $friends ) ): ?>
<div class="box2 box-friend">
    <?php if($title_enable): ?>
    <h3><?php echo $title; ?></h3>
    <?php endif; ?>
    <div class="box_content">
        <ul class="list_block">
            <?php
            $tip = 'tip';
                if (Configure::read('core.profile_popup')){
                    $tip = '';
                }
            foreach ($friends as $user): ?>
                <li><?php echo $this->Moo->getItemPhoto(array('User' => $user['User']),array('prefix' => '50_square'),array('class' => "user_avatar_small $tip"))?></li>
            <?php
            endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>