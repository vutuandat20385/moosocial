<?php if ($this->request->is('ajax')) $this->setCurrentStyle(4) ?>

<div class="title-modal">
    <?php echo __('User Tagging'); ?>

    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
</div>
<div class="modal-body">
    <ul class="list1 users_list user-like" id="list-content2">

        <?php echo $this->element('lists/users_list_bit'); ?>

        <?php if (count($users) >= RESULTS_LIMIT): ?>
            <?php $this->Html->viewMore($more_url,'list-content2'); ?>
        <?php endif; ?>
    </ul>
</div>
