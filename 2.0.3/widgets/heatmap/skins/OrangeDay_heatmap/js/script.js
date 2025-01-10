
//const WEEKDAYS = ['日', '月', '火', '水', '木', '金', '土'];
const WEEKDAYS = ['일', '월', '화', '수', '목', '금', '토'];
const MONTHS = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'];
const MAX_ROW = 8;
const MAX_COLUMN = 53;

function createHeatmap(elementId, heatmapData, postsLevel, firstDate, lastDate) {

    function getLevelClass(postsLevel, countDoc) {
        if (countDoc < postsLevel[0]) {
            return 'level-0';
        } else if (countDoc < postsLevel[1]) {
            return 'level-1';
        } else if (countDoc < postsLevel[2]) {
            return 'level-2';
        } else if (countDoc < postsLevel[3]) {
            return 'level-3';
        } else if (postsLevel[3] <= countDoc) {
            return 'level-4';
        }
    }

    const heatmap = document.getElementById(elementId);

    /* row head: weekday label */
    for (let i=0; i<MAX_ROW; i++) {
        const div = document.createElement('div');
        div.className = 'weekday-label';
        if (i != 0) {
            div.innerText = WEEKDAYS[i-1];
        }
        div.style.gridRowStart = i + 1;
        div.style.gridColumnStart = 1;
        heatmap.appendChild(div);
    }

    const currDate = new Date(firstDate);    
    let printMonthLabel = true;
    /* to avoid overlapping first two month labels */
    if (currDate.getDate() > 20) {
        printMonthLabel = false;
    }
    let monthIndex = currDate.getMonth() + 1;
    let startWeekday = currDate.getDay();

    for (let i=0; i<MAX_ROW * MAX_COLUMN; i++) {
        const div = document.createElement('div');
        const rowIndex = (i % 8) + 1;
        const columnIndex = Math.floor(i / 8) + 2;
        div.style.gridRowStart = rowIndex;
        div.style.gridColumnStart = columnIndex;

        /* column head: month label */
        if (i % 8 == 0) {
            div.className = 'month-label';
            if (printMonthLabel == true) {
                div.innerText = MONTHS[monthIndex-1];
                printMonthLabel = false;
            }
        }
        else {
            /* check for the start cell */
            if (startWeekday != 0) {
                startWeekday -= 1;
                continue;
            }
            /* check for the last cell */
            if (currDate >= new Date(lastDate)) {
                break;
            }

            /* update day cell info in heatmap */
            const dateIndex = currDate.toISOString().split('T')[0];
            const countDoc = heatmapData[dateIndex] || 0;
            div.className = 'day';
            if (countDoc >= 0) {
                const levelClass = getLevelClass(postsLevel, countDoc); 
                div.classList.add(levelClass);
            }

            /* add tooltip to the day cell */
            const span = document.createElement('span');
            span.className = 'tooltip';
            span.innerText = `${countDoc} posts on ${dateIndex}`;
            div.appendChild(span);

            /* update current date */
            currDate.setDate(currDate.getDate() + 1);

            /* update info for the next month label */
            if (monthIndex != currDate.getMonth() + 1) {
                printMonthLabel = true;
                monthIndex = currDate.getMonth() + 1;
            }
        }
        heatmap.appendChild(div);
    }
}
