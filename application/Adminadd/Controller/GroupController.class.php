<?php
namespace Adminadd\Controller;

use Common\Controller\AdminbaseController;

class GroupController extends AdminbaseController{
    protected $GroupModel;
    function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        $this->GroupModel = M('group');
    }

    public function group_verify(){
        $where_ands=array("group_status=0 ");
        $fields=array(
            'start_time'=> array("field"=>"group_create","operator"=>">"),
            'end_time'  => array("field"=>"group_create","operator"=>"<"),
            'keyword'  => array("field"=>"group_name","operator"=>"like"),
        );
        if(IS_POST){

            foreach ($fields as $param =>$val){
                if (isset($_POST[$param]) && !empty($_POST[$param])) {
                    $operator=$val['operator'];
                    $field   =$val['field'];
                    $get=$_POST[$param];
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
                    $get=$_GET[$param];
                    if($operator=="like"){
                        $get="%$get%";
                    }
                    array_push($where_ands, "$field $operator '$get'");
                }
            }
        }

        $where= join(" and ", $where_ands);

        $count=$this->GroupModel
            ->alias("a")
            ->join(C('DB_PREFIX')."users as b ON a.user_id=b.id")
            ->where($where)->count();
        $page = $this->page($count, 10);

        $posts=$this->GroupModel
            ->alias("a")
            ->join(C('DB_PREFIX')."users as b ON a.user_id=b.id")
            ->where($where)->limit($page->firstRow . ',' . $page->listRows)->select();

        $this->assign("Page", $page->show('Admin'));
        $this->assign("formget",$_GET);
        $this->assign("posts",$posts);

        $this->display(":group_verify");
    }
    public function refuse(){
        if(isset($_POST['ids'])){
            $ids = implode(",", $_POST['ids']);
            $data=array("group_status"=>"2");
            if ($this->GroupModel->where("group_id in ($ids)")->save($data)) {
                $this->success("拒绝成功！");
            } else {
                $this->error("拒绝失败！");
            }
        }else{
            if(isset($_GET['id'])){
                $id = intval(I("get.id"));
                $data=array("group_id"=>$id,"group_status"=>"2");
                if ($this->GroupModel->save($data)) {
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
            $data=array("group_status"=>"1");
            if ($this->GroupModel->where("group_id in ($ids)")->save($data)) {
                $this->success("同意成功！");
            } else {
                $this->error("同意失败！");
            }
        }else{
            if(isset($_GET['id'])){
                $id = intval(I("get.id"));
                $data=array("group_id"=>$id,"group_status"=>"1");
                if ($this->GroupModel->save($data)) {
                    $this->success("同意成功！");
                } else {
                    $this->error("同意失败！");
                }
            }
        }
    }
}