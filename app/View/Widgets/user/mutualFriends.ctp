<?php if($uid != $user['User']['id']): ?>
    <?php
    if(empty($title)) $title = "Mutual Friends";
    if(empty($num_item_show)) $num_item_show = 10;
    if(isset($title_enable)&&($title_enable)=== "") $title_enable = false; else $title_enable = true;

    
    ?>

    <?php if ( !empty( $mutual_friends ) ): ?>

    <div class="box2 ">
        <?php if($title_enable): ?>
        <h3>
            <?php
      $this->MooPopup->tag(array(
             'href'=>$this->Html->url(array("controller" => "friends",
                                            "action" => "ajax_show_mutual",
                                            "plugin" => false,
                                            $user['User']['id']
                                            
                                        )),
             'title' => $title,
             'innerHtml'=> $title,
     ));
 ?>
           </h3>
        <?php endif; ?>
        <?php
        	 $tip = 'tip';
             if (Configure::read('core.profile_popup')){
             	$tip = '';
             } 
        ?>
        <div class="box_content">
            <ul class="list3">
                <?php
                foreach ($mutual_friends as $user): ?>
                    <li><?php echo $this->Moo->getItemPhoto(array('User' => $user['User']),array( 'prefix' => '50_square'), array('class' => 'img_wrapper2 '.$tip.' user_avatar_large'))?></li>
                <?php
                endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

<?php endif; ?>