// Board
function board(bdObj){
	
	(function($){
		var bd = $(bdObj);
		var default_style = bd.attr('data-default_style');
		if(default_style=='guest'){
			if(bd.find('#list_guest').length){	
				if(bd.find('form>div.simple_wrt').length){
					$.getScript("modules/editor/tpl/js/editor_common.min.js",function(){
						if($('#re_cmt').length) editorStartTextarea(2,'content','comment_srl');
						var cmtWrt = bd.find('form.cmt_wrt textarea');
						if(bd.find('form.bd_wrt_main textarea').length){
							editorStartTextarea(1,'content','document_srl');
						};
						cmtWrt.each(function(){
							editorStartTextarea($(this).attr('id').split('_')[1],'content','comment_srl');
						});
					});	
				}
			}
		}
		// Read Page Only
		if(bd.find('#readBox').length){		
			if(bd.find('form.bd_wrt').length){
				if(bd.find('form>div.wysiwyg').length){
					editorStartTextarea(2,'content','comment_srl');
				} else {
					$.getScript(request_uri+'modules/editor/tpl/js/editor_common.min.js',function(){
						editorStartTextarea(2,'content','comment_srl');
						var cmtWrt = bd.find('form.write_comment_inline textarea');
						cmtWrt.each(function(){
							editorStartTextarea($(this).attr('id').split('_')[1],'content','comment_srl');
						});
					});
				};
			};
		};	
	})(jQuery)
}
function reComment(doc_srl,cmt_srl,edit_url){
	var o = jQuery('#re_cmt').eq(0);
	o.find('input[name=error_return_url]').val('/'+doc_srl);
	o.find('input[name=mid]').val(current_mid);
	o.find('input[name=document_srl]').val(doc_srl);
	o.appendTo(jQuery('#comment_'+cmt_srl)).fadeIn().find('input[name=parent_srl]').val(cmt_srl);
	o.find('a.wysiwyg').attr('href',edit_url);
	o.find('textarea').focus();
}

$('a.copy_comment_url').click(function() {
    var url = $(this).attr('href');
    if (!url) {
        return
    }
    copy(url);
    alert('댓글 주소가 복사되었습니다.');
    return !1
});
function copy(val) {
    var dummy = document.createElement('textarea');
    document.body.appendChild(dummy);
    dummy.value = val;
    dummy.select();
    document.execCommand('copy');
    document.body.removeChild(dummy)
}
