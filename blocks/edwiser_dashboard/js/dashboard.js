// document.addEventListener('DOMContentLoaded', () => {

    

//     /* ================= COURSE PROGRESS DONUT ================= */
//     const donut = document.getElementById('progressDonut');
//     if (donut && donut.dataset.distribution && typeof Chart !== 'undefined') {
//         const raw = donut.dataset.distribution
//             .split(',')
//             .filter(Boolean)
//             .map(Number);

//         new Chart(donut, {
//             type: 'doughnut',
//             data: {
//                 labels: ['0–20%', '21–40%', '41–60%', '61–80%', '81–100%'],
//                 datasets: [{
//                     data: raw,
//                     backgroundColor: [
//                         '#0f172a',
//                         '#f97316',
//                         '#3b82f6',
//                         '#22c55e',
//                         '#9333ea'
//                     ],
//                     borderWidth: 2,
//                     hoverOffset: 10
//                 }]
//             },
//             options: {
//                 responsive: true,
//                 cutout: '65%',
//                 plugins: { legend: { display: false } }
//             }
//         });
//     }

//     /* ================= SITE OVERVIEW LINE ================= */
//     const chart = document.getElementById('overviewChart');
//     if (chart && typeof Chart !== 'undefined') {
//         const activeUsers = JSON.parse(chart.dataset.trendactiveusers || '[]');
//         const enrollments = JSON.parse(chart.dataset.trendenrollments || '[]');
//         const completions = JSON.parse(chart.dataset.trendcompletions || '[]');

//         new Chart(chart, {
//             type: 'line',
//             data: {
//                 labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul'],
//                 datasets: [
//                     { label: 'Active Users', data: activeUsers, borderColor: '#0f172a', tension: 0.35 },
//                     { label: 'Enrollments', data: enrollments, borderColor: '#f97316', tension: 0.35 },
//                     { label: 'Completions', data: completions, borderColor: '#22c55e', tension: 0.35 }
//                 ]
//             },
//             options: {
//                 responsive: true,
//                 plugins: { legend: { position: 'bottom' } },
//                 scales: { y: { beginAtZero: true } }
//             }
//         });
//     }



// });
