<?php
    require_once(_XE_PATH_.'modules/comment/comment.item.php');

    class socialCommentItem extends commentItem {

        // 생성자
        function socialCommentItem($comment_srl = 0){
            parent::commentItem($comment_srl);
        }

        // DB에서 댓글 정보를 가져온다.
        function _loadFromDB() {
            if(!$this->comment_srl) return;

            $args->comment_srl = $this->comment_srl;
            $output = executeQuery('socialxe.getComment', $args);

            $this->setAttribute($output->data);
        }

        // 속성을 직접 정의
        function setAttribute($attribute) {
            // 부모 함수 먼저 실행하고
            parent::setAttribute($attribute);

            // 소셜 정보를 추가한다.
            $oSocialxeModel = &getModel('socialxe');
            $this->add('link', $oSocialxeModel->getAuthorLink($this->get('provider'), $this->get('id')));

            // 대댓글 개수
            if ($this->get('sub_comment_count') === null){
                $this->add('sub_comment_count', 0);
            }

            // 리플 형식
            $this->add('reply_prefix', $oSocialxeModel->getReplyPrefix($this->get('provider'), $this->get('id'), $this->get('nick_name')));
        }

        // 프로필 이미지
        function getProfileImage() {
            if (!$this->isExists()) return;
            if ($profile_image = $this->get('profile_image')) return $profile_image;

            if (!$this->get('member_srl')) return;

            $oMemberModel = &getModel('member');
            $profile_info = $oMemberModel->getProfileImage($this->get('member_srl'));
            if(!$profile_info) return;

            return $profile_info->src;
        }

        // 댓글 권한 체크
        function isGranted(){
            // 우선 부모 함수 먼저
            $grant = parent::isGranted();

            // 권한 있으면 리턴~
            if ($grant) return true;

            // 소셜 권한이 있는지 확인
            $provider = $this->get('provider');
            $id = $this->get('id');

            if (!$id) return false;

            $oMemberModel = &getModel('socialxe');
            $logged_id = $oMemberModel->getProviderID($provider);

            if ($id == $logged_id) return true;
            return false;
        }

    }
?>
