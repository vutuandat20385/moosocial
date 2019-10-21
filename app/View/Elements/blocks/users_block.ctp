<?php
         $tip = 'tip';
         if (Configure::read('core.profile_popup')){
            $tip = '';
         } 
?>
<ul class="list_block">
<?php
foreach ($users as $user): ?>
	<li>
            
                <?php echo $this->Moo->getItemPhoto(array('User' => $user['User']),array('prefix' => '50_square'),array('class' => $tip))?>
           
        </li>
<?php
endforeach; ?>
</ul>
<div class='clear'></div>