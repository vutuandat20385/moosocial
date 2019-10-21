<?php
if(empty($title)) $title = "Featured Members";
if(empty($num_item_show)) $num_item_show = 10;
if(isset($title_enable)&&($title_enable)=== "") $title_enable = false; else $title_enable = true;

?>

<?php if ( !empty( $featured_users ) ): ?>
<div class="box2">
    <?php if($title_enable): ?>
    <h3><?php echo $title;?></h3>
    <?php endif; ?>
    <div class="box_content box_featured_user">
        <ul class="list_block">
        	<?php
	        	 $tip = 'tip';
	             if (Configure::read('core.profile_popup')){
	             	$tip = '';
	             } 
	        ?>
            <?php
            foreach ($featured_users as $user): ?>
                <li>
                <?php echo $this->Moo->getItemPhoto(array('User' => $user['User']), array( 'prefix' => '50_square'), array('class' => 'img_wrapper2 '.$tip.' user_avatar_large'))?>
                </li>
            <?php
            endforeach; ?>
        </ul>
        <div class="clear"></div>
    </div>
</div>
<?php endif; ?>