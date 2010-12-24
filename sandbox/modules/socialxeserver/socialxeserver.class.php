<?php
    
    require_once(_XE_PATH_.'modules/socialxeserver/sessionManager.php');
    require_once(_XE_PATH_.'modules/socialxeserver/communicator.php');
    require_once(_XE_PATH_.'modules/socialxeserver/providerManager.php');
    require_once(_XE_PATH_.'modules/socialxeserver/provider.class.php');
    require_once(_XE_PATH_.'modules/socialxeserver/provider.twitter.php');
    require_once(_XE_PATH_.'modules/socialxeserver/provider.me2day.php');
    require_once(_XE_PATH_.'modules/socialxeserver/provider.facebook.php');
    require_once(_XE_PATH_.'modules/socialxeserver/provider.yozm.php');
    
    class socialxeserver extends ModuleObject {

        function socialxeserver(){
            // 설정 정보를 받아옴 (module model 객체를 이용)
            $oModuleModel = &getModel('module');
            $this->config = $oModuleModel->getModuleConfig('socialxeserver');
            
            $this->session = &socialxeServerSessionManager::getInstance();
            $this->communicator = &socialxeServerCommunicator::getInstance($this->session, $this->config);
        }
        
        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            return new Object();
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
        }
        
        function getNotEncodedFullUrl() {
            $num_args = func_num_args();
            $args_list = func_get_args();
            $request_uri = Context::getRequestUri();
            if(!$num_args) return $request_uri;

            $url = Context::getUrl($num_args, $args_list, null, false);
            if(!preg_match('/^http/i',$url)){
                preg_match('/^(http|https):\/\/([^\/]+)\//',$request_uri,$match);
                $url = Context::getUrl($num_args, $args_list, null, false);
                return substr($match[0],0,-1).$url;
            }
            return $url;
        }
    }
?>
