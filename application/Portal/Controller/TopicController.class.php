<?php
namespace Portal\Controller;

use Common\Controller\HomebaseController;
use Common\Controller\MemberbaseController;

class TopicController extends HomebaseController{
    protected $topicModel,$groupModel,$joinModel;
    function _initialize()
    {
        $this->topicModel = D('Portal/Topic');
        $this->groupModel = M('group');
        $this->joinModel = M('join');
        parent::_initialize(); // TODO: Change the autogenerated stub
    }

    function topic(){

        $join = C('DB_PREFIX')."group as b ON a.group_id=b.group_id";

        $where = array('topic_status' => 1);

        $count = $this->topicModel
                ->alias("a")
                ->where($where)
                ->join($join)
                ->count();

        $page = $this->page($count, 4);

        $topics = $this->topicModel->alias("a")
                    ->join($join)
                    ->where($where)
                    ->order("topic_id desc")
                    ->field("topic_id,topic_title,topic_content,b.group_id,group_name,a.zan_count as topic_zan_count")
                    ->limit($page->firstRow.','.$page->listRows)
                    ->select();
        $sub = substring($topics,'topic_content',255);

        $this->newJoin();

        $this->assign('topics', $sub);
        $this->assign('Page', $page->show('Admin'));

        $this->display(":topic");
    }

    /**
     * 新创建的小组
     */
    function newJoin(){
        $groups = $this->groupModel
                    ->order("group_id desc")
                    ->limit(5)
                    ->select();

        $NotNull = $this->NullPic($groups);

        $this->assign("newJoinGroups", $NotNull);
    }
    /**当封面为空时，用默认pic
     * @param $arr 查询结果
     */
    protected function NullPic($arr){

        foreach ($arr as $key => $item) {
            if (empty($item['group_cover'])) {
                $arr[$key]['group_cover']=sp_get_asset_upload_path("cover/default_tupian4.png");
            }
        }
        return $arr;
    }
    /**
     * 话题详情tpl
     */
    public function topic_detail()
    {

        $id = I('get.topic_id');

        $where = array('topic_id' => $id);
        $join_user = C('DB_PREFIX') ."users as b ON a.user_id=b.id";
        $join_group = C('DB_PREFIX') ."group as c ON a.group_id=c.group_id";
        $details = $this->topicModel
            ->alias('a')
            ->where($where)
            ->join($join_user)
            ->join($join_group)
            ->field('c.group_id,group_cover,group_total,chat_count,user_nicename,topic_id,topic_title,group_name,user_nicename,avatar,topic_create,a.zan_count,comment_count,topic_content')
            ->find();

        $this->isJoin($details['group_id']);

        $details = $this->user_default($details);

        $details = NullGroupCover($details);

        $belongGd = $this->topicModel->where(array('topic_id' => $id))->getField('group_id');
        $this->latelyTopic($belongGd);


        $this->assign('details', $details);
        $this->display(":topic_detail");
        //var_dump($details);
    }
    /**判断是否加入小组
     * @param $id小组id
     */
    protected function isJoin($id){

        if (sp_is_user_login()) {
            $user_id = sp_get_current_userid();
            $if = $this->joinModel->where(array('group_id'=>$id,'user_id'=>$user_id))->find();
            if (empty($if)) {
                $rst =false;
            }else{
                $rst=true;
            }
        }else{
            $rst = false;
        }
        $this->assign('joinStatus', $rst);
    }
    /**
     * 最近话题assign
     */
    function latelyTopic($gid){
        $topics = $this->topicModel
                    ->where(array('group_id'=>$gid))
                    ->order('topic_id desc')
                    ->limit(6)
                    ->select();
        $this->assign('latelyTops', $topics);
    }
    /**
     * 用户没有设置头像时，采用默认头像
     */
    protected function user_default($arr){
        foreach ($arr as $key => $item) {
            if (empty($arr['avatar'])) {
                $arr['avatar']=sp_get_asset_upload_path("group_avatar/user_default.jpg");
            }
        }
        return $arr;
    }
    /**
     * 话题增加页
     */
    function topicadd(){

        if (!sp_is_user_login()){
            $this->error('你未登录，请登陆后再发言');
        }
        $id = I('get.group_id');
        $user_id = sp_get_current_userid();
        $if = $this->joinModel->where(array('group_id'=>$id,'user_id'=>$user_id))->find();
        if(empty($if)){
            $this->error('你尚未加入小组，要加入小组才能发言');
        }

        $this->check_user();
        $groups = $this->groupModel->where(array('group_id'=>$id))->find();
        $this->assign('groups', $groups);
        //检测该话题是否是用户自身创建的，防止非法修改
        $tid = I('get.topic_id');
        if (!empty($tid)) {
            $umsg = $this->topicModel->where(array('topic_id'=>$tid))->find();
            if ($umsg['user_id'] != sp_get_current_userid()) {
                $this->error('该话题不是你创建的，你不能编辑');
            }else{
                $this->assign('currentTopics', $umsg);
            }
        }


        $this->display(":topicadd");
    }
    /**
     * 话题增加ajax
     */
    function dotopicadd(){

        $id = I('group_id');
        if (!sp_is_user_login()){
            $this->error('你未登录，请登陆后再发言');
        }/*else if(!sp_auth_check(sp_get_current_userid(),'portal/topic/topicadd')){
            $this->error('你不是组织用户没有访问权限');
        }*/else if($this->judgeId($id)==0){
            $this->error('参数错误，不存在该小组');
        }else if(!sp_check_verify_code()){
            $this->error("验证码错误请重新输入");
        }else{
            $this->check_user();
            //编辑话题
            if (I('post.contentLen') > 0) {
                $createRsg = $this->topicModel->create();
                if(!$createRsg)
                {
                    $this->error($this->topicModel->getError());
                }else{
                    $this->topicModel->save();
                    $this->success('保存成功！');
                }
            }

//            var_dump($this->topicModel);
            //不合法
            $createRsg = $this->topicModel->create();
            $group_id = I('group_id');
            if(!$createRsg)
            {
                $this->error($this->topicModel->getError());
            }else if($this->topicModel->add()){

                $this->success('话题新增成功，请等待管理员审核',U('Portal/group/group_detail',array('group_id'=>$group_id)));
            }else{
                $this->error('话题新增失败，具体原因请联系管理员');
            }
        }
    }

    /**判断group_id参数是否非法
     * @param $id   get参数
     * @return mixed 小组的数量 0表示没有改小组
     */
    function judgeId($id){
        $num = $this->groupModel->where('group_id=%d',$id)
            ->count();
        return $num;
    }

    /**
     *点赞操作
     */
    function topic_like(){
        $this->check_login();

        $id=intval($_GET['id']);

        $topic_model=M("topic");

        $can_like=sp_check_user_action("topic$id",1);

        if($can_like){
            $topic_model->save(array("topic_id"=>$id,"zan_count"=>array("exp","zan_count+1")));
            $this->success("点赞成功！在个人中心可见点赞的详情");
        }else{
            $this->error("您已赞过啦！");
        }
    }
}