<?php
if ( !( empty($uid) && Configure::read('core.force_login') ) ):
    if(empty($num_item_show)) $num_item_show=10;
    if(isset($title_enable)&&($title_enable)=== "") $title_enable = false; else $title_enable = true;
    
    $online = $onlineUsersCoreWidget['online'];
    ?>

<?php if ( !empty( $online['members'] ) ): ?>
    <div class="box2">
        <?php if($title_enable): ?>
            <h3><a href="<?php echo $this->request->base?>/users/index/online:1">
                    <?php if(empty($title)) $title = __("Who's Online");?>
                    <?php echo $title; ?>
                </a>
            </h3>
        <?php endif; ?>
        <div class="box_content box_online_user">
            <?php if ( !empty( $online['members'] ) ): 
                $tip = 'tip';
                if (Configure::read('core.profile_popup')){
                    $tip = '';
                }
                ?>
                <ul class="list_block">
                    <?php foreach ($online['members'] as $u): ?>
                        <li>
                                <?php echo $this->Moo->getItemPhoto(array('User' => $u['User']), array( 'prefix' => '50_square'), array('class' => "img_wrapper2 user_avatar_large $tip", 'title' => Configure::read('core.profile_popup')?'':$user['User']['name']))?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <div class='clear'></div>
            <?php endif; ?>
            <div class='p_10'>
            <?php
                if(empty($member_only))
                    printf( __('There are currently %s and %s online'), __n( '%s member', '%s members', count($online['userids']), count($online['userids']) ), __n( '%s guest', '%s guests', $online['guests'], $online['guests'] ) );
                else
                    echo __n('There is currently %s member online', 'There are currently %s members online', count($online['userids']), count($online['userids']) );
            ?>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php
endif;
?>
