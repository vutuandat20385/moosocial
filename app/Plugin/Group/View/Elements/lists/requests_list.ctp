<?php foreach ($requests as $request): ?>
    <li id="request_<?php echo $request['GroupUser']['id']?>">
        <div style="float:right">
            <a href="javascript:void(0)" onclick="respondRequest(<?php echo $request['GroupUser']['id']?>, 1)" class="button button-action"><?php echo __( 'Accept')?></a>
            <a href="javascript:void(0)" onclick="respondRequest(<?php echo $request['GroupUser']['id']?>, 0)" class="button button-caution"><?php echo __( 'Delete')?></a>
        </div>
        <?php echo $this->Moo->getItemPhoto(array('User' => $request['User']), array( 'prefix' => '100_square'), array('class' => 'img_wrapper2 user_avatar_large'))?>
        <div class="comment">
            <?php echo $this->Moo->getName($request['User'])?><br />
            <span class="date"><?php echo $this->Moo->getTime( $request['GroupUser']['created'], Configure::read('core.date_format'), $utz )?></span>
        </div>
    </li>
<?php endforeach; ?>

<?php if (!empty($more_requests)):?>
    <?php $this->Html->viewMore($more_url,'list-request') ?>
<?php endif; ?>

<?php if($this->request->is('ajax')): ?>
    <script type="text/javascript">
        require(["jquery","mooBehavior"], function($,mooBehavior) {
            mooBehavior.initMoreResults();
        });
    </script>
<?php else: ?>
    <?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires'=>array('jquery','mooBehavior'), 'object' => array('$', 'mooBehavior'))); ?>
    mooBehavior.initMoreResults();
    <?php $this->Html->scriptEnd(); ?>
<?php endif; ?>