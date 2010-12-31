<?php

// 서비스 관리를 위한 클래스
class socialxeProviderManager{
    var $master_provider = null;

    // 인스턴스
    function getInstance(&$sessionManager){
        static $instance;
        if (!isset($instance)) $instance = new socialxeProviderManager($sessionManager);
        return $instance;
    }

    // 생성자
    function socialxeProviderManager(&$sessionManager){
        // 세션 관리자 저장
        $this->session = $sessionManager;

        // 제공하는 서비스
        $this->provider_list = array('twitter', 'me2day', 'facebook', 'yozm');

        // 각 서비스 클래스
        $this->provider['twitter'] = &socialxeProviderTwitter::getInstance($this->session);
        $this->provider['me2day'] = &socialxeProviderMe2day::getInstance($this->session);
        $this->provider['facebook'] = &socialxeProviderFacebook::getInstance($this->session);
        $this->provider['yozm'] = &socialxeProviderYozm::getInstance($this->session);
    }

    // 환경 설정 값 세팅
    function setConfig($config){
        $this->config = $config;

        // 대표계정 설정
        $master_provider = $this->session->getSession('master');
        $this->setMasterProvider($master_provider);
    }

    // 제공하는 전체 서비스 목록
    function getFullProviderList(){
        return $this->provider_list;
    }

    // 제공하는 서비스 목록(환경설정에서 선택한 것만)
    function getProviderList(){
        $provider_list = $this->getFullProviderList();
        $result = array();
        foreach($provider_list as $provider){
            if ($this->config->select_service[$provider] == 'Y'){
                $result[] = $provider;
            }
        }

        return $result;
    }

    // 제공하는 서비스 여부 확인
    function inProvider($provider){
        return in_array($provider, $this->getProviderList());
    }

    // 로그인
    function doLogin($provider, $access, $account){
        $result = new Object();

        // 제공하는 서비스인지 확인
        if (!$this->inProvider($provider)){
            $result->setError(-1);
            return $result->setMessage('msg_invalid_provider');
        }

        // 로그인 처리
        $this->provider[$provider]->doLogin($access, $account);

        // 대표계정 설정
        if (count($this->getLoggedProviderList()) == 1){
            $this->setMasterProvider($provider);
        }

        return $result;
    }

    // 로그아웃
    function doLogout($provider){
        $result = new Object();

        // 제공하는 서비스인지 확인
        if (!$this->inProvider($provider)){
            $result->setError(-1);
            return $result->setMessage('msg_invalid_provider');
        }

        // 로그아웃 처리
        $this->provider[$provider]->doLogout();

        // 대표계정 설정
        $this->setNextMasterProvider();

        return $result;
    }

    // 로그인 여부 싱크
    function syncLogin(){
        foreach($this->getProviderList() as $provider){
            $this->provider[$provider]->syncLogin();
        }

        $master = $this->session->getSession('master');
        $this->setMasterProvider($master);
    }

    // 로그인 여부
    function isLogged($provider){
        if (!$this->inProvider($provider)) return;
        return $this->provider[$provider]->isLogged();
    }

    // 로그인된 서비스 리스트
    function getLoggedProviderList(){
        $result = array();

        foreach($this->getProviderList() as $provider){
            if ($this->provider[$provider]->isLogged()){
                $result[] = $provider;
            }
        }

        return $result;
    }

    // 대표계정 설정
    function setMasterProvider($provider){
        $result = new Object();

        // 제공하는 서비스인지 확인
        if (!$this->inProvider($provider)){
            $this->setNextMasterProvider();
            $result->setError(-1);
            return $result->setMessage('msg_invalid_provider');
        }

        // 대표계정 설정
        $this->master_provider = $provider;
        $this->session->setSession('master', $provider);

        return $result;
    }

    // 다음 대표계정 설정
    function setNextMasterProvider(){
        // 대표 계정을 현재 로그인된 서비스 중 그냥 첫번째로 선택한다.
        $logged_provider_list = $this->getLoggedProviderList();

        if (count($logged_provider_list)){
            $this->setMasterProvider($logged_provider_list[0]);
        }else{
            $this->session->clearSession('master');
        }
    }

    // 대표계정
    function getMasterProvider(){
        return $this->master_provider;
    }

    // 해당 서비스의 현재 로그인 아이디
    function getProviderID($provider){
        if (!$this->inProvider($provider)) return;
        return $this->provider[$provider]->getId();
    }

    // 대표계정의 아이디
    function getMasterProviderId(){
        // 대표계정이 설정되었는지 확인
        if (!$this->inProvider($this->getMasterProvider())) return;

        // 대표계정의 아이디
        return $this->provider[$this->getMasterProvider()]->getId();
    }

    // 대표계정의 닉네임
    function getMasterProviderNickName(){
        // 대표계정이 설정되었는지 확인
        if (!$this->inProvider($this->getMasterProvider())) return;

        // 대표계정의 닉네임
        return $this->provider[$this->getMasterProvider()]->getNickName();
    }

    // 대표계정의 프로필 이미지
    function getMasterProviderProfileImage(){
        // 대표계정이 설정되었는지 확인
        if (!$this->inProvider($this->getMasterProvider())) return;

        // 대표계정의 닉네임
        return $this->provider[$this->getMasterProvider()]->getProfileImage();
    }

    // 액세스 정보 얻기
    function getAccess($provider){
        // 제공하는 서비스인지 확인
        if (!$this->inProvider($provider)) return;

        return $this->provider[$provider]->getAccess();
    }

    // 액세스 정보 통으로 얻기
    function getAccesses(){
        $result = array();

        foreach($this->provider_list as $provider){
            $result[$provider] = $this->provider[$provider]->getAccess();
        }

        return $result;
    }

    // 소셜 서비스 링크
    function getAuthorLink($provider, $id){
        if (!$this->inProvider($provider)) return;

        return $this->provider[$provider]->getAuthorLink($id);
    }

    // 소셜 서비스의 리플 형식으로 반환
    function getReplyPrefix($provider, $id, $nick_name){
        if (!$this->inProvider($provider)) return;

        return $this->provider[$provider]->getReplyPrefix($id, $nick_name);
    }

    // 각 소셜 서비스의 리플 형식이 들어있는지 확인
    function getReplyProviderList($content){
        $result = Array();
        foreach($this->provider_list as $provider){
            if ($this->provider[$provider]->isContainReply($content)){
                $result[] = $provider;
            }
        }

        return $result;
    }
}

?>