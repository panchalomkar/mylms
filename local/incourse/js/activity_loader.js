document.addEventListener("DOMContentLoaded", function () {
    const mainContent = document.getElementById("main-content");
    const leftPanel = document.getElementById("course-index");

    if (!leftPanel || !mainContent) return;

    leftPanel.addEventListener("click", function (e) {
        const link = e.target.closest("a.activity-link");
        if (!link) return;

        e.preventDefault();

        const url = new URL(link.href, window.location.origin);
        const moduleid = url.searchParams.get("id");
        const courseid = new URLSearchParams(window.location.search).get("id");

        if (!moduleid || !courseid) return;

        mainContent.innerHTML = '<div class="text-center py-5 text-muted">Loading activity...</div>';

        fetch(`${M.cfg.wwwroot}/local/incourse/viewactivity.php?courseid=${courseid}&moduleid=${moduleid}`)
            .then(resp => resp.text())
            .then(html => {
                mainContent.innerHTML = html;
                history.pushState({}, '', `?id=${courseid}&moduleid=${moduleid}`);
            })
            .catch(err => {
                console.error(err);
                mainContent.innerHTML = '<div class="text-danger p-4">Failed to load activity.</div>';
            });
    });
});
