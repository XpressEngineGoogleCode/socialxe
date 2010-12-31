<?php

// 페이스북 라이브러리 로드
require_once(_XE_PATH_.'modules/socialxeserver/facebook/facebook.php');

// 페이스북을 위한 클래스
class socialxeServerProviderFacebook extends socialxeServerProvider{

    // 인스턴스
    function getInstance(&$sessionManager, $app_id, $app_secret){
        static $instance;
        if (!isset($instance)) $instance = new socialxeServerProviderFacebook($sessionManager, $app_id, $app_secret);
        return $instance;
    }

    // 생성자
    function socialxeServerProviderFacebook(&$sessionManager, $app_id, $app_secret){
        parent::socialxeServerProvider('twitter', $sessionManager);
        $this->app_id = $app_id;
        $this->app_secret = $app_secret;
        $this->callback = $this->getNotEncodedFullUrl('', 'module', 'socialxeserver', 'act', 'procSocialxeserverCallback', 'provider', 'facebook');
    }

    // 로그인 url을 얻는다.
    function getLoginUrl(){
        // 페이스북 객체 생성
        $fb = new Facebook(array(
            "appId" => $this->app_id,
            "secret" => $this->app_secret,
            "cookie" => false
        ));

        // URL 생성
        $loginUrl = $fb->getLoginUrl(array(
            "req_perms" => "publish_stream,offline_access",
            "display" => "popup",
            "next" => $this->callback,
            "cancel_url" => $this->callback
        ));

        $result = new Object();
        $result->add('url', $loginUrl);

        return $result;
    }

    // 콜백 처리
    function callback(){
        // 페이스북 객체 생성
        $fb = new Facebook(array(
            "appId" => $this->app_id,
            "secret" => $this->app_secret,
            "cookie" => false
        ));

        $session = $fb->getSession();

        // 로그인 취소했으면 이전 페이지로 돌아간다.
        if (!$session){
            $this->session->clearSession('facebook');
            return new Object();
        }

        // 사용자 정보도 받아서 저장해 놓는다.
        $account = $fb->api($fb->getUser());

        // 액세스 토큰과 사용자 정보를 묶는다.
        $info['access'] = $session;
        $info['account'] = $account;

        $result = new Object();
        $result->add('info', $info);
        return $result;
    }

    // 댓글 전송
    function send($comment, $access, $uselang = 'en', $use_socialxe = false){
        $result = new Object();

        // 머리글
        $lang->comment = $this->lang->comment[$uselang];
        if (!$lang->comment) $lang->comment = $this->lang->comment['en'];
        $lang->notify = $this->lang->notify[$uselang];
        if (!$lang->notify) $lang->notify = $this->lang->notify['en'];

        // 페이스북 객체 생성
        $fb = new Facebook(array(
            "appId" => $this->app_id,
            "secret" => $this->app_secret,
            "cookie" => false
        ));

        // 세션 세팅
        if (is_object($access)){
            $fb->setSession((array)$access, false);
        }

        // 얼마만큼의 길이를 사용할 수 있는지 확인
        $max_length = 420;

        // 실제 내용을 준비
        if ($comment->content_title){
            $title = $comment->content_title;
        }else if ($use_socialxe){
            $title = $lang->notify;
        }else{
            $title = $lang->comment;
        }
        $content2 = '「' . $title . '」 ' . $comment->content;

        // 내용 길이가 최대 길이를 넘는지 확인
        if (mb_strlen($content2, 'UTF-8') > $max_length){
            // 말줄임을 위한 3자를 남기고 내용을 자른다.
            $content = mb_substr($content2, 0, $max_length-3, 'UTF-8') . '...';
        }else{
            $content = $content2;
        }

        // 부모 댓글이 페이스북이면 댓글 처리
        if ($comment->parent && $comment->parent->provider == 'facebook'){
            $reply_id = $comment->parent->comment_id;

            try{
                $output = $fb->api($comment->parent->id . '/feed', 'POST', array('message' => $content, 'link' => $comment->content_link, 'picture' => 'http://socialxe.xpressengine.net/files/attach/project_logo/19351736.jpg'));
            }catch(FacebookApiException $e){
                $output->error = $e->__toString();
            }

        }

        // 댓글 전송
        else{
            try{
                $output = $fb->api($fb->getUser() . '/feed', 'POST', array('message' => $content, 'link' => $comment->content_link, 'picture' => 'http://socialxe.xpressengine.net/files/attach/project_logo/19351736.jpg'));
            }catch(FacebookApiException $e){
                $output->error = $e->__toString();
            }
        }

        $result->add('result', $output);
        return $result;
    }
}

?>