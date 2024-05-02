// Board

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
