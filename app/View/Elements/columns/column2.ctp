
    <?php if (!empty($is_profile_page)): ?>
       <?php echo $this->element('user/header_profile'); ?>
    <?php endif; ?>
        <?php if( !$this->isEmpty('west') ): ?>
            <div id="leftnav" class="sl-rsp-modal col-md-3">
                <div class="visible-xs visible-sm closeButton">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span> <span class="sr-only">Close</span></button>
                </div>
                <?php echo $west; ?>
            </div>
        <?php endif; ?>
        <div id="center" <?php if( !$this->isEmpty('west') ): echo 'class="col-md-9"'; 
                               endif; ?>>
        <?php echo $center; ?>
        </div>
    

