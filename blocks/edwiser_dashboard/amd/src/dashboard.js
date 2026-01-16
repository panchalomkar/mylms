define([], function () {
    return {
        init: function () {

            // Donut chart
            const donut = document.getElementById('progressDonut');
            if (donut) {
                const ctx = donut.getContext('2d');
                const data = [35,25,20,12,8];
                let start = 0;

                const colors = ['#0f172a','#f97316','#3b82f6','#22c55e','#a855f7'];
                data.forEach((val, i) => {
                    ctx.beginPath();
                    ctx.moveTo(100,100);
                    ctx.fillStyle = colors[i];
                    ctx.arc(100,100,80,start,start + (val/100)*Math.PI*2);
                    ctx.fill();
                    start += (val/100)*Math.PI*2;
                });
            }

            // Line chart
            const line = document.getElementById('overviewChart');
            if (line) {
                const ctx = line.getContext('2d');
                ctx.beginPath();
                ctx.moveTo(0,80);
                ctx.lineTo(50,60);
                ctx.lineTo(100,40);
                ctx.lineTo(150,50);
                ctx.lineTo(200,30);
                ctx.strokeStyle = '#0f172a';
                ctx.stroke();
            }
        }
    };
});
