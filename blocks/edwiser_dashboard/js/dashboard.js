document.addEventListener('DOMContentLoaded', () => {

    /* ================= COURSE PROGRESS DONUT ================= */
    const donut = document.getElementById('progressDonut');
    if (donut && donut.dataset.distribution && typeof Chart !== 'undefined') {
        const raw = donut.dataset.distribution
            .split(',')
            .filter(Boolean)
            .map(Number);

        new Chart(donut, {
            type: 'doughnut',
            data: {
                labels: ['0–20%', '21–40%', '41–60%', '61–80%', '81–100%'],
                datasets: [{
                    data: raw,
                    backgroundColor: [
                        '#0f172a',
                        '#f97316',
                        '#3b82f6',
                        '#22c55e',
                        '#9333ea'
                    ],
                    borderWidth: 2,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                cutout: '65%',
                plugins: { legend: { display: false } }
            }
        });
    }

    /* ================= SITE OVERVIEW LINE ================= */
    const chart = document.getElementById('overviewChart');
    if (chart && typeof Chart !== 'undefined') {
        const activeUsers = JSON.parse(chart.dataset.trendactiveusers || '[]');
        const enrollments = JSON.parse(chart.dataset.trendenrollments || '[]');
        const completions = JSON.parse(chart.dataset.trendcompletions || '[]');

        new Chart(chart, {
            type: 'line',
            data: {
                labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul'],
                datasets: [
                    { label: 'Active Users', data: activeUsers, borderColor: '#0f172a', tension: 0.35 },
                    { label: 'Enrollments', data: enrollments, borderColor: '#f97316', tension: 0.35 },
                    { label: 'Completions', data: completions, borderColor: '#22c55e', tension: 0.35 }
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    /* ================= COURSE PROGRESS MODAL ================= */
    const modal = document.getElementById('cpModal');
    const body = document.getElementById('cpModalBody');

    if (!modal || !body) return;

    const courseid = modal.dataset.courseid?.trim();
    if (!courseid) {
        console.error('Modal courseid is empty!');
        return;
    }

    function openModal() {
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    // Close modal on clicking close button or backdrop
    const closeBtn = modal.querySelector('.close');
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', e => {
        if (e.target === modal) closeModal();
    });

    // Legend click
    document.querySelectorAll('.cp-legend').forEach(legend => {
        legend.addEventListener('click', async () => {
            const range = legend.dataset.range?.trim();
            if (!range) return;

            console.log('Fetching course progress', courseid, range);
            openModal();

            body.innerHTML = `
                <tr><td colspan="3" class="text-center text-muted">Loading...</td></tr>
            `;

            try {
                const res = await fetch(
                    `${M.cfg.wwwroot}/blocks/edwiser_dashboard/ajax/courseprogress_modal.php?course=${courseid}&range=${range}`
                );

                const data = await res.json();
                body.innerHTML = '';

                if (!data.rows || !data.rows.length) {
                    body.innerHTML = `
                        <tr><td colspan="3" class="text-center text-muted">No learners found</td></tr>
                    `;
                    return;
                }

                data.rows.forEach(row => {
                    body.insertAdjacentHTML('beforeend', `
                        <tr>
                            <td>${row[0]}</td>
                            <td>${row[1]}</td>
                            <td>${row[2]}%</td>
                        </tr>
                    `);
                });

            } catch (err) {
                console.error(err);
                body.innerHTML = `
                    <tr><td colspan="3" class="text-danger text-center">Failed to load data</td></tr>
                `;
            }
        });
    });

});
