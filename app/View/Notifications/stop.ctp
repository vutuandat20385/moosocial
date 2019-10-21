<script type="text/javascript">
    require(["jquery","mooNotification"], function($,mooNotification) {
        mooNotification.initNotification();
    });
</script>

<div class="title-modal">
    <?php echo  __('Confirm') ?>
    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
</div>
<div class="modal-body">
    <div class="error-message" style="display:none;"></div>
    <div class='create_form'>
        <form id="notificationForm">
            <?php echo $this->Form->hidden('item_type', array('value' => $item_type)); ?>
            <?php echo $this->Form->hidden('item_id', array('value' => $item_id)); ?>
            <div class='col-md-12'>
                <p>
                    <?php if ($notification_stop): ?>
                        <?php echo  __('Are you sure you want to getting notifications of this post?') ?>
                    <?php else: ?>
                        <?php echo  __('Are you sure you want to stop getting notifications of this post?') ?>
                    <?php endif; ?>
                </p>
            </div>
            <div class='col-md-12'>
                <button type="button" class="btn" id="notificationButton">
                    <?php if ($notification_stop): ?>
                    <?php echo  __('Turn On') ?>
                    <?php else: ?>
                    <?php echo  __('Stop') ?>
                    <?php endif; ?>
                </button>
            </div>
            <div class='clear'></div>
        </form>
    </div>
</div>