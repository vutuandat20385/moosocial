<div class="title-modal">
    <?php echo __('Error')?>
    <button onclick="return $(this).parents('.notify_content').find('#notificationDropdown').dropdown('toggle')" type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
</div>
<div class="modal-body">
    <div class="bar-content full_content p_m_10">
        <div class="content_center">
            <?php echo $msg; ?>
        </div>
    </div>
</div>