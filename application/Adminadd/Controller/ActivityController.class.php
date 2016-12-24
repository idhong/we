<?php
namespace Adminadd\Controller;

use Common\Controller\AdminbaseController;

class ActivityController extends AdminbaseController{
    protected $joinModel,$activityModel;
    function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        $this->joinModel = M('activityJoin');
        $this->activityModel = M('activity');

    }

    public function sign_check(){
        $uid = sp_get_current_admin_id();
        if($uid==1){
            $where_ands=array("join_status=0 ");
        }else{
            $where_ands=array("join_status=0 and a.user_id=$uid ");
        }

        $fields=array(
            'start_time'=> array("field"=>"join_time","operator"=>">"),
            'end_time'  => array("field"=>"join_time","operator"=>"<"),
            'keyword'  => array("field"=>"activity_name","operator"=>"like"),
        );
        if(IS_POST){

            foreach ($fields as $param =>$val){
                if (isset($_POST[$param]) && !empty($_POST[$param])) {
                    $operator=$val['operator'];
                    $field = $val['field'];
                    if ($param == 'start_time' || $param == 'end_time') {
                        $get = strtotime($_POST[$param]);
                    }else{
                        $get=$_POST[$param];
                    }

                    $_GET[$param]=$get;
                    if($operator=="like"){
                        $get="%$get%";
                    }
                    array_push($where_ands, "$field $operator '$get'");
                }
            }
        }else{
            foreach ($fields as $param =>$val){
                if (isset($_GET[$param]) && !empty($_GET[$param])) {

                    $operator=$val['operator'];
                    $field   =$val['field'];

                    if ($param == 'start_time' || $param == 'end_time') {
                        $get = strtotime($_GET[$param]);
                    }else{
                        $get=$_GET[$param];
                    }

                    if($operator=="like"){
                        $get="%$get%";
                    }
                    array_push($where_ands, "$field $operator '$get'");
                }
            }
        }

        $where= join(" and ", $where_ands);

        $count=$this->joinModel
            ->alias("a")
            ->join(C('DB_PREFIX')."users as b ON a.user_id=b.id")
            ->join(C('DB_PREFIX')."activity as c ON a.activity_id=c.id")
            ->where($where)->count();
        $page = $this->page($count, 10);

        $posts=$this->joinModel
            ->alias("a")
            ->join(C('DB_PREFIX')."users as b ON a.user_id=b.id")
            ->join(C('DB_PREFIX')."activity as c ON a.activity_id=c.id")
            ->where($where)->limit($page->firstRow . ',' . $page->listRows)->select();

        $this->assign("Page", $page->show('Admin'));
        $this->assign("formget",$_GET);
        $this->assign("posts",$posts);

        $this->display(":sign_check");
    }
    public function refuse(){
        if(isset($_POST['ids'])){
            $ids = implode(",", $_POST['ids']);
            $data=array("join_status"=>"2");
            if ($this->joinModel->where("join_id in ($ids)")->save($data)) {
                $this->success("拒绝成功！");
            } else {
                $this->error("拒绝失败！");
            }
        }else{
            if(isset($_GET['id'])){
                $id = intval(I("get.id"));
                $data=array("join_id"=>$id,"join_status"=>"2");
                if ($this->joinModel->save($data)) {
                    $this->success("拒绝成功！");
                } else {
                    $this->error("拒绝失败！");
                }
            }
        }
    }
    public  function agree(){
        if(isset($_POST['ids'])){
            $ids = implode(",", $_POST['ids']);
            $data=array("join_status"=>"1");
            if ($this->joinModel->where("join_id in ($ids)")->save($data)) {
                $this->success("同意成功！");
            } else {
                $this->error("同意失败！");
            }
        }else{
            if(isset($_GET['id'])){
                $id = intval(I("get.id"));
                $data=array("join_id"=>$id,"join_status"=>"1");
                if ($this->joinModel->save($data)) {
                    $this->success("同意成功！");
                } else {
                    $this->error("同意失败！");
                }
            }
        }
    }

    public function activityManage()
    {
        $uid = get_current_admin_id();
        if ($uid == 1) {
            $where_ands=[];
        }else{
            $where_ands = array("user_id=$uid");
        }
        $fields=array(
            'start_time'=> array("field"=>"start_time","operator"=>">"),
            'end_time'  => array("field"=>"end_time","operator"=>"<"),
            'keyword'  => array("field"=>"activity_name","operator"=>"like"),
            'sort'  => array("field"=>"activity_status","operator"=>"="),
        );
        if(IS_POST){

            foreach ($fields as $param =>$val){
                if (isset($_POST[$param]) && !empty($_POST[$param])) {
                    $operator=$val['operator'];
                    $field = $val['field'];
                    if ($param == 'start_time' || $param == 'end_time') {
                        $get = strtotime($_POST[$param]);
                    }else{
                        $get=$_POST[$param];
                    }

                    $_GET[$param]=$get;
                    if($operator=="like"){
                        $get="%$get%";
                    }
                    $where_ands = array("activity_status=0");
                    array_push($where_ands, "$field $operator '$get'");
                }
            }
        }else{
            foreach ($fields as $param =>$val){
                if (isset($_GET[$param]) && !empty($_GET[$param])) {

                    $operator=$val['operator'];
                    $field   =$val['field'];

                    if ($param == 'start_time' || $param == 'end_time') {
                        $get = strtotime($_GET[$param]);
                    }else{
                        $get=$_GET[$param];
                    }

                    if($operator=="like"){
                        $get="%$get%";
                    }
                    array_push($where_ands, "$field $operator '$get'");
                }else if($param=='sort'){
                    array_push($where_ands, "activity_status = '0'");
                }
            }
        }

        $where= join(" and ", $where_ands);

        $count=$this->activityModel
            ->where($where)->count();
        $page = $this->page($count, 10);

        $posts=$this->activityModel
            ->where($where)->limit($page->firstRow . ',' . $page->listRows)->select();
        $this->assign("Page", $page->show('Admin'));
        $this->assign("formget",$_GET);
        $this->assign("posts",NullActivityCover($posts));
        $this->display(':activityManage');
    }
    public function delete(){
        if(isset($_POST['ids'])){
            $ids = implode(",", $_POST['ids']);
            $data=array("activity_status"=>"2");
            if ($this->activityModel->where("id in ($ids)")->save($data)) {
                $this->success("拉黑成功！");
            } else {
                $this->error("拉黑失败！");
            }
        }else{
            if(isset($_GET['id'])){
                $id = intval(I("get.id"));
                $data=array("id"=>$id,"activity_status"=>"2");
                if ($this->activityModel->save($data)) {
                    $this->success("拉黑成功！");
                } else {
                    $this->error("拉黑失败！");
                }
            }
        }
    }

    public function returnDelete()
    {
        if(isset($_POST['ids'])){
            $ids = implode(",", $_POST['ids']);
            $data=array("activity_status"=>"0");
            if ($this->activityModel->where("id in ($ids)")->save($data)) {
                $this->success("撤回成功！");
            } else {
                $this->error("撤回失败！");
            }
        }else{
            if(isset($_GET['id'])){
                $id = intval(I("get.id"));
                $data=array("id"=>$id,"activity_status"=>"0");
                if ($this->activityModel->save($data)) {
                    $this->success("撤回成功！");
                } else {
                    $this->error("撤回失败！");
                }
            }
        }
    }
}