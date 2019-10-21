<?php
if (count($users) > 0)
{
    ?>
    <?php if ($page == 1): ?>
    <div class="bar-content profile-user-list">
        <div class="content_center">
            <ul class="users_list" id="list-content">
    <?php endif; ?>
    <?php
    foreach ($users as $user):
        ?>
        <li
            class="user-list-index">
            <div class="list-content">
                <div class="user-idx-item">
                    <?php echo $this->Moo->getItemPhoto(array('User' => $user['User']), array('prefix' => '200_square'))?>                       
                </div>
                <div class="user-list-info">
                    <div class="user-name-info">
                        <?php echo $this->Moo->getName($user['User'])?>
                    </div>
                    <div class="">
				<span class="date">
					<?php echo __n( '%s friend', '%s friends', $user['User']['friend_count'], $user['User']['friend_count'] )?> .
                    <?php echo __n( '%s photo', '%s photos', $user['User']['photo_count'], $user['User']['photo_count'] )?><br />
				</span>
                    </div>
                </div>
            </div>
        </li>
        <?php
    endforeach;
    ?>
    <?php if (!empty($url_more)):?>
        <?php $this->Html->viewMore($url_more); ?>
    <?php endif; ?>
    <?php if ($page == 1): ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>
    <?php
}
else
    echo '<div class="bar-content">
        <div class="content_center"><div class="clear">' . __('No more results found') . '</div></div></div>';
?>

<?php if($this->request->is('ajax')): ?>
    <script type="text/javascript">
        require(["jquery","mooBehavior"], function($, mooBehavior) {
        	mooBehavior.initMoreResults();
        });
    </script>
<?php else: ?>
    <?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires'=>array('jquery', 'mooBehavior'), 'object' => array('$', 'mooBehavior'))); ?>
    mooBehavior.initMoreResults();
    <?php $this->Html->scriptEnd(); ?>
<?php endif; ?>