$(function(){
	const containers = document.querySelectorAll('.default.tabbox');

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
		//container.style.cursor = 'grab'; // 커서 초기화
	  });

	  container.addEventListener('mouseup', () => {
		isDown = false;
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


	
});


$(document).ready(function () {
	$('.default.tabbox').each(function() {
        let $ul = $(this);
        let totalWidth = 0;

        // li 요소들의 전체 넓이를 합산
        $ul.find('li').each(function() {
            totalWidth += $(this).outerWidth(true); // margin 포함
        });

        // ul의 넓이와 비교
        if (totalWidth > $ul.width()) {
           $ul.after('<div class="morepage"><i class="bx bxs-chevron-right"></i></div>');
        }
    });
 });