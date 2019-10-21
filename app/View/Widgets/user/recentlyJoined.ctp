<?php
if(empty($title)) $title = "Recently Joined";
if(empty($num_item_show)) $num_item_show = 10;
if(isset($title_enable)&&($title_enable)=== "") $title_enable = false; else $title_enable = true;

    $new_users = $recentlyJoinedCoreWidget['users'];
?>
<?php if ( !empty( $new_users ) ): ?>
<div class="box2 box_recently_join">
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
            foreach ($new_users as $user): ?>
                <li>
                    <div class=''>
                        <?php echo $this->Moo->getItemPhoto($user, array( 'prefix' => '50_square') ,array('class' => "$tip user_avatar_small", 'title' => Configure::read('core.profile_popup')?'':$user['User']['name']));?>
                    </div>
                </li>
            <?php
            endforeach; ?>
        </ul>
        <div class='clear'></div>
        <?php //echo $this->element('blocks/users_block', array( 'users' => $new_users ));?>
    </div>
</div>
<?php endif; ?>