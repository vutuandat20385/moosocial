
    <?php if (!empty($is_profile_page)): ?>
        <?php echo $this->element('user/header_profile'); ?>
    <?php endif; ?>
        <?php if( !$this->isEmpty('west') ): ?>
        <div id="leftnav" class="sl-rsp-modal col-md-3" data-keyboard="false" data-backdrop="static">
            <div class="visible-xs visible-sm closeButton">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
            </div>
            <?php echo $west; ?>
        </div>
        <?php endif; ?>

        <?php if( !$this->isEmpty('east') ): ?>
        <div id="right"  class="sl-rsp-modal col-md-3 pull-right" data-keyboard="false" data-backdrop="static">
            <div class="visible-xs visible-sm closeButton">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span> <span class="sr-only">Close</span></button>
            </div>
            <?php echo $east; ?>
        </div>
        <?php endif; ?> 

        <div id="center" <?php if( !$this->isEmpty('east') &&  !$this->isEmpty('west') ): echo 'class="col-md-6"'; 
                               elseif (($this->isEmpty('east') && !$this->isEmpty('west')) || (!$this->isEmpty('east') && $this->isEmpty('west'))): echo 'class="col-md-9"';
                               endif; ?>>
            
        <?php echo $center; ?>
        </div>
        <div class="clear"></div>
    
  

