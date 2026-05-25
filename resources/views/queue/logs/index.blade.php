<div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0">Queue Logs</h4>
    <div class="text-secondary small">Review queue activity history.</div>
  </div>

  <button class="btn btn-dark" type="button" id="queueLogsRefresh">
    <i class="bi bi-arrow-clockwise me-1"></i>
    Refresh
  </button>
</div>

<style>
  .transactions-table th,
  .transactions-table td {
    padding: .75rem 1rem;
  }
  .transactions-table {
    border-collapse: separate;
    border-spacing: 0 .35rem;
  }
  .transactions-table tbody tr {
    background: var(--bs-body-bg);
  }
</style>

<div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-2">
  <div class="d-flex align-items-center gap-2 flex-wrap" style="width: 80%">
    <input
      id="queueLogsSearch"
      type="search"
      class="form-control form-control-sm"
      placeholder="Search action/remarks/queue…"
      style="max-width: 260px;"
      aria-label="Search queue logs"
    >

    <select id="queueLogsStatus" class="form-select form-select-sm" style="max-width: 170px;" aria-label="Status">
      <option value="">All status</option>
      <option value="waiting">Waiting</option>
      <option value="called">Called</option>
      <option value="serving">Serving</option>
      <option value="completed">Completed</option>
      <option value="skipped">Skipped</option>
      <option value="cancelled">Cancelled</option>
      <option value="transferred">Transferred</option>
    </select>

    <input id="queueLogsDateFrom" type="date" class="form-control form-control-sm" style="max-width: 165px;" aria-label="From date">
    <input id="queueLogsDateTo" type="date" class="form-control form-control-sm" style="max-width: 165px;" aria-label="To date">

    <button type="button" class="btn btn-outline-secondary btn-sm" id="queueLogsReset">
      Reset
    </button>
  </div>

  <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
    <div id="queueLogsPerPage"></div>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table
        id="queueLogsTable"
        class="table table-hover bordered align-middle mb-0 transactions-table"
        data-endpoint="{{ route('queue.logs.data') }}"
      >
        <thead class="table-light">
          <tr>
            <th style="width: 220px;">Timestamp</th>
            <th style="width: 110px;">Level</th>
            <th style="width: 160px;">Action</th>
            <th style="width: 140px;">Queue #</th>
            <th style="width: 150px;">Status</th>
            <th>Message / Remarks</th>
            <th style="width: 220px;">User</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 p-3">
    <div id="queueLogsStatusText" class="small text-secondary"></div>
    <div id="queueLogsPager"></div>
  </div>
</div>

@push('scripts')
  <script>
    (function () {
      function escHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
      }

      function formatTime(value) {
        if (!value) return '';
        const d = new Date(value);
        if (Number.isNaN(d.getTime())) return String(value);
        return d.toLocaleString();
      }

      function limit(value, maxLen) {
        const s = value == null ? '' : String(value);
        if (s.length <= maxLen) return s;
        return s.slice(0, Math.max(0, maxLen - 1)) + '…';
      }

      function getLevelBadge(level) {
        const l = (level || '').toString().toLowerCase();
        const label = escHtml(l ? l.toUpperCase() : 'INFO');
        const map = { info: 'text-bg-info', warning: 'text-bg-warning', error: 'text-bg-danger' };
        const cls = map[l] || 'text-bg-secondary';
        return '<span class="badge ' + cls + '">' + label + '</span>';
      }

      function getStatusBadge(status) {
        const s = (status || '').toString().toLowerCase();
        const label = escHtml(s ? s.toUpperCase() : '—');
        const map = {
          waiting: 'text-bg-warning',
          called: 'text-bg-info',
          serving: 'text-bg-primary',
          completed: 'text-bg-success',
          skipped: 'text-bg-secondary',
          cancelled: 'text-bg-danger',
          transferred: 'text-bg-dark',
        };
        const cls = map[s] || 'text-bg-secondary';
        return '<span class="badge ' + cls + '">' + label + '</span>';
      }

      $(function () {
        const $table = $('#queueLogsTable');
        if (!$table.length || typeof $.fn.paginatedTable !== 'function') return;

        const endpoint = String($table.data('endpoint') || '');

        const els = {
          tbody: $table.find('tbody'),
          search: $('#queueLogsSearch'),
          status: $('#queueLogsStatus'),
          dateFrom: $('#queueLogsDateFrom'),
          dateTo: $('#queueLogsDateTo'),
          reset: $('#queueLogsReset'),
          refresh: $('#queueLogsRefresh'),
        };

        $table.paginatedTable({
          url: endpoint,
          tbodySelector: 'tbody',
          pagerContainer: '#queueLogsPager',
          perPageContainer: '#queueLogsPerPage',
          statusContainer: '#queueLogsStatusText',
          searchInput: '#queueLogsSearch',
          loader: { type: 'skeleton' },
          perPage: { default: 10, options: [10, 25, 50, 100] },
          timeoutMs: 30000,
          requestData: function (state) {
            return {
              page: state.page,
              per_page: state.perPage,
              q: state.q,
              status: String(els.status.val() || ''),
              start_date: String(els.dateFrom.val() || ''),
              end_date: String(els.dateTo.val() || ''),
            };
          },
          renderEmpty: function () {
            return '<tr><td colspan="7" class="text-center text-secondary py-4">No logs found.</td></tr>';
          },
          renderRow: function (item) {
            const created = escHtml(formatTime(item.created_at));
            const level = getLevelBadge(item.level);
            const action = escHtml(item.action || '-');
            const queueNumber = escHtml(item.queue_number || '-');
            const status = getStatusBadge(item.new_status || item.status);
            const message = escHtml(limit(item.remarks || '', 180));
            const user = escHtml(item.user_name || '-');

            return (
              '<tr>' +
                '<td class="text-secondary">' + created + '</td>' +
                '<td>' + level + '</td>' +
                '<td class="fw-semibold">' + action + '</td>' +
                '<td>' + queueNumber + '</td>' +
                '<td>' + status + '</td>' +
                '<td class="text-secondary">' + message + '</td>' +
                '<td class="text-secondary">' + user + '</td>' +
              '</tr>'
            );
          },
        });

        const api = $table.data('paginatedTable');

        function refreshToFirstPage() {
          api.state.page = 1;
          api.refresh();
        }

        els.status.on('change', function () { refreshToFirstPage(); });
        els.dateFrom.on('change', function () { refreshToFirstPage(); });
        els.dateTo.on('change', function () { refreshToFirstPage(); });

        els.reset.on('click', function () {
          els.search.val('');
          els.status.val('');
          els.dateFrom.val('');
          els.dateTo.val('');
          refreshToFirstPage();
        });

        els.refresh.on('click', function () {
          api.refresh();
        });

        $table.on('data:error', function () {
          els.tbody.html('<tr><td colspan="7" class="text-center text-danger py-4">Failed to load data. Please try again.</td></tr>');
        });
      });
    })();
  </script>
@endpush

