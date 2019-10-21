<?php if($this->request->is('ajax')) $this->setCurrentStyle(4) ?>
    <script>
function respondRequest(id, status)
{
	jQuery.post('<?php echo $this->request->base?>/groups/ajax_respond', {id: id, status: status}, function(data){
		jQuery('#request_'+id).html(data);
	});
    var request_count = parseInt(jQuery("#join-request").attr("data-request"));
    request_count = request_count - 1;
    if(request_count == 0)
        jQuery("#join-request").parent().remove();
    else if(request_count == 1)
        jQuery("#join-request").html(request_count+' <?php echo addslashes(__('join request'));?>');
    else
        jQuery("#join-request").html(request_count+' <?php echo addslashes(__('join requests'));?>');
    jQuery("#join-request").attr("data-request",request_count);
}

</script>

<?php if (empty($requests)): echo '<div align="center">' . __( 'No join requests') . '</div>';
else: ?>
<div class="title-modal">
    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only"><?php echo __('Close');?></span></button>
    <h4 class="modal-title" id="myModalLabel"><?php echo __('Join Requests');?></h4>
</div>
<div class="modal-body">
<ul class="list6 comment_wrapper join_request_wrapper" style="margin-top:0" id="list-request">
    <?php echo $this->element( 'lists/requests_list');?>
</ul>
</div>
<?php endif; ?>