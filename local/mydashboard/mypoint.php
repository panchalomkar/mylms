<?php 
require_once('../../config.php');
require_once 'lib.php';
global $DB, $USER;

require_login();

$points_log = get_my_points_log($USER->id);
?>

<div class="bg-white shadow-lg rounded-lg overflow-hidden p-4">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-semibold text-gray-700">My Points Logs</h3>
    </div>

    <div class="flex justify-between items-center mb-3">
        <div class="flex space-x-2">
            <button class="px-3 py-1 bg-gray-200 rounded text-sm copyBtn d-none">
                <span class="material-symbols-rounded d-none"style="font-size: 14px;">content_copy</span> Copy
            </button>
            <button class="px-3 py-1 bg-gray-200 rounded text-sm csvBtn">
                <span class="material-symbols-rounded"style="font-size: 14px;">file_download</span> CSV
            </button>
            <button class="px-3 py-1 bg-gray-200 rounded text-sm excelBtn d-none">
                <span class="material-symbols-rounded d-none"style="font-size: 14px;">grid_view</span> Excel
            </button>
            <button class="px-3 py-1 bg-gray-200 rounded text-sm pdfBtn ">
                <span class="material-symbols-rounded"style="font-size: 14px;">picture_as_pdf</span> PDF
            </button>
            <button class="px-3 py-1 bg-gray-200 rounded text-sm printBtn d-none">
                <span class="material-symbols-rounded d-none"style="font-size: 14px;">print</span> Print
            </button>
        </div>
        <input type="text" id="searchInput" placeholder="Search..." class="border rounded px-3 py-1 text-sm w-1/3">
    </div>

    <div class="overflow-x-auto">
        <table id="pointsTable" class="min-w-full divide-y divide-gray-200 ">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Emp ID</th>
                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Fullname</th>
                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Email</th>
                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Point Type</th>
                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Action</th>
                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Points</th>
                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Date Time</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200 ">
                <?php foreach ($points_log as $log): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm text-gray-700"><?= $log->username ?></td>
                    <td class="px-4 py-2 text-sm text-gray-700"><?= $log->firstname . ' ' . $log->lastname ?></td>
                    <td class="px-4 py-2 text-sm text-gray-500"><?= $log->email ?></td>
                    <td class="px-4 py-2 text-sm text-gray-700"><?= ucwords($log->point_type) ?></td>
                    <td class="px-4 py-2 text-sm">
                        <?php if($log->action === 'added'): ?>
                            <span class="px-2 py-1 rounded-full text-white bg-green-500 text-xs font-semibold capitalize"><?= $log->action ?></span>
                        <?php else: ?>
                            <span class="px-2 py-1 rounded-full text-white bg-red-500 text-xs font-semibold capitalize"><?= $log->action ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-2 text-sm text-gray-700"><?= $log->points ?></td>
                    <td class="px-4 py-2 text-sm text-gray-700"><?= date('d/m/Y, h:i a', $log->timecreated) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="flex justify-between mt-3 items-center">
        <div id="pagination" class="space-x-1"></div>
        <div id="pageInfo" class="text-sm text-gray-600"></div>
    </div>
</div>

<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" rel="stylesheet">
<script src="external/plugins/jquery/jquery.min.js"></script>

<script>
$(function() {
    const rowsPerPage = 10; // adjust as needed
    let currentPage = 1;
    const $table = $('#pointsTable');
    const $rows = $table.find('tbody tr');

    function getFilteredRows() {
        const val = $('#searchInput').val().toLowerCase();
        $rows.each(function() {
            const match = $(this).text().toLowerCase().includes(val);
            $(this).toggle(match);
        });
        return $rows.filter(':visible');
    }

    function renderTable(filteredRows) {
        filteredRows.hide();
        const totalPages = Math.ceil(filteredRows.length / rowsPerPage) || 1;
        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        filteredRows.slice(start, end).show();

        $('#pageInfo').text(`Showing ${start+1} to ${Math.min(end, filteredRows.length)} of ${filteredRows.length} entries`);
        renderPagination(filteredRows.length);
    }

    function renderPagination(filteredLength) {
        const $pagination = $('#pagination');
        $pagination.empty();
        const totalPages = Math.ceil(filteredLength / rowsPerPage) || 1;

        const $prev = $('<button>').text('Previous').addClass('px-3 py-1 border rounded text-sm');
        if(currentPage === 1) $prev.attr('disabled', true).addClass('opacity-50');
        $prev.click(function() {
            if(currentPage > 1) { currentPage--; renderTable(getFilteredRows()); }
        });
        $pagination.append($prev);

        for(let i = 1; i <= totalPages; i++) {
            const $btn = $('<button>').text(i).addClass('px-3 py-1 border rounded text-sm');
            if(i === currentPage) $btn.addClass('bg-blue-600 text-white');
            $btn.click(function() { currentPage = i; renderTable(getFilteredRows()); });
            $pagination.append($btn);
        }

        const $next = $('<button>').text('Next').addClass('px-3 py-1 border rounded text-sm');
        if(currentPage === totalPages) $next.attr('disabled', true).addClass('opacity-50');
        $next.click(function() {
            if(currentPage < totalPages) { currentPage++; renderTable(getFilteredRows()); }
        });
        $pagination.append($next);
    }

    $('#searchInput').on('keyup', function() {
        currentPage = 1;
        const filteredRows = getFilteredRows();
        renderTable(filteredRows);
    });

    renderTable($rows);

    // Download buttons
    $('.csvBtn').click(() => { window.location.href = 'download_points.php?type=csv'; });
    $('.excelBtn').click(() => { window.location.href = 'download_points.php?type=excel'; });
    $('.pdfBtn').click(() => { window.location.href = 'download_points.php?type=pdf'; });

    // Copy
    $('.copyBtn').click(() => {
        const visibleRows = $rows.filter(':visible');
        let text = '';
        visibleRows.each(function() {
            const row = [];
            $(this).find('td').each(function(){ row.push($(this).text().trim()); });
            text += row.join("\t") + "\n";
        });
        navigator.clipboard.writeText(text);
        alert('Copied to clipboard');
    });

    // Print
    $('.printBtn').click(() => {
        const visibleRows = $rows.filter(':visible');
        const clonedTable = $table.clone();
        clonedTable.find('tbody tr').not(':visible').remove();
        const win = window.open('', '');
        win.document.write(clonedTable[0].outerHTML);
        win.print();
        win.close();
    });


});
</script>
