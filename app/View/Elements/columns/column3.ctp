

    <?php if (!empty($is_profile_page)): ?>
       
        <!--Add cover here-->
         <?php echo $this->element('user/header_profile'); ?>
        
       
     <?php endif; ?>
        <?php if( !$this->isEmpty('east') ): ?>
        <div id="right"  class="sl-rsp-modal col-md-3 pull-right">
            <div class="visible-xs visible-sm closeButton">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span> <span class="sr-only">Close</span></button>
            </div>
            <?php echo $east; ?>
        </div>
        <?php endif; ?>
        <div id="center" <?php if( !$this->isEmpty('east')): echo 'class="col-md-9"'; 
                               endif; ?>>
        <?php echo $center; ?>
        </div>
   


