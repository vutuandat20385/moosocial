<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('Component', 'Controller');
class OAuth2Component extends Component {
    protected $token = null;
    protected $ownerIdResouseRequest = null;
    protected $ownerResouseRequest = null;
    protected $clientIdRequest       = null;
    protected $request = null;
    protected $OauthAccessToken = null;
    protected $OauthRefreshToken = null;
    protected $User = null;
    public function __construct(ComponentCollection $collection, $settings = array()) {
        parent::__construct($collection, $settings);
        $collection->getController()->loadModel('OauthAccessToken');
        $collection->getController()->loadModel('OauthRefreshToken');
        $collection->getController()->loadModel('User');
        $this->request = $collection->getController()->request;
        $this->OauthAccessToken = $collection->getController()->OauthAccessToken;
        $this->OauthRefreshToken= $collection->getController()->OauthRefreshToken;
        $this->User= $collection->getController()->User;
    }

    public function verifyResourceRequest($excludeActions = array()) {
        $token = $this->getAccessTokenData();

        if(in_array($this->_Collection->getController()->action,$excludeActions)){
            return true;
        }
        // Check if we have token data
        if (is_null($token)) {
        	if ($this->_Collection->getController()->isApp() && $this->request->params['plugin'] != 'Api')
        	{
        		$this->_Collection->getController()->theme = 'mooApp';
        		return $this->_Collection->getController()->render('/Elements/token_error');
        		die();
        	}
            throw new BadRequestException("Error parameter : Token is invalid");
            return false;
        }
        $this->token = $token;
        if(time() > $token["expires"]){
            //throw new TokenHasExpiredException(__('The access token provided has expired'));
        	if ($this->_Collection->getController()->isApp() && $this->request->params['plugin'] != 'Api')
        	{
        		$this->_Collection->getController()->layout = 'simple';
        		return $this->_Collection->getController()->render('/Errors/token_has_expired');
        		die();
        	}
			$this->request->data('apiErrorCodeText', 'token_expired');
            throw new ApiUnauthorizedException(__('The access token provided has expired'));
            return false;
        }
        return (bool) $token;
    }
    public function getAccessTokenData() {
        $access_token = null;
        if($this->request->header('moo-access-token') !== false){
            $access_token = $this->request->header('moo-access-token');
        }
        if($this->request->is('post') || $this->request->is('put')){
            if(!empty($this->request->data['access_token'])){
                $access_token =  $this->request->data['access_token'];
            }
        }

        if(is_null($access_token)){
            $access_token = $this->request->query('access_token');
        }

        if(is_null($access_token)) return null;
        if($token = $this->OauthAccessToken->findByAccessToken($access_token)){
            $token['OauthAccessToken']['expires'] = strtotime($token['OauthAccessToken']['expires']);
        }else{
            return null;
        }
        // Automaticaly detecting OwnerIdRewsoudRequest
        $this->setOwnerIdRewsoudRequest($token['OauthAccessToken']['user_id']);
        return $token['OauthAccessToken'];
    }
    protected function getRefreshTokenData() {
        $refresh_token = null;
        if($this->request->is('post')){
            if(!empty($this->request->data['refresh_token'])){
                $refresh_token =  $this->request->data['refresh_token'];
            }
        }
        if(is_null($refresh_token)) return null;
        if($token = $this->OauthRefreshToken->findByRefreshToken($refresh_token)){
            $token['OauthRefreshToken']['expires'] = strtotime($token['OauthRefreshToken']['expires']);
        }else{
            return null;
        }
        // Automaticaly detecting OwnerIdRewsoudRequest
        $this->setOwnerIdRewsoudRequest($token['OauthRefreshToken']['user_id']);
        return $token['OauthRefreshToken'];
    }
    public function setOwnerIdRewsoudRequest($userId) {
        $this->ownerIdResouseRequest = $userId;
    }
    public function getOwnerResourceRequest($idOnly = true) {
        if ($idOnly) {
            return $this->ownerIdResouseRequest;
        }else{
            if (empty($this->ownerResouseRequest)) {
				$user = $this->User->find('first',array(
                    'conditions'=>array('User.id'=>$this->ownerIdResouseRequest),
                    'contain'=>'Role')
                );
                $this->ownerResouseRequest = $user['User'];
                $this->ownerResouseRequest['Role'] = $user['Role'];
            }
            return $this->ownerResouseRequest;
        }
        return false;
    }
    public function token() {

        if (!$this->isRefeshTokenRequest()) {

            if ($this->validateResourceOwnerPasswordCredentials()) {
                /*
                 * http://tools.ietf.org/html/rfc6749#section-5.1
                 * Successful Response
                 *
                 * We are using Bearer token type to make a protected resource request
                 * http://tools.ietf.org/html/rfc6750#page-10
                 */

                $this->sendReponse($this->createToken());

            } else {
                /*
                 * http://tools.ietf.org/html/rfc6749#section-5.2
                 * Error Response
                 */
                 throw new NotFoundException(__('Parameter error : username or password is invalid'));
            }
        } else {
            if ($this->validateRefreshingToken()) {
                $token = $this->getRefreshTokenData();
                if (is_null($token)) {
                    throw new BadRequestException(__('The refresh token provided is invalid'));
                }

                $this->sendReponse($this->createToken());
            }
        }

    }

    /**
     * http://tools.ietf.org/html/rfc6749#section-3.2
     * The client MUST use the HTTP "POST" method when making access token requests.
     *
     *
     * http://tools.ietf.org/html/rfc6749#section-4.3.2
     */
    public function validateTokentRequest() {
        if (!$this->request->is('post')) {
            throw new BadRequestException(__('The client MUST use the HTTP "POST" method when making access token requests.'));
        }

        $data = $this->request->data;
        if (empty($data['grant_type'])) {
            //throw new BadRequestException(__('grant_type is REQUIRED'));
            $data['grant_type'] = "password";
        }

        if (($data['grant_type'] != "password") && ($data['grant_type'] != "refresh_token")) {
            throw new BadRequestException(__('grant_type\'s value MUST be set to "password" or "refresh_token" '));
        }


        return true;
    }

    private function validateResourceOwnerPasswordCredentials() {
        $data = $this->request->data;

        if (empty($data['username'])) {
            throw new BadRequestException(__('Missing parameter : username is REQUIRED'));
        }

        if (empty($data['password'])) {
            throw new BadRequestException(__('Missing parameter : password is REQUIRED'));
        }
      
        // Validate email
        if (filter_var($data['username'], FILTER_VALIDATE_EMAIL) === false) {
            throw new BadRequestException(__('Parameter error : please enter a valid email'));
        }
        // Todo: Verify username and password
        $user = $this->User->findByEmail(trim($data['username']));
    	if ($user)
		{
			if (!$user['User']['active']) {
				throw new BadRequestException(__('This account has been disabled'));
			}
			elseif (!$user['User']['approved']) {
				throw new BadRequestException(__('Your account is pending for approval'));
			}
		}
        
        $this->_Collection->getController()->request->data('User.email',$data['username']);
        $this->_Collection->getController()->request->data('User.password',$data['password']);
        if (!$this->_Collection->getController()->Auth->login()) {
            if($user) {
                throw new UnauthorizedException(__('Parameter error : password is not correct'));
            }
            else {
                return false;
            }
        }
        $user = $this->_Collection->getController()->Auth->user();
        // Automaticaly detecting OwnerIdRewsoudRequest
        $this->setOwnerIdRewsoudRequest($user['id']);
        return true;
    }

    private function validateRefreshingToken() {
        $data = $this->request->data;
        if (empty($data['refresh_token'])) {
            throw new BadRequestException('Missing parameter : refresh_token is REQUIRED');
        }
        return true;
    }

    private function generateToken($type = null) {
        if ($type == "refresh") {

        }
        if (function_exists('mcrypt_create_iv')) {
            $randomData = mcrypt_create_iv(20, MCRYPT_DEV_URANDOM);
            if ($randomData !== false && strlen($randomData) === 20) {
                return bin2hex($randomData);
            }
        }
        if (function_exists('openssl_random_pseudo_bytes')) {
            $randomData = openssl_random_pseudo_bytes(20);
            if ($randomData !== false && strlen($randomData) === 20) {
                return bin2hex($randomData);
            }
        }
        if (@file_exists('/dev/urandom')) { // Get 100 bytes of random data
            $randomData = file_get_contents('/dev/urandom', false, null, 0, 20);
            if ($randomData !== false && strlen($randomData) === 20) {
                return bin2hex($randomData);
            }
        }
        // Last resort which you probably should just get rid of:
        $randomData = mt_rand() . mt_rand() . mt_rand() . mt_rand() . microtime(true) . uniqid(mt_rand(), true);
        return substr(hash('sha512', $randomData), 0, 40);
    }

    private function isRefeshTokenRequest() {
        if ($this->validateTokentRequest()) {

            if (isset($this->request->data['grant_type']) && $this->request->data['grant_type'] == "refresh_token") {
                return true;
            }
        }
        return false;
    }

    public function createToken() {
        $config = array(
            'token_type' => 'bearer',
        	'access_lifetime' => AUTH2_ACCESS_LIFETIME,
        	'refresh_token_lifetime' => AUTH2_REFRESH_TOKEN_LIFETIME,
        );

        $token = array(
            'access_token' => $this->generateToken(),
            'token_type' => $config['token_type'],
            'expires_in' => $config['access_lifetime'],
            'refresh_token' => $this->generateToken("refresh"),
            'scope' => null,
        );

        $expires = date('Y-m-d H:i:s', time() + $config['access_lifetime']);
        $accessTokenSaved = $this->OauthAccessToken->save(array('OauthAccessToken' => array(
            'client_id' => null,
            'expires' => $expires,
            'user_id' => $this->ownerIdResouseRequest,
            'scope' => null,
            'access_token' => $token["access_token"],
        )));
        $expires = date('Y-m-d H:i:s', time() + $config['refresh_token_lifetime']);
        $RefressTokenSaved = $this->OauthRefreshToken->save(array('OauthRefreshToken' => array(
            'client_id' => null,
            'expires' => $expires,
            'user_id' => $this->ownerIdResouseRequest,
            'scope' => null,
            'refresh_token' => $token["refresh_token"],
        )));
        return ($accessTokenSaved && $RefressTokenSaved) ? $token : false;
    }
    public function sendReponse($token) {
        $this->_Collection->getController()->set(array(
            'access_token' => $token['access_token'],
            'token_type' => $token['token_type'],
            'expires_in' => $token['expires_in'],
            'refresh_token' => $token['refresh_token'],
            'scope' => $token['scope'],
            '_serialize' => array('access_token','token_type','expires_in','refresh_token','scope')
        ));
    }
}