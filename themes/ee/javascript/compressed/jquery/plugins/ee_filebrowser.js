/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */
!function(e){function t(){var t=e("#dir_choice"),r=.95*e(window).width();r>974&&(r=974),i.dialog({width:r,height:615,resizable:!1,position:["center","center"],modal:!0,draggable:!0,title:EE.filebrowser.window_title,autoOpen:!1,zIndex:99999,open:function(){var r=c[n].directory;isNaN(r)||t.val(r),t.trigger("interact");e("#dir_choice").val()},close:function(){e("#keywords",i).val("")}});var o=e("#file_browser_body").find("table");o.each(function(){return _=e(this),_.data("table_config")?!1:void 0});var l=_.data("table_config");_.table(l),_.table("add_filter",t),_.table("add_filter",e("#keywords"));var d=_.table("get_template");thumb_template=e("#thumbTmpl").remove().html(),table_container=_.table("get_container"),thumb_container=e("#file_browser_body"),e("#view_type").change(function(){"thumb"==this.value?(_.detach(),_.table("set_container",thumb_container),_.table("set_template",thumb_template),_.table("add_filter",{per_page:36})):(thumb_container.html(_),_.table("set_container",table_container),_.table("set_template",d),_.table("add_filter",{per_page:15}))}),e("#upload_form",i).submit(e.ee_filebrowser.upload_start),e("#file_browser_body",i).addClass(a)}function r(){"all"!=c[n].directory?(e("#dir_choice",i).val(c[n].directory),e("#dir_choice_form .dir_choice_container",i).hide()):(e("#dir_choice",i).val(),e("#dir_choice_form .dir_choice_container",i).show())}var i,o,n="",a="list",l=0,c={},d="",_=null;e.ee_filebrowser=function(){e.ee_filebrowser.endpoint_request("setup",function(r){dir_files_structure={},dir_paths={},i=e(r.manager).appendTo(document.body);for(var o in r.directories)l||(l=o),dir_files_structure[o]="";t(),"undefined"!=typeof e.ee_fileuploader&&e.ee_fileuploader({type:"filebrowser",open:function(){e.ee_fileuploader.set_directory_id(e("#dir_choice").val())},close:function(){e("#file_uploader").removeClass("upload_step_2").addClass("upload_step_1"),e("#file_browser").size()&&e.ee_filebrowser.reload()},trigger:"#file_browser #upload_form input"})})},e.ee_filebrowser.endpoint_request=function(t,r,i){"undefined"==typeof i&&e.isFunction(r)&&(i=r,r={}),r=e.extend(r,{action:t}),e.ajax({url:EE.BASE+"&"+EE.filebrowser.endpoint_url,type:"GET",dataType:"json",data:r,cache:!1,success:function(e){return e.error?void(d=e.error):void("function"==typeof i&&i.call(this,e))}})},e.ee_filebrowser.add_trigger=function(t,a,l,_){_?c[a]=l:e.isFunction(a)?(_=a,a="userfile",c[a]={content_type:"any",directory:"all"}):e.isFunction(l)&&(_=l,c[a]={content_type:"any",directory:"all"}),e(t).click(function(){if(d)return alert(d),!1;var e=this;return n=a,r(),i.dialog("open"),o=function(t){_.call(e,t,a)},!1})},e.ee_filebrowser.get_current_settings=function(){return c[n]},e.ee_filebrowser.placeImage=function(t){return e.ee_filebrowser.endpoint_request("file_info",{file_id:t},function(e){o(e),i.dialog("close")}),!1},e.ee_filebrowser.clean_up=function(t){void 0!=i&&(t&&o(t),e("#keywords",i).val(""),i.dialog("close"))},e.ee_filebrowser.reload_directory=function(){e.ee_filebrowser.reload()},e.ee_filebrowser.reload=function(){_&&(_.table("clear_cache"),_.table("refresh"))}}(jQuery);