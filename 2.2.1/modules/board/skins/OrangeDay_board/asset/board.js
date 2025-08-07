// Board
function board(bdObj){
	
	(function($){
		var bd = $(bdObj);
		var default_style = bd.attr('data-default_style');
		if(default_style=='guest'){
			if(bd.find('#list_guest').length){	
				if(bd.find('form>div.simple_wrt').length){
					$.getScript("modules/editor/tpl/js/editor_common.js",function(){
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
					$.getScript(request_uri+'modules/editor/tpl/js/editor_common.js',function(){
						editorStartTextarea(2,'content','comment_srl');
						var cmtWrt = bd.find('form.write_comment_inline textarea');
						cmtWrt.each(function(){
							editorStartTextarea($(this).attr('id').split('_')[1],'content','comment_srl');
						});
					});
				};
			};
		};
		
		
		if(bd.find('#list-bd-table').length){	
			 let $wtable = $('#list-bd-table');
			 let $table = $('#list-bd-table > table');
			 /*
			 if ($table.width() > $wtable.width()) {
			*/
			
			
				 /**/
				const containers = document.querySelectorAll('#list-bd-table');

				containers.forEach((container) => {
				  let isDown = false;
				  let startX;
				  let scrollLeft;

				  container.addEventListener('mousedown', (e) => {
					isDown = true;
					container.classList.add('active');
					startX = e.pageX - container.offsetLeft;
					scrollLeft = container.scrollLeft;
					//container.style.cursor = 'grabbing'; // 드래그 중일 때 커서 변경
					//document.onselectstart = () => false;
				  });

				  container.addEventListener('mouseleave', () => {
					isDown = false;
					container.classList.remove('active');
					//container.style.cursor = 'grab'; // 커서 초기화
				  });

				  container.addEventListener('mouseup', () => {
					isDown = false;
					container.classList.remove('active');
					//container.style.cursor = 'grab';
				  });

				  container.addEventListener('mousemove', (e) => {
					if (!isDown) return;
					e.preventDefault();
					const x = e.pageX - container.offsetLeft;
					const walk = (x - startX) * 1; // 스크롤 속도 조절
					container.scrollLeft = scrollLeft - walk;
				  });
				});
				/**/
				
				
			/*
			}
			*/
		}
		
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
    $(this).after('<span class="commtip"> 댓글 주소가 복사되었습니다.</span>');
	setTimeout(function() { 
		$(".commtip").fadeOut();
	}, 1000);
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

