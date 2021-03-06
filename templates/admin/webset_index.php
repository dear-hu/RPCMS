<?php if (!defined('CMSPATH')){exit('error!');}?>
{include:/header}
<style>
.me_form{width: 50%;}
.sendPost{position: fixed;top: 5.2rem;right: 2rem;width: 15rem;height: 2.6rem;line-height: 2.6rem;}
</style>
<div style="width:100%;background:#fff;position: relative;">
	<form class="me_form" action="" onSubmit="return false">
		<div class="tab_list">
			<a href="#tab1" class="tab_link active">基本设置</a>
			<a href="#tab2" class="tab_link">优化设置</a>
			<a href="#tab3" class="tab_link">评论/其他</a>
		</div>
		<div class="tabs">
			<div id="tab1" class="tab active">
				<div class="me_input big"><label>网站名称</label><input type="text" name="webName" value="{$option['webName']|default=''}"></div>
				<div class="me_input big"><label>关键字</label><input type="text" name="keyword" value="{$option['keyword']|default=''}"><p class="tips">多个关键词用“,”隔开</p></div>
				<div class="me_input big"><label>描述</label><textarea name="description">{$option['description']|default=''}</textarea></div>
				<div class="me_input big"><label>ICP备案号</label><input type="text" name="icp" value="{$option['icp']|default=''}"></div>
				<div class="me_input big"><label>统计代码</label><textarea name="totalCode">{$option['totalCode']|default=''|stripslashes}</textarea></div>
			</div>
			<div id="tab2" class="tab">
				<div class="me_input me_input_line"><label>开发模式</label><input type="checkbox" name="isDevelop" value="1" {if isset($option['isDevelop']) && $option['isDevelop'] == 1}checked{/if}></div>
				<div class="me_input me_input_line"><label>网站关闭</label><input type="checkbox" name="webStatus" value="1" {if isset($option['webStatus']) && $option['webStatus'] == 1}checked{/if}></div>
				<div class="me_input big"><label>闭站说明</label><textarea name="closeText">{$option['closeText']|default=''}</textarea></div>
				<div class="me_input big"><label>前台分页大小</label><input type="number" name="pagesize" value="{$option['pagesize']|default=''}"></div>
				<div class="me_input big"><label>附件类型</label><input type="text" name="fileTypes" value="{$option['fileTypes']|default=''}"></div>
				<div class="me_input big"><label>附件大小(MB)</label><input type="text" name="fileSize" value="{$option['fileSize']|default=''}"></div>
				<div class="me_input me_input_line"><label>启用分类别名</label><input type="checkbox" name="cateAlias" value="1" {if isset($option['cateAlias']) && $option['cateAlias'] == 1}checked{/if}></div>
				<div class="me_input me_input_line"><label>启用文章别名</label><input type="checkbox" name="logAlias" value="1" {if isset($option['logAlias']) && $option['logAlias'] == 1}checked{/if}></div>
				<div class="me_input me_input_line"><label>启用单页别名</label><input type="checkbox" name="pageAlias" value="1" {if isset($option['pageAlias']) && $option['pageAlias'] == 1}checked{/if}></div>
				<div class="me_input me_input_line"><label>启用标签别名</label><input type="checkbox" name="tagAlias" value="1" {if isset($option['tagAlias']) && $option['tagAlias'] == 1}checked{/if}></div>
				<div class="me_input me_input_line"><label>启用专题别名</label><input type="checkbox" name="specialAlias" value="1" {if isset($option['specialAlias']) && $option['specialAlias'] == 1}checked{/if}></div>
			</div>
			<div id="tab3" class="tab">
				<div class="me_input me_input_line"><label>缩略图</label>
					<input type="number" name="attImgWitch" value="{$option['attImgWitch']|default=''}" placeholder="缩略图宽度">
					<span class="text" style="width: auto;background: transparent;">x</span>
					<input type="number" name="attImgHeight" value="{$option['attImgHeight']|default=''}" placeholder="缩略图高度">
				</div><br/><br/>
				<div class="me_input me_input_line"><label>评论开启</label><input type="checkbox" name="commentStatus" value="1" {if isset($option['commentStatus']) && $option['commentStatus'] == 1}checked{/if}></div>
				<div class="me_input me_input_line"><label>评论审核</label><input type="checkbox" name="commentCheck" value="1" {if isset($option['commentCheck']) && $option['commentCheck'] == 1}checked{/if}></div>
				<div class="me_input me_input_line"><label>内容包含中文</label><input type="checkbox" name="commentCN" value="1" {if isset($option['commentCN']) && $option['commentCN'] == 1}checked{/if}></div>
				<div class="me_input me_input_line"><label>评论验证码</label><input type="checkbox" name="commentVcode" value="1" {if isset($option['commentVcode']) && $option['commentVcode'] == 1}checked{/if}></div>
				<div class="me_input big"><label>显示排序</label><select name="commentSort">
					<option value="new" {php}echo $option['commentSort'] == 'new' ? 'selected' : '';{/php}>最新</option>
					<option value="old" {php}echo $option['commentSort'] == 'old' ? 'selected' : '';{/php}>最早</option>
				</select></div>
				<div class="me_input big"><label>每页显示数量</label><input type="number" name="commentPage" value="{$option['commentPage']|default=''}"></div>
				<div class="me_input big"><label>评论间隔时间(秒)</label><input type="number" name="commentInterval" value="{$option['commentInterval']|default='0'}"></div>
				
			</div>
		</div>
		<button type="sumbit" class="rp_btn success sendPost">保存设置</button>
	</form>
</div>
<script>
$(document).ready(function(){
	$(".menu_tree").find(".menu_item[data-type='webset']").addClass('active');
	$(".sendPost").click(function(){
		var a=$('.me_form').serializeArray(),
			param={
				'isDevelop':0,
				'webStatus':0,
				'cateAlias':0,
				'logAlias':0,
				'pageAlias':0,
				'tagAlias':0,
				'commentStatus':0,
				'commentCheck':0,
				'commentCN':0,
				'commentVcode':0,
			};
		$.each(a, function(d,e){
			param[e.name] = e.value;
		});
		$.ajaxpost('{:url("index/webPost")}',param,function(res){
			$.Msg(res.msg);
			res.code == 200 && setTimeout(function(){window.location.reload()},2200);
		});
	})
})
</script>
{include:/footer}