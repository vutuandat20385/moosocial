<?php
    //echo "<div>$center$east</div>" . $south;
?>

    <?php if (!empty($is_profile_page)): ?>
       
        <?php echo $this->element('user/header_profile'); ?>
    <?php endif; ?>
        <?php if( !$this->isEmpty('east') ): ?>
        <div id="right"  class="sl-rsp-modal col-md-sl2 pull-right">
            <div class="visible-xs visible-sm closeButton">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span> <span class="sr-only">Close</span></button>
            </div>
            <?php echo $east; ?>
        </div>
        <?php endif; ?>
        <div id="center" <?php if( !$this->isEmpty('east')): echo 'class="col-md-sl8"'; 
                               endif; ?>>
        <?php echo $center; ?>
        </div>
    


<div class="clear"></div>
<?php if( !$this->isEmpty('south') ): ?>
<?php echo $south; ?>
 <?php endif; ?>

