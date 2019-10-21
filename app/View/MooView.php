<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class MooView extends View
{
    private $_currentStyle = 0;
    private $isNorthEmpty =  true,$isWestEmpty = true,$isCenterEmpty =true,$isEastEmpty =true,$isSouthEmpty =true ;
    private $_currentStyleIsSet = false;
	private $_pageId = '';
    public $ngController = false;
    public $components = array('RequestHandler');
    public $manualLoadingLayout = false;
    public $location = '';
    public $here = false;
    public $isLandingPage = null;
    public $isEnableRequirejs = true;
    public $phraseJs = array();
    public $initJs   = array();
    public $fileJs = array();
    private $isAllowed = true;
    public $sizeLimit = 0;
    public $videoMaxUpload = 0;
    public $isMobile = false;
    public $photoExt = array();
    public $videoExt = array();
    public $attachmentExt = array();

    public function isLandingPage(){
        if(!is_null($this->isLandingPage)) return $this->isLandingPage;
        if($this->currentUri()=='home.index' && !MooCore::getInstance()->getViewer(true)){
            $this->isLandingPage = true;
        }else{
            return $this->isLandingPage = false;
        }
        return $this->isLandingPage;
    }
    public function isEmpty($region='center'){
        switch($region){
            case 'north':
                return $this->isNorthEmpty;
                break;
            case 'west':
                return $this->isWestEmpty;
                break;
            case 'center':
                return $this->isCenterEmpty;
                break;
            case 'east':
                return $this->isEastEmpty;
                break;
            case 'south':
                return $this->isSouthEmpty;
                break;
            default :
                return true;
        }
    }
    //Todo :
    public function isActive($region='center'){
        switch($this->_currentStyle){
            case 1:
                if($region == 'north' || $region == 'south'){
                    return false;
                }else{
                    return true;
                }
                break;
            case 2:
                if($region == 'north' || $region == 'south' || $region == 'east'){
                    return false;
                }else{
                    return true;
                }
                break;
            case 3:
                if($region == 'north' || $region == 'south' || $region == 'west'){
                    return false;
                }else{
                    return true;
                }
                break;
            case 4:
                if($region == 'center'){
                    return true;
                }else{
                    return false;
                }
                break;
            case 5:
                if($region == 'south'){
                    return false;
                }else{
                    return true;
                }
                break;
            case 6:
                if($region == 'south' || $region == 'east'){
                    return false;
                }else{
                    return true;
                }
                break;
            case 7:
                if($region == 'south' || $region == 'west'){
                    return false;
                }else{
                    return true;
                }
                break;
            case 8:
                if($region == 'north' || $region == 'center'){
                    return true;
                }else{
                    return false;
                }
                break;
            case 9:
                if($region == 'north'){
                    return false;
                }else{
                    return true;
                }
                break;
            case 10:
                if($region == 'north'  ||$region == 'east'){
                    return false;
                }else{
                    return true;
                }
                break;

            case 11:
                if($region == 'north' || $region == 'west'){
                    return false;
                }else{
                    return true;
                }
                break;
            case 12:
                if($region == 'center' || $region == 'south'){
                    return true;
                }else{
                    return false;
                }
                break;
            default: return true;        }
        //return true;
    }
    public function setCurrentStyle($style=0){
        $this->_currentStyle = $style ;
        $this->_currentStyleIsSet = true;
    }
    public function setEmpty($region='center'){
        switch($region){
            case 'north':
                $this->isNorthEmpty = true;
                break;
            case 'west':
                $this->isWestEmpty= true;
                break;
            case 'center':
                $this->isCenterEmpty= true;
                break;
            case 'east':
                $this->isEastEmpty= true;
                break;
            case 'south':
                $this->isSouthEmpty= true;
                break;
            default :
                return true;
        }
    }
    public function setNotEmpty($region='center',$value=false){

        switch($region){
            case 'north':
                $this->isNorthEmpty = false;
                break;
            case 'west':
                $this->isWestEmpty= false;
                break;
            case 'center':
                $this->isCenterEmpty= false;
                break;
            case 'east':
                $this->isEastEmpty= false;
                break;
            case 'south':
                $this->isSouthEmpty= false;
                break;
            default :
                return true;
        }
    }
    public  function _helpingLoadingBlocks($rows,$parent_id,$invisiblecontent=null,$tabs = null){
        if (empty($rows)){
            return false;
        }
        $i = 0;//used as flag to find out if this is the first element in tab array or not
        
        // this is flag render content from code *.ctp (invisiblecontent)
        $renderInvisibleContent = false;

        foreach($rows as $row){
            if(!empty($tabs) && $row['parent_id'] == $parent_id)
            {
                $attr = '';
                if($i == 0)
                    $attr = 'in active';
                echo '<div role="tabpanel" class="tab-pane fade '.$attr.'" id="'.$row["id"].'">';
                $i++;
            }
            if($row['parent_id'] == $parent_id && $row['type'] == 'widget'){
                if($row['name']!='invisiblecontent'){
                    if ($this->request->is('androidApp') || $this->request->is('iosApp')) {
                        continue;
                    }
                    if ($row['role_access'] && trim($row['role_access']) != 'all')
                    {
                        $role_access = explode(',',$row['role_access']);
                        if (!in_array($this->viewVars['role_id'],$role_access))
                            continue;

                    }

                    $element = str_replace('.',DS,$row['name']);
                    $params = json_decode($row['params'],true);
                    $translated_title = '';
                    foreach ($row['nameTranslation'] as $translation) :
                        if ($translation['locale'] == Configure::read('Config.language')) :
                            $translated_title = $translation['content'];
                            break;
                        endif;
                    endforeach;
                    $params['title'] = $translated_title;
                    $row['params'] = json_encode($params);
                    $data_content = isset($this->viewVars['data_block'.$row['id']]) ? $this->viewVars['data_block'.$row['id']] : array();
                    if ($row['plugin']){
						$plugins_active = MooCore::getInstance()->getListPluginEnable();
                    	if (!in_array($row['plugin'],$plugins_active))
			        	{
			        		continue;
			        	}
                        echo $this->widget($element,
                            array_merge($data_content,json_decode($row['params'],true)),
                            array('plugin' => $row['plugin'])
                        )
                        ;
                    }else {
                        $elParams = array_merge($data_content,json_decode($row['params'],true));
                        if($row['name'] == 'tabsWidget')
                        {
                            $elParams['tabId'] = $row['id'];
                            $elParams['widgets'] = $rows;
                            $elParams['invisible'] = null;
                        }
                        echo $this->widget($element,
                            $elParams
                        // ToDo : Caching Improvement
                        );
                    }
                }
                else{
                    // set flag to true, and we dont need to render invisiblecontent in next step
                    $renderInvisibleContent = true;
                    echo $invisiblecontent;
                }
            }
            if(!empty($tabs) && $row['parent_id'] == $parent_id)
               echo "</div>";
        }
        
        // if invisiblecontent not null and not render yet => we render it here
        if (!empty($invisiblecontent) && !$renderInvisibleContent){
            echo $invisiblecontent; 
        }
    }
    public function initStyle(){
        $row = $this->get('mooPage');
        if(empty($row)) return;
        if(!$this->_currentStyleIsSet)
            $this->_currentStyle = (int)$row['Page']['layout'];
    }
    public function currentUri()
    {
        if($this->here) return $this->here;
        $uri = empty($this->params['plugin']) ? "" : $this->params['plugin'].".";
        $uri .= empty($this->params['controller']) ? "" : $this->params['controller'];
        $uri .= empty($this->params['action']) ? "" : "." . $this->params['action'];

        if ($uri == 'pages.display') {
            $uri .= empty($this->params['pass'][0]) ? "" : "." . $this->params['pass'][0];
        }
        $this->here= $uri;
        return $uri;
    }
	 public function getPageId()
    {
        if(!empty($this->_pageId)){
            return $this->_pageId;
        }else{
            $uri = empty($this->params['controller']) ? "" : $this->params['controller'];
            $uri .= empty($this->params['action']) ? "" : "-" . $this->params['action'];

            if ($uri == 'pages.display') {
                $uri .= empty($this->params['pass'][0]) ? "" : "-" . $this->params['pass'][0];
            }
            if (!empty($this->viewVars['uid'])){
                $this->_pageId = 'page_' . $uri;
            }else{
                $this->_pageId = 'page_guest_' . $uri;
            }
            
            return $this->_pageId;
        }
       
    }
    ////**Working here**////
    public function setCurrentUri(){
        //$uri = explode('.',$uri);
        $this->params['controller'] = 'landing';
        $this->params['action'] = 'index';
        //return $this->params['action'];
    }
    ////****////
    public function isContinue()
    {

        return $this->_isAllowed();
    }
    public function isBlocksEmtpy(){
        $config = $this->get('mooPage');
        if(empty($config) &&
            $this->isNorthEmpty  &&
            $this->isWestEmpty &&
            $this->isCenterEmpty &&
            $this->isEastEmpty &&
            $this->isSouthEmpty
        ) return  true;
        return false;
    }
    public function setContentForRegions(){

        $row = $this->get('mooPage'); 
        if(empty($row)){
            // Fix on ajax empty
            $this->start('center');
            echo $this->Blocks->get('content');
            $this->end();
            return;
        }
        $northId = $southId = $westId = $eastId = $centerId = 0;
        $iCenter = $this->Blocks->get('content');
        $iNorth  = $this->fetch('north');
        $iSouth  = $this->fetch('south');
        $iWest   = $this->fetch('west');
        $iEast   = $this->fetch('east');
        // Reset
        $this->Blocks->set('content','');
        $this->Blocks->set('north','');
        $this->Blocks->set('south','');
        $this->Blocks->set('west','');
        $this->Blocks->set('east','');
        if(!empty($row['CoreContent'])){
            foreach($row['CoreContent'] as $content){
                if($content['type'] == 'container'){
                    // Help check region is empty
                    if(!empty($content['core_content_count']) && (int)$content['core_content_count'] > 0){
                        $regionIsEmpty = false;
                    }else{
                        $regionIsEmpty = true;
                    }

                    switch($content['name']){
                        case 'north':
                            $northId = $content['id'];
                            if($this->isNorthEmpty)
                                $this->isNorthEmpty = $regionIsEmpty;
                            break;
                        case 'south':
                            $southId= $content['id'];
                            if($this->isSouthEmpty)
                                $this->isSouthEmpty = $regionIsEmpty;
                            break;
                        case 'west':
                            $westId= $content['id'];
                            if($this->isWestEmpty)
                                $this->isWestEmpty = $regionIsEmpty;
                            break;
                        case 'east':
                            $eastId= $content['id'];
                            if($this->isEastEmpty)
                                $this->isEastEmpty = $regionIsEmpty;
                            break;
                        case 'center':
                            $centerId = $content['id'];
                            if($this->isCenterEmpty)
                                $this->isCenterEmpty = $regionIsEmpty;
                            break;
                    }
                }

            }
        }

        // NORTH region
        $this->assign('north','');
        $original_north = $this->element("region/north",array(
            'widgets'=>null,
            'northId'=>null,
            'invisible'=>null,
        ));
        $this->start('north');
        echo $this->element("region/north",array(
            'widgets'=>$row['CoreContent'],
            'northId'=>$northId,
            'invisible'=>$iNorth,
        ));
        $this->end();
        if (trim(strip_tags($original_north)) == trim(strip_tags($this->fetch('north')))){
            $this->setEmpty('north');
        }

        // SOUTH region
        $this->assign('south','');
        $original_south = $this->element("region/south",array(
            'widgets'=>null,
            'southId'=>null,
            'invisible'=>null,
        ));
        $this->start('south');
        echo $this->element("region/south",array(
            'widgets'=>$row['CoreContent'],
            'southId'=>$southId,
            'invisible'=>$iSouth,
        ));
        $this->end();
        if (trim(strip_tags($original_south)) == trim(strip_tags($this->fetch('south')))){
            $this->setEmpty('south');
        }
        
        // WEST region
        $this->assign('west','');
        $original_west = $this->element("region/west",array(
            'widgets'=>null,
            'westId'=>null,
            'invisible'=>null,
        ));
        $this->start('west');
        echo $this->element("region/west",array(
            'widgets'=>$row['CoreContent'],
            'westId'=>$westId,
            'invisible'=>$iWest,
        ));
        $this->end();
        if (trim(strip_tags($original_west)) == trim(strip_tags($this->fetch('west')))){
            $this->setEmpty('west');
        }

        // EAST region
        $this->assign('east','');
        $original_east = $this->element("region/east",array(
            'widgets'=>null,
            'eastId'=>null,
            'invisible'=>null,
        ));
        $this->start('east');
        echo $this->element("region/east",array(
            'widgets'=>$row['CoreContent'],
            'eastId'=>$eastId,
            'invisible'=>$iEast,
        ));
        $this->end();
        if (trim(strip_tags($original_east)) == trim(strip_tags($this->fetch('east')))){
            $this->setEmpty('east');
        }
        
        // CENTER region
        $this->assign('center','');
        $original_center = $this->element("region/center",array(
            'widgets'=>null,
            'centerId'=>null,
            'invisible'=>null,
        ));
        $this->start('center');
        echo $this->element("region/center",array(
            'widgets'=>$row['CoreContent'],
            'centerId'=>$centerId,
            'invisible'=>$iCenter,
        ));
        $this->end();
        if (trim(strip_tags($original_center)) == trim(strip_tags($this->fetch('center')))){
            $this->setEmpty('center');
        }
    }


    public function doLoadingHeader(){
        $row = $this->get('mooHeader');
        if(empty($row) || empty($row[0]['Children'])) return;
        $headerId = $footerId = 0;
        if((!empty($row[0]['CoreContent']))){
            $headerId = $row[0]['CoreContent']['id'];
        }
        
        $row[0]['Children'] = Hash::sort($row[0]['Children'],'{n}.order','asc');
        foreach($row[0]['Children'] as $value){
            if($value['parent_id'] == $headerId && $value['type'] == 'widget'){

                $element = str_replace('.',DS,$value['name']);
                $params = json_decode($value['params'],true);
                $translated_title = '';
                foreach ($value['nameTranslation'] as $translation) :
                    if ($translation['locale'] == Configure::read('Config.language')) :
                        $translated_title = $translation['content'];
                        break;
                    endif;
                endforeach;
                $params['title'] = $translated_title;
                //$block = $allBlocks[$value['core_block_id']];
            	$data_content = isset($this->viewVars['data_block'.$value['id']]) ? $this->viewVars['data_block'.$value['id']] : array();
                if ($value['plugin']){
                	$plugins_active = MooCore::getInstance()->getListPluginEnable();
                    if (!in_array($value['plugin'],$plugins_active))
		        	{
		        		continue;
		        	}
                    echo $this->widget($element,
                        array_merge($data_content,$params),
                        array('plugin' => $value['plugin'])
                    )
                    ;
                }else{
                    echo $this->widget($element,array_merge($data_content,$params));
                }
            }
        }
    }
    public function doLoadingFooter(){
        $row = $this->get('mooFooter');
        if(empty($row) || empty($row[0]['Children'])) return;
        $headerId = $footerId = 0;
        if((!empty($row[0]['CoreContent']))){
            $headerId = $row[0]['CoreContent']['id'];
        }
        
        $row[0]['Children'] = Hash::sort($row[0]['Children'],'{n}.order','asc');
        foreach($row[0]['Children'] as $value){
            if($value['parent_id'] == $headerId && $value['type'] == 'widget'){

            	$element = str_replace('.',DS,$value['name']);
                $params = json_decode($value['params'],true);
                $translated_title = '';
                if (isset($value['nameTranslation']))
                {
	                foreach ($value['nameTranslation'] as $translation) :
	                    if ($translation['locale'] == Configure::read('Config.language')) :
	                        $translated_title = $translation['content'];
	                        break;
	                    endif;
	                endforeach;
                }
                $params['title'] = $translated_title;

            	$data_content = isset($this->viewVars['data_block'.$value['id']]) ? $this->viewVars['data_block'.$value['id']] : array();
                if ($value['plugin']){
                	$plugins_active = MooCore::getInstance()->getListPluginEnable();
                    if (!in_array($value['plugin'],$plugins_active))
		        	{
		        		continue;
		        	}
                    echo $this->widget($element,
                        array_merge($data_content,$params),
                        array('plugin' => $value['plugin'])
                    )
                    ;
                }else{
                    echo $this->widget($element,array_merge($data_content,$params));
                }
            }
        }
    }

    public function currentStyle()
    {
        return $this->_currentStyle;
    }

    public function __construct(Controller $controller = null)
    {
        parent::__construct($controller);
        
        $this->sizeLimit = MooCore::getInstance()->_getMaxFileSize();
        $this->videoMaxUpload = MooCore::getInstance()->_getMaxVideoUpload();
        $this->isMobile = MooCore::getInstance()->isMobile(null);
        
        $this->photoExt = MooCore::getInstance()->_getPhotoAllowedExtension();
        $this->videoExt = MooCore::getInstance()->_getVideoAllowedExtension();
        $this->attachmentExt = MooCore::getInstance()->_getFileAllowedExtension();
        
        if(
            !$this->request->is('ajax') &&
            !$this->request->is('requested') &&
            //!$this->request->is('post') &&
            !$this->request->is('xml') &&
            ($this->theme != 'adm')
        ){
            //$this->loadLibarary('mooCore');

            if($this->isLandingPage()){
               $this->loadingManualLayout();
            }
            $this->prepend('header');
            echo $this->element('region/header');
            $this->end();
            $this->prepend('footer');
            echo $this->element('region/footer');
            $this->end();
        }
    }

    public function addPhraseJs($name, $value=null){
        if (!is_array($name)){
            $name = array($name => $value);
        }
        $this->phraseJs = (array_merge($this->phraseJs,$name));
    }

    public function addInitJs($jsfunction){
        if (!is_array($jsfunction)){
            $this->initJs[] =$jsfunction;
            return;
        }

        $this->initJs = array_unique(array_merge($this->initJs,$jsfunction));
    }
    public function loadJs($js){
        if (!is_array($js)){
            $this->fileJs[] =$js;
            return;
        }
        $this->fileJs = array_unique(array_merge($this->fileJs,$js));
    }
    public function requireJs($script=array(),$initJs=array()){
        $this->addInitJs($initJs);
        $this->loadJs($script);
    }
    public function loadLibarary($libs=array()){
        $this->loadLibrary($libs);

    }
    public function loadLibrary($libs=array()){

        if (empty($libs)){
            return;
        }
        if(!is_array($libs)){
            $libs = array($libs);
        }
        $this->getEventManager()->dispatch(new CakeEvent('mooView.loadLibrary', $this, array('libs' => $libs)));
    }
    private  function _isAllowed(){
        if ($this->theme == 'adm') return false;
        if($this->request->is('ajax')) return false;
        if($this->request->is('requested')) return false;
        //if($this->request->is('post')) return false;
        if($this->request->is('xml')) return false;

        // Checking uri in table core_page , if not return false
        return $this->isAllowed;
    }
    public function setIsAllowed($state){
        $this->isAllowed = $state;
    }

    // Rendering header region
    public function renderHeader(){}
    // Rendering footer region
    public function renderFooter(){}
    // Rendering content region
    public function renderContent(){
        $this->getEventManager()->dispatch(new CakeEvent('MooView.BeforeRenderContent', $this));
        if(!$this->_isAllowed()) return false;

        $config = $this->get('mooPage');


        //if(empty($config)) return false;
        if($this->isBlocksEmtpy())
        {
            if ($this->request->is('post'))
            return false;
        }
        $this->setContentStyle(isset($config['Page']['layout'])?(int)$config['Page']['layout']:null);
        $this->setContentForRegions();
        $style = $this->currentStyle();
        $content = $this->element("columns/column$style", array(
            'north' => $this->fetch('north'),
            'south' => $this->fetch('south'),
            'center' => $this->fetch('center'),
            'west' => $this->fetch('west'),
            'east' => $this->fetch('east'),
        ));

        if($this->manualLoadingLayout){
            //$viewLayout = $this->geCustomLayout(); // "/home/landing"
            $uri = $this->currentUri();
            $aUrl = explode('.',$uri);
            $url = array();
            if(count($aUrl) >= 3)
            {
                list($plugin,$controller,$action) = $aUrl;
            }
            else{
                list($controller,$action,$plugin) = array_pad($aUrl,3,'');
            }

            if(!empty($this->location))
            {
                $pPlugin = explode('.',$this->location);
                if(count($pPlugin) >1)
                {
                    if(!empty($pPlugin) && $pPlugin[0] != $plugin)
                        $plugin = $pPlugin[0];
                    $pControllerAction = $pPlugin[1];
                }
                else
                    $pControllerAction = $pPlugin[0];

                $location = explode('_',$pControllerAction);
                if(count($location) > 1)
                {
                    if( $controller != $location[0] )
                        $controller = $location[0];
                    if($action != $location[1]);
                        $action = $location[1];
                }
                else
                    $action = $location[0];
            }
            if(!empty($plugin))
                $plugin = ucfirst($plugin) . DS;
            if($this->isLandingPage()){
                $currentTheme = $this->theme;
                if ($currentTheme == 'default'){
                    $viewFile = APP . $plugin . 'View' . DS . ucfirst($controller) . DS . 'landing.ctp';
                }else{
                    if (file_exists(APP . $plugin . 'View' . DS . 'Themed' . DS . ucfirst($currentTheme) . DS . ucfirst($controller) . DS . 'landing.ctp')){
                        $viewFile = APP . $plugin . 'View' . DS . 'Themed' . DS . ucfirst($currentTheme) . DS . ucfirst($controller) . DS . 'landing.ctp';
                    }else {
                        $viewFile = APP . $plugin . 'View' . DS . ucfirst($controller) . DS . 'landing.ctp';
                    }
                    
                }
            }else{
                $viewFile = APP . $plugin . 'View' . DS . ucfirst($controller) . DS . $action.'.ctp';
            }


            $content = $this->_evaluate($viewFile,array_merge(array('content'=>$content),$this->viewVars));

            $this->Blocks->set('content', $content);
        }

        $this->Blocks->set('content', $content);
        $this->getEventManager()->dispatch(new CakeEvent('MooView.AfterRenderContent', $this));
    }
    private function _isAllowedSetContentStyle(){
        if(!$this->_currentStyleIsSet){ return true;}else{ return false;}
    }
    public function setContentStyle($style = null){
        if (empty($style)) return false;
        if( $this->_isAllowedSetContentStyle()){
            $this->_currentStyle =  $style;
        }
        return true;
    }
    public function setNgController(){

        //$ng = empty($this->params['plugin']) ? "" : ucfirst($this->params['controller']).".";
        $ng = empty($this->params['controller']) ? "" : ucfirst($this->params['controller']);
        $ng .= empty($this->params['action']) ? "" :  ucfirst($this->params['action']);
        $this->ngController = $ng."Controller";

    }
    public function getNgController(){
        if($this->ngController){
            echo "ng-controller=\"".$this->ngController."\"";
        }

    }
    public function render($view = null, $layout = null) {
        
        $this->getEventManager()->dispatch(new CakeEvent('MooView.beforeRender', $this));
        return parent::render($view,$layout);
    }
    
    public function renderFile($file,$data = array())
    {
    	$viewFileName = $this->_getViewFileName($file);
    	return $this->_render($viewFileName, array_merge($this->viewVars, $data));
    }
    
    public function renderActivityFeed()
    {   //deprecated
        return;
    }
    
    public function renderLike($options = array())
    {
    	$subject = MooCore::getInstance()->getSubject(); 
    	$likeModel =  MooCore::getInstance()->getModel('Like');
    	$uid = $this->Auth->user('id');
    	
    	if (!$subject) 
    		return false;
    	$key = key($subject);
    	
    	$likes = $likeModel->getLikes($subject[$key]['id'], $subject[$key]['moo_type']);
    	$dislikes = $likeModel->getDisLikes($subject[$key]['id'], $subject[$key]['moo_type']);
    	$like = null;
    	if ($uid)
    	{
    		$like = $likeModel->getUserLike($subject[$key]['id'], $uid, $subject[$key]['moo_type']);
    	}
    	
    	return $this->element('likes', $options + array('dislikes'=>$dislikes,'like'=>$like,'likes'=>$likes,'item' => $subject[$key], 'type' => $subject[$key]['moo_type']));
    }

    public function renderComment($params = array())
    {
        $subject = MooCore::getInstance()->getSubject();
        $likeModel =  MooCore::getInstance()->getModel('Like');
        $uid = $this->Auth->user('id');

        if (!$subject)
            return false;

        $key = key($subject);

        list($plugin, $name) = mooPluginSplit($subject[$key]['moo_type']);
        $helper = MooCore::getInstance()->getHelper($plugin . '_' . $plugin);
        $check_post_status = $helper->checkPostStatus($subject,$uid);
        $check_see_status = $helper->checkSeeComment($subject,$uid);
        if (!$check_see_status)
        {
            return false;
        }

        $commentModel =  MooCore::getInstance()->getModel('Comment');

        if(!empty( $this->request->named['comment_id'] )){
            $comment_id = $this->request->named['comment_id'];
        }elseif(!empty($this->request->query('comment_id'))){
            $comment_id = $this->request->query('comment_id');
        }
        if(!empty( $this->request->named['reply_id'] )){
            $reply_id = $this->request->named['reply_id'];
        }elseif(!empty($this->request->query('reply_id'))){
            $reply_id = $this->request->query('reply_id');
        }

        if (!empty( $comment_id )) {
            $data['cmt_id'] = $comment_id;
            $comments[0] = $commentModel->find('first', array(
                'conditions' => array(
                    'Comment.id' => $comment_id,
                    'Comment.type' => $subject[$key]['moo_type'],
                    'Comment.target_id' => $subject[$key]['id'],
                )
            ));
        }

        if(empty($comments[0])) {
            $comments = $commentModel->getComments($subject[$key]['id'], $subject[$key]['moo_type']);
        }elseif(!empty( $reply_id )){
            $reply = $commentModel->find('all', array(
                'conditions' => array(
                    'Comment.id' => $reply_id,
                )
            ));
            $replies_count = $commentModel->getCommentsCount( $comments[0]['Comment']['id'], 'comment' );
            $comment_likes = $likeModel->getCommentLikes( $reply, $uid );

            $comments[0]['Replies'] = $reply;
            $comments[0]['RepliesIsLoadMore'] = ($replies_count - 1) > 0 ? true : false;
            $comments[0]['RepliesCommentLikes'] = $comment_likes;
        }
        $comment_likes = array();
        if ($uid)
        {
            $comment_likes = $likeModel->getCommentLikes($comments,$uid);
        }
        $data['subject'] = $subject;
        $data['comments'] = $comments ;
        $data['comment_likes'] = $comment_likes;
        if(empty($subject[$key]['comment_count'])){
            if(!empty($params['comment_count']))
                $subject[$key]['comment_count'] = $params['comment_count'];
            else
                $subject[$key]['comment_count'] = 0;
        }
        $data['bIsCommentloadMore'] = $subject[$key]['comment_count'] - RESULTS_LIMIT ;
        $data['more_comments'] = '/comments/browse/'.$subject[$key]['moo_type'].'/' . $subject[$key]['id'] . '/page:2' ;

        //close comment
        $closeCommentModel =  MooCore::getInstance()->getModel('CloseComment');
        $item_close_comment = $closeCommentModel->getCloseComment($subject[$key]['id'], $subject[$key]['moo_type']);

        return $this->element('comments_widget',array_merge($params,array('check_see_status'=>$check_see_status,'check_post_status'=>$check_post_status,'subject'=>$subject,'data'=>$data,'params'=>$params, 'item_close_comment' => $item_close_comment)));
    }
    
    public function viewMore($string, $moreLength = null, $maxLength = null,  $lessLength = null, $nl2br = true, $options = array())
  	{
	  	if( !is_numeric($moreLength) || $moreLength <= 0 ) {
			$moreLength = 500;
	    }
	    if( !is_numeric($maxLength) || $maxLength <= 0 ) {
			$maxLength = 1027;
	    }
	    if( !is_numeric($lessLength) || $lessLength <= 0 ) {
			$lessLength = 511;
	    }
        //$string = preg_replace('/<script\b[^>]*>(.*?)<\/script>/i', "", $string);

	    $string = preg_replace('/(\r?\n){2,}/', "\n\n", $string);
	    $string = preg_replace_callback(REGEX_MENTION,array($this->Text,'mentionLink'),$string);
	    $string = $this->Moo->parseSmilies($string);
        $string = $this->Text->autoLink($string, array_merge(array('target' => '_blank', 'rel' => 'nofollow', 'escape' => false),$options));
        
        
        $shortText = $this->Text->truncate($string,$moreLength,array(
        	'ellipsis' => '',
        	'html' => true
        ));
	    $fullText = $string;
            // MOOSOCIAL-1588
            if ($shortText == $fullText){
                // limit to 10 lines
                $limit_lines = 10;
                if (count(explode("\n", $fullText)) > $limit_lines){
                    $shortText = '';
                    $arr = array_slice(explode("\n", $fullText), 0, 10);
                    foreach ($arr as $line){
                        $shortText .= $line;
                    }
                }
            }
	    
            if (strlen($fullText) <= strlen($shortText))
	    	return nl2br($fullText);
	    // Do nl2br
	    if( $nl2br ) {
	      $shortText = nl2br($shortText);
	      $fullText = nl2br($fullText);
	    }
	    
	    $tag = 'span';
	    $strLen = strlen($string);
	
	    $content = '<'
	      . $tag
	      . ' class="view_more"'
	      . '>'
	      . $shortText
	      . __('... &nbsp;')
	      . '<a class="view_more_link" href="javascript:void(0);" onclick="$(this).parent().next().show();$(this).parent().hide();">'.__('more').'</a>'
	      . '</'
	      . $tag
	      . '>'
	      . '<'
	      . $tag
	      . ' class="view_more"'
	      . ' style="display:none;"'
	      . '>'
	      . $fullText
	      . ' &nbsp;'
	      ;
	    $content .= '</'
	      . $tag
	      . '>'
	      ;

	    return $content;
  	}
  	
    public function loadingManualLayout($isActive = true,$location = ''){
        $this->manualLoadingLayout = $isActive;
        $this->location = $location;
    }
    public function widget($name, $data = array(), $options = array()) {
        $file = $plugin = null;

        if (isset($options['plugin'])) {
            $name = Inflector::camelize($options['plugin']) . '.' . $name;
        }

        if (!isset($options['callbacks'])) {
            $options['callbacks'] = false;
        }

        if (isset($options['cache'])) {
            $contents = $this->_elementCache($name, $data, $options);
            if ($contents !== false) {
                return $contents;
            }
        }

        $file = $this->_getWidgetFileName($name);
        if ($file) {
            return $this->_renderElement($file, $data, $options);
        }

        if (empty($options['ignoreMissing'])) {
            list ($plugin, $name) = pluginSplit($name, true);
            $name = str_replace('/', DS, $name);
            $file = $plugin . 'Widgets' . DS . $name . $this->ext;
            trigger_error(__d('cake_dev', 'Element Not Found: %s', $file), E_USER_NOTICE);
        }
    }

    protected function _getWidgetFileName($name) {
        list($plugin, $name) = $this->pluginSplit($name);

        $paths = $this->_paths($plugin);
        $exts = $this->_getExtensions();
        foreach ($exts as $ext) {
            foreach ($paths as $path) {
                if (file_exists($path . 'Widgets' . DS . $name . $ext)) {
                    return $path . 'Widgets' . DS . $name . $ext;
                }
            }
        }
        return false;
    }
    public function isEnableJS($name){
        $isEnableRequirejs = false;
        
        switch($name){
            case 'Requirejs':
                $isEnableRequirejs = $this->isEnableRequirejs;
                break;
            default:
                $isEnableRequirejs = false;
        }
        
        // do not enable requirejs in admincp
        if ($this->theme == 'adm'){
            $isEnableRequirejs = false;
        }
        
        return $isEnableRequirejs;
    }
    public function fetch($name, $default = '',$return=false) {

        switch($name){
        	case 'config':
        		$profile_popup = Configure::read('core.profile_popup');
        		if(!MooCore::getInstance()->getViewer(true) && Configure::read('core.force_login') && !Configure::read('core.user_consider_force')){
        			$profile_popup = 2;
        		}
        		$mooConfig = array(
        				'url'=>array(
        						'base'=>$this->request->base,
        						'webroot'=>$this->request->webroot,
        						'full' => FULL_BASE_URL
        				),
        				'language'=> Configure::read('Config.language'),
        				'language_2letter'=>MooLangISOConvert::getInstance()->lang_iso639_2t_to_1(Configure::read('Config.language')),
        				'autoLoadMore'=>Configure::read('core.auto_load_more'),
        				'sizeLimit' => $this->sizeLimit,
        				'videoMaxUpload' => $this->videoMaxUpload,
        				'isMobile' => $this->isMobile,
        				'isMention' => true,
        				'photoExt' => $this->photoExt,
        				'videoExt' => $this->videoExt,
        				'attachmentExt' => $this->attachmentExt,
        				'comment_sort_style' => Configure::read('core.comment_sort_style'),
                        'reply_sort_style' => Configure::read('core.reply_sort_style'),
        				'tinyMCE_language' => $this->tinyMCELanguageCode(Configure::read('Config.language')),
        				'time_format' => Configure::read('Event.time_format'),
        				'profile_popup' => $profile_popup,
        				'rtl' => isset($this->viewVars['site_rtl']) ? $this->viewVars['site_rtl'] : 0,
        				'force_login' => Configure::read('core.force_login'),
						'isApp' =>  ($this->request->is('androidApp') || $this->request->is('iosApp')) ? 1 : 0,
						'appAccessToken' => $this->viewVars['appAccessToken'],
        				'product_mode' => Configure::read('debug')
        				
        		);
                if (!is_null($this->Helpers->Session->read('mooTokens'))) {
                    $mooConfig["token"] = $this->Helpers->Session->read('mooTokens');;
                }
        		// Remove as 2.5.2
        		//$event = new CakeEvent('MooView.beforeMooConfigJSRender', $this,array('mooConfig' => $mooConfig));
        		$this->mooConfig =  $mooConfig;
        		$this->getEventManager()->dispatch(new CakeEvent('MooView.beforeMooConfigJSRender', $this));
        		$mooConfig = $this->mooConfig;
        		/* Remove as 2.5.2
        		 if(!empty($event->result['mooConfig'])){
        		 $mooConfig = $event->result['mooConfig'];
        		 }
        		 */
        		if($return){
        		    return $mooConfig;
                }
        		$this->Helpers->Html->scriptBlock(
        				"var mooConfig = ".json_encode($mooConfig,true).";",
        				array(
        						'inline' => true,
        						'block' => 'config'
        				)
        				);
        		
        		break;
            case 'script':
                if($this->isEnableRequirejs){
                    $this->loadLibarary('requireJS');
                }else{

                }
                if(!empty($this->fileJs)){
                    $this->Helpers->Html->script($this->fileJs,array(
                        'inline' => false,
                        'block' => 'script'
                    ));
                }
                
                
                if(!empty($this->initJs)){
                	if($this->isEnableRequirejs){
                		$init = "var MooSite = function() { var init = function() {".implode(" ", $this->initJs)."}; return {init:init}; }();require(['jquery'],function($){ $(function() {MooSite.init(); });});";
                	}else{
                		$init = "var MooSite = function() { var init = function() {".implode(" ", $this->initJs)."}; return {init:init}; }();$(function() {MooSite.init();});";
                	}
                	$this->Helpers->Html->scriptBlock(
                			$init,
                			array(
                					'inline' => false,
                					'block' => 'script'
                			)
                			);
                	
                }
                
                break;
            default:

        }
        return parent::fetch($name,$default);
    }

    public function getUrlInfo()
    {
        $uri = $this->currentUri();
        $aUrl = explode('.',$uri);
        $url = array();
        if(count($aUrl) >= 3)
        {
            list($plugin,$controller,$action) = $aUrl;
        }
        else{
            list($controller,$action,$plugin) = array_pad($aUrl,3,'');
        }

        return array('plugin' => $plugin, 'controller' => $controller, 'action' => $action);
    }
    
    public function tinyMCELanguageCode($site_lang = null){
        $arr_mapping = array(
            "ara" => "ar",
            "aze" => "az",
            "bel" => "be",
            "bul" => "bg_BG",
            "ben" => "bn_BD",
            "bos" => "bs",
            "cze" => "cs",
            "ces" => "cs_CZ",
            "wel" => "cy",
            "cym" => "cy",
            "dan" => "da",
            "ger" => "de",
            "deu" => "de_AT",
            "div" => "dv",
            "gre" => "el",
            "eng" => "en_CA",
            "epo" => "eo",
            "spa" => "es",
            "est" => "et",
            "baq" => "eu",
            "per" => "fa",
            "fin" => "fi",
            "fao" => "fo",
            "fra" => "fr_FR",
            "gle" => "ga",
            "gla" => "gd",
            "glg" => "gl",
            "heb" => "he_IL",
            "hin" => "hi_IN",
            "hrv" => "hr",
            "hun" => "hu_HU",
            "ind" => "id",
            "ice" => "is_IS",
            "ita" => "it",
            "jpn" => "ja",
            "geo" => "ka_GE",
            "kab" => "kab",
            "kaz" => "kk",
            "khm" => "km_KH",
            "kor" => "ko_KR",
            "kur" => "ku_IQ",
            "ltz" => "lb",
            "lit" => "lt",
            "lav" => "lv",
            "mac" => "mk_MK",
            "mal" => "ml_IN",
            "mon" => "mn_MN",
            "nor" => "nb_NO",
            "nld" => "nl",
            "pol" => "pl",
            "por" => "pt_PT",
            "ron" => "ro",
            "rus" => "ru_RU",
            "sin" => "si_LK",
            "slo" => "sk",
            "slv" => "sl_SI",
            "srp" => "sr",
            "swe" => "sv_SE",
            "tam" => "ta_IN",
            "tgk" => "tg",
            "tha" => "th_TH",
            "tur" => "tr_TR",
            "crh" => "tt",
            "uig" => "ug",
            "ukr" => "uk_UA",
            "vie" => "vi_VN",
            "chi" => "zh_TW",
        );
        
        if (empty($site_lang)){
            return 'en_CA';
        }
        
        $tinymce_lang = isset($arr_mapping[$site_lang]) ? $arr_mapping[$site_lang] : 'en_CA';
        
        return $tinymce_lang;
    }
}


