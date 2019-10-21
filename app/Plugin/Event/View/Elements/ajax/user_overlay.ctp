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

<?php if ( $page == 1 ): ?>
<div class="title-modal">
    <?php if ($rsvp_type == RSVP_AWAITING): ?>
        <?php echo __("%s Awaiting Response", $rsvp_count)?>
    <?php elseif ($rsvp_type == RSVP_ATTENDING): ?>
        <?php echo __("%s Attending", $rsvp_count)?>
    <?php elseif ($rsvp_type == RSVP_NOT_ATTENDING): ?>
        <?php echo __("%s Not Attending", $rsvp_count)?>
    <?php elseif ($rsvp_type == RSVP_MAYBE): ?>
        <?php echo __("%s Maybe Attending", $rsvp_count)?>
    <?php endif; ?>

    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
</div>
<div class="modal-body">
<ul class="users_list event-rsvp" id="list-content2">
<?php endif; ?>

<?php echo $this->element('lists/users_list_bit'); ?>

<?php if (!empty($more_result)):?>
    <?php $this->Html->viewMore($more_url,'list-content2') ?>
<?php endif; ?>

<?php if ( $page == 1 ): ?>
</ul>
</div>
<?php endif; ?>