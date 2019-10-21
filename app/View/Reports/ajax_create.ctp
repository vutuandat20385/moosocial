<?php if($this->request->is('ajax')) $this->setCurrentStyle(4) ?>

<script type="text/javascript">
    require(["jquery","mooBehavior"], function($,mooBehavior) {
        mooBehavior.initOnReportItem();
    });
</script>
 
<div class="title-modal">
    <?php echo __('Report')?>
    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
</div>
<div class="modal-body">
<div class="error-message" style="display:none;"></div>
<div class='create_form'>
<form id="reportForm">
<?php echo $this->Form->hidden('type', array( 'value' => $type ) ); ?>
<?php echo $this->Form->hidden('target_id', array( 'value' => $target_id ) ); ?>
<ul class="list6 list6sm2" style="position:relative">
	<li>
            <div class='col-md-2'>
                <label><?php echo __('Reason')?></label>
            </div>
            <div class='col-md-10'>
                <?php echo $this->Form->textarea('reason'); ?>
            </div>
            <div class='clear'></div>
	</li>
	<li>
            <div class='col-md-2'>
                <label>&nbsp;</label>
            </div>
            <div class='col-md-10'>
                <a href="javascript:void(0);" class="button" id="reportButton"><?php echo __('Report')?></a>
            </div>
            <div class='clear'></div>
	</li>
</ul>
</form>
</div>
</div>