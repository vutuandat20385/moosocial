<?php

App::import('Lib/SocialIntegration', 'ProviderModelOAuth2');

class SocialIntegration_Providers_Google extends SocialIntegration_Provider_Model_OAuth2 {

    // > more infos on google APIs: http://developer.google.com (official site)
    // or here: http://discovery-check.appspot.com/ (unofficial but up to date)
    // default permissions 
    public $scope = "https://www.googleapis.com/auth/userinfo.email https://www.google.com/m8/feeds/";

    /**
     * IDp wrappers initializer 
     */
    function initialize() {
        parent::initialize();

        // Provider api end-points
        $this->api->authorize_url = "https://accounts.google.com/o/oauth2/auth";
        $this->api->token_url = "https://accounts.google.com/o/oauth2/token";
        $this->api->token_info_url = "https://www.googleapis.com/oauth2/v2/tokeninfo";
        $this->api->revoke_token_url = "https://accounts.google.com/o/oauth2/revoke";

        // Override the redirect uri when it's set in the config parameters. This way we prevent
        // redirect uri mismatches when authenticating with Google.
        if (isset($this->config['redirect_uri']) && !empty($this->config['redirect_uri'])) {
            $this->api->redirect_uri = $this->config['redirect_uri'];
        }
    }

    /**
     * begin login step 
     */
    function loginBegin() {
        $parameters = array("scope" => $this->scope, "access_type" => "offline");
        $optionals = array("scope", "access_type", "redirect_uri", "approval_prompt", "hd", "state");

        foreach ($optionals as $parameter) {
            if (isset($this->config[$parameter]) && !empty($this->config[$parameter])) {
                $parameters[$parameter] = $this->config[$parameter];
            }
            if (isset($this->config["scope"]) && !empty($this->config["scope"])) {
                $this->scope = $this->config["scope"];
            }
        }

        SocialIntegration_Auth::redirect($this->api->authorizeUrl($parameters));
    }

    /**
     * load the user profile from the IDp api client
     */
    function getUserProfile() {
        // refresh tokens if needed
        $this->refreshToken();

        // ask google api for user infos
        if (strpos($this->scope, '/auth/userinfo.email') !== false) {
            $verified = $this->api->api("https://www.googleapis.com/oauth2/v2/userinfo");

            if (!isset($verified->id) || isset($verified->error))
                $verified = new stdClass();
        } else {
            $verified = $this->api->api("https://www.googleapis.com/plus/v1/people/me/openIdConnect");

            if (!isset($verified->sub) || isset($verified->error))
                $verified = new stdClass();
        }

        $response = $this->api->api("https://www.googleapis.com/plus/v1/people/me");
        if (!isset($response->id) || isset($response->error)) {
            throw new Exception("User profile request failed! {$this->providerId} returned an invalid response.", 6);
        }
        
        $uc = array();
        # store the user profile.
        $uc['identifier'] = (property_exists($verified, 'id')) ? $verified->id : ((property_exists($response, 'id')) ? $response->id : "");
        $uc['displayName'] = (property_exists($response, 'displayName')) ? $response->displayName : "";
        $uc['first_name'] = (property_exists($response, 'name')) ? $response->name->givenName : "";
        $uc['last_name'] = (property_exists($response, 'name')) ? $response->name->familyName : "";
        $uc['profileURL'] = (property_exists($response, 'url')) ? $response->url : "";
        $uc['photoURL'] = (property_exists($response, 'image')) ? ((property_exists($response->image, 'url')) ? $response->image->url : '') : '';
        $uc['gender'] = (property_exists($response, 'gender')) ? $response->gender : "";
        $uc['description'] = (property_exists($response, 'aboutMe')) ? $response->aboutMe : "";
        $uc['email'] = (property_exists($response, 'email')) ? $response->email : ((property_exists($verified, 'email')) ? $verified->email : "");
        $uc['access_token'] = $this->api->access_token;
        return $uc;
    }

    /**
     * load the user (Gmail and google plus) contacts 
     *  ..toComplete
     */
    function getUserContacts() {
        // refresh tokens if needed 
        $this->refreshToken();

        $contacts = array();
        $key_temp = 0;
        if (!isset($this->config['contacts_param'])) {
            $this->config['contacts_param'] = array("max-results" => 500);
        }

        // Google Gmail and Android contacts
        if (strpos($this->scope, '/m8/feeds/') !== false) {

            $response = $this->api->api("https://www.google.com/m8/feeds/contacts/default/full?"
                    . http_build_query(array_merge(array('alt' => 'json', 'v' => '3.0'), $this->config['contacts_param'])));

            if (!$response) {
                return ARRAY();
            }

            if (isset($response->feed->entry)) {
                foreach ($response->feed->entry as $idx => $entry) {
                    if (isset($entry->{'gd$email'}[0]->address) && !empty($entry->{'gd$email'}[0]->address)) {
                        $contacts[$key_temp]['email'] = isset($entry->{'gd$email'}[0]->address) ? (string) $entry->{'gd$email'}[0]->address : '';
                        $contacts[$key_temp]['name'] = isset($entry->title->{'$t'}) && !empty($entry->title->{'$t'}) ? (string) $entry->title->{'$t'} : $contacts[$key_temp]['email'];
                        $contacts[$key_temp]['identifier'] = ($contacts[$key_temp]['email'] != '') ? $contacts[$key_temp]['email'] : '';
                        $contacts[$key_temp]['description'] = '';
                        if (property_exists($entry, 'link')) {
                            /**
                             * sign links with access_token
                             */
                            if (is_array($entry->link)) {
                                foreach ($entry->link as $l) {
                                    if (property_exists($l, 'gd$etag') && $l->type == "image/*") {
                                        $contacts[$key_temp]['picture'] = $this->addUrlParam($l->href, array('access_token' => $this->api->access_token));
                                    } else if ($l->type == "self") {
                                        $contacts[$key_temp]['profileURL'] = $this->addUrlParam($l->href, array('access_token' => $this->api->access_token));
                                    }
                                }
                            }
                        } else {
                            $contacts[$key_temp]['profileURL'] = '';
                        }
                        if (property_exists($response, 'website')) {
                            if (is_array($response->website)) {
                                foreach ($response->website as $w) {
                                    if ($w->primary == true)
                                        $contacts[$key_temp]['webSiteURL'] = $w->value;
                                }
                            } else {
                                $contacts[$key_temp]['webSiteURL'] = $response->website->value;
                            }
                        } else {
                            $contacts[$key_temp]['webSiteURL'] = '';
                        }

                        $key_temp++;
                    }
                }
            }
        }

        // Google social contacts
        /*
        if (strpos($this->scope, '/auth/plus.login') !== false) {

            $response = $this->api->api("https://www.googleapis.com/plus/v1/people/me/people/visible?"
                    . http_build_query($this->config['contacts_param']));

            if (!$response) {
                return ARRAY();
            }

            foreach ($response->items as $idx => $item) {
                $contacts[$key_temp]['email'] = (property_exists($item, 'email')) ? $item->email : '';
                $contacts[$key_temp]['displayName'] = (property_exists($item, 'displayName')) ? $item->displayName : '';
                $contacts[$key_temp]['identifier'] = (property_exists($item, 'id')) ? $item->id : '';

                $contacts[$key_temp]['description'] = (property_exists($item, 'objectType')) ? $item->objectType : '';
                $contacts[$key_temp]['photoURL'] = (property_exists($item, 'image')) ? ((property_exists($item->image, 'url')) ? $item->image->url : '') : '';
                $contacts[$key_temp]['profileURL'] = (property_exists($item, 'url')) ? $item->url : '';
                $contacts[$key_temp]['webSiteURL'] = '';

                $key_temp++;
            }
        }
         * 
         */

        return $contacts;
    }
    
    public function logout() {
        $this->api->revokeToken($this->api->access_token);
        parent::logout();
    }

    /**
     * Add to the $url new parameters
     * @param string $url
     * @param array $params
     * @return string
     */
    function addUrlParam($url, array $params) {
        $query = parse_url($url, PHP_URL_QUERY);

        // Returns the URL string with new parameters
        if ($query) {
            $url .= '&' . http_build_query($params);
        } else {
            $url .= '?' . http_build_query($params);
        }
        return $url;
    }

}
