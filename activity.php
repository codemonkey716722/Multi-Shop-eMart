<?php
/*
 Author:刘洋
 Wechat:15221580783
 基于ecshop模板开发
 2017-7-16	15:58
 */

/* ---- 活动列表 ---- */
 
define('IN_ECS',true);
require(dirname(__FILE__).'/includes/init.php');
if((DEBUG_MODE & 2)!=2)
	$smarty->caching=true;
require_once(ROOT_PATH.'includes/lib_order.php');
include_once(ROOT_PATH . 'includes/lib_transaction.php');

// 载入语言文件 
require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/shopping_flow.php');
require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/user.php');

/* ---- PROCESSOR ---- */
$cache_id = sprintf('%X', crc32(date('ym') . '-' . $_CFG['lang']));
if (!$smarty->is_cached('activity.dwt', $cache_id))
{
	assign_template();
	$position = assign_ur_here(0, $_LANG['shopping_activity']);
	$smarty->assign('page_title',       $position['title']);    // 页面标题
	$smarty->assign('ur_here',          $position['ur_here']);  // 当前位置

	// 数据准备
	// 获取得用户等级 
	$user_rank_list = array();
	$user_rank_list[0] = $_LANG['not_user'];
	$sql = "SELECT rank_id, rank_name FROM " . $ecs->table('user_rank');
	$res = $db->query($sql);
	while ($row = $db->fetchRow($res))
		$user_rank_list[$row['rank_id']] = $row['rank_name'];

	// 开始执行
	$nowtime = time();
	$sql = "SELECT fa.* FROM " . $ecs->table('favourable_activity'). " AS fa "."WHERE fa.start_time<=".$nowtime." AND fa.end_time>=".$nowtime." ORDER BY fa.`sort_order` ASC,fa.`end_time` DESC ";
	$res=$db->query($sql);
	
	$list=array();
	while($row=$db->fetchRow($res)){
		$row['start_time']=local_date('Y-m-d H:i',$row['start_time']);
		$row['end_time']=local_date('Y-m-d H:i',$row['end_time']);
		//享受优惠会员等级
		$user_rank=explode(',',$row['user_rank']);
		$row['user_rank']=array();
		foreach($user_rank AS $val){
			if(isset($user_rank_list[$val])){
				$row['user_rank'][]=$user_rank_list[$val];
			}
		}
		
		//优惠范围类型，内容
		if($row['act_range']!=FAR_ALL && !empty($row['act_range_ext'])){
			if($row['act_range']==FAR_CATEGORY){
				$row['act_range']=$_LANG['far_category'];
				$row['program']='category.php?id=';
				$sql="SELECT cat_id AS id,cat_name AS name FROM ".$ecs->table('category')." WHERE cat_id ".db_create_in($row['act_range_ext']);
				if($row['supplier_id']>0){
					$row['program']='supplier.php?go=category&suppId='.$row['supplier_id'].'&id=';
					$sql="SELECT cat_id AS id,cat_name AS name FROM ".$ecs->table('supplier_category')." WHERE cat_id".db_create_in($row['act_range_ext']);
				}
			}
			elseif($row['act_range']==FAR_BRAND){
				$row['act_range']=$_LANG['far_brand'];
				$row['program']='brand.php?id=';
				if($row['supplier_id']>0){
					$row['program']='brand.php?suppId='.$row['supplier_id'].'&id=';
			
				}
				$sql="SELECT brand_id AS id,brand_name AS name FROM ".$ecs->table('brand')." WHERE brand_id ".db_create_in($row['act_range_ext']);
			}
			else{
				$row['act_range']=$_LANG['far_goods'];
				$row['program']='goods.php?id=';
				$sql="SELECT goods_id AS id,goods_name AS name FROM ".$ecs->table('goods')." WHERE goods_id ".db_create_in($row['act_range_ext'].' AND is_delete=0 AND is_on_sale=1');
				
			}
			$act_range_ext=$db->getAll($sql);
			$row['act_range_ext']=$act_range_ext;
		}else{
			$row['act_range']=$_LANG['far_all'];
		}
		//优惠方式
		$row['act_type_num']=$row['act_type'];
		switch($row['act_type']){
			case 0:
				$row['act_type']=$_LANG['fat_goods'];
				$row['gift']=unserialize($row['gift']);
				if(is_array($row['gift'])){
					foreach($row['gift'] as $k=>$v){
						$row['gift'][$k]['thumb']=get_image_path($v['id'],$db->getOne("SELECT goods_thumb FROM ".$ecs->table('goods')." WHERE goods_id='".$v['id']."'"),true);
					}
				}
				break;
			case 1:
				$row['act_type']=$_LANG['fat_price'];
				$row['act_type_ext'].=$_LANG['unit_yuan'];
				$row['gift']=array();
				break;
			case 2:
				$row['act_type']=$_LANG['fat_discount'];
				$row['act_type_ext'].="%";
				$row['gift']=array();
				break;
		}
		if($row['supplier_id']>0){
			$sql="select code,value from ".$ecs->table('supplier_shop_config')." where supplier_id=".$row['supplier_id']." AND code in('shop+name','shop_logo')";
			$r=$db->getAll($sql);
			foreach($r as $k=>$v){
				$row[$v['code']]=$v['value'];
			}
		}else{
			$row['shop_logo']='./images/ziying.jpg';
		}
		$list[]=$row;
	}
	$smarty->assign('list',$list);
	$smarty->assign('helps',get_shop_help());   //网店帮助
	$smarty->assign('lang',$_LANG);
	assign_dynamic('activity');
}
$smarty->display('activity.dwt',$cache_id);