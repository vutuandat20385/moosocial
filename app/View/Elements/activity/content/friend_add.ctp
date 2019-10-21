<?php
	$ids = explode(',',$activity['Activity']['items']);
	$userModel = MooCore::getInstance()->getModel('User');	
	$users = $userModel->find( 'all', array( 'conditions' => array( 'User.id' => $ids ), 'limit'=>10
													 ) ); 
	$userModel->cacheQueries = false;
?>
<ul class="activity_content activity_friend_add">
<?php foreach ( $users as $u ): ?>
	<li class="user-list-index"><?php echo $this->element('user/item', array('user' => $u)); ?></li>
<?php endforeach; ?>
</ul>