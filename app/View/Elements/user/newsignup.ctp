<?php
if(empty($title)) $title = "Recently Joined";
if(empty($num_item_show)) $num_item_show = 8;
if(isset($title_enable)&&($title_enable)=== "") $title_enable = false; else $title_enable = true;

    $new_users = $this->requestAction(
        "users/recently_joined/num_new_members:$num_item_show"
    );
?>
<?php if ( !empty( $new_users ) ): ?>
<?php
	 $tip = 'tip';
	 if (Configure::read('core.profile_popup')){
		$tip = '';
	 } 
?>
<div class="new_recent_signup">
    <div class="box_content">
        <ul class="list_block">
            <?php
            foreach ($new_users as $user): ?>
                <li>
                    <div class=''>
                        <?php echo $this->Moo->getItemPhoto($user, array( 'prefix' => '100_square') ,array('class' => $tip.' user_avatar_small'));?>
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