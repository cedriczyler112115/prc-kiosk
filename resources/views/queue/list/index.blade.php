<div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0">Queue List</h4>
    <div class="text-secondary small">Browse and filter all queue tickets.</div>
  </div>

  <button class="btn btn-dark" type="button" id="queueListRefresh">
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
      id="queueListSearch"
      type="search"
      class="form-control form-control-sm"
      placeholder="Search queue/name…"
      style="max-width: 260px;"
      aria-label="Search queue list"
    >

    <input
      id="queueListDate"
      type="date"
      class="form-control form-control-sm"
      style="max-width: 165px;"
      aria-label="Date"
      value="{{ date('Y-m-d') }}"
    >

    <select id="queueListStatus" class="form-select form-select-sm" style="max-width: 170px;" aria-label="Status">
      <option value="">All status</option>
      <option value="waiting">Waiting</option>
      <option value="called">Called</option>
      <option value="serving">Serving</option>
      <option value="completed">Completed</option>
      <option value="skipped">Skipped</option>
      <option value="cancelled">Cancelled</option>
      <option value="transferred">Transferred</option>
    </select>

    <select id="queueListTransaction" class="form-select form-select-sm" style="max-width: 220px;" aria-label="Transaction type">
      <option value="">All transactions</option>
      @foreach($transactions as $transaction)
        <option value="{{ $transaction->id }}">{{ $transaction->name }}</option>
      @endforeach
    </select>

    <select id="queueListPriority" class="form-select form-select-sm" style="max-width: 200px;" aria-label="Priority">
      <option value="">All priorities</option>
      @foreach($priorities as $priority)
        <option value="{{ $priority->id }}">{{ $priority->name }}</option>
      @endforeach
    </select>

    <select id="queueListCounter" class="form-select form-select-sm" style="max-width: 170px;" aria-label="Counter">
      <option value="">All counters</option>
      @foreach($counters as $counterId)
        <option value="{{ $counterId }}">Counter {{ $counterId }}</option>
      @endforeach
    </select>

    <button type="button" class="btn btn-outline-secondary btn-sm" id="queueListReset">
      Reset
    </button>
  </div>

  <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
    <div id="queueListPerPage"></div>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table
        id="queueListTable"
        class="table table-hover bordered align-middle mb-0 transactions-table"
        data-endpoint="{{ route('queue.list.data') }}"
        data-logs-endpoint="{{ route('queue.logs.data') }}"
      >
        <thead class="table-light">
          <tr>
            <th style="width: 160px;">Queue #</th>
            <th style="width: 230px;">Name</th>
            <th style="width: 170px;">Last Transaction</th>
            <th style="width: 150px;">Priority</th>
            <th style="width: 130px;">Counter</th>
            <th style="width: 150px;">Status</th>
            <th style="width: 190px;">Created</th>
            <th style="width: 150px;" class="text-end">Waiting Time</th>
            <th style="width: 150px;" class="text-end">Service Time</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 p-3">
    <div id="queueListStatusText" class="small text-secondary"></div>
    <div id="queueListPager"></div>
  </div>
</div>

<div class="modal fade" id="queueLogsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Queue Logs</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex gap-2 mb-2">
          <input type="search" id="qlmSearch" class="form-control form-control-sm" placeholder="Search logs">
          <select id="qlmStatus" class="form-select form-select-sm" style="max-width: 160px;">
            <option value="">All status</option>
            <option value="waiting">Waiting</option>
            <option value="called">Called</option>
            <option value="serving">Serving</option>
            <option value="completed">Completed</option>
            <option value="skipped">Skipped</option>
            <option value="cancelled">Cancelled</option>
            <option value="transferred">Transferred</option>
          </select>
        </div>
        <div id="qlmLoading" class="text-center py-4 d-none">
          <div class="spinner-border" role="status" aria-hidden="true"></div>
        </div>
        <div id="qlmError" class="alert alert-danger d-none" role="alert"></div>
        <div id="qlmList" class="list-group small"></div>
      </div>
      <div class="modal-footer d-flex justify-content-between">
        <div id="qlmMeta" class="text-secondary small"></div>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-outline-secondary btn-sm" id="qlmPrev">Prev</button>
          <button type="button" class="btn btn-outline-secondary btn-sm" id="qlmNext">Next</button>
        </div>
      </div>
    </div>
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

      function formatDuration(seconds) {
        const n = Number(seconds);
        if (!Number.isFinite(n) || n <= 0) return '—';
        const s = Math.floor(n);
        const h = Math.floor(s / 3600);
        const m = Math.floor((s % 3600) / 60);
        const r = s % 60;
        if (h > 0) return `${h}h ${m}m`;
        if (m > 0) return `${m}m ${r}s`;
        return `${r}s`;
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

      function todayStr() {
        const d = new Date();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        return `${d.getFullYear()}-${mm}-${dd}`;
      }

      $(function () {
        const txNameById = (function () {
          const map = {};
          @foreach($transactions as $transaction)
            map[{{ (int) $transaction->id }}] = {!! json_encode($transaction->name) !!};
          @endforeach
          return map;
        })();
        const $table = $('#queueListTable');
        if (!$table.length || typeof $.fn.paginatedTable !== 'function') return;

        const endpoint = String($table.data('endpoint') || '');

        const els = {
          tbody: $table.find('tbody'),
          search: $('#queueListSearch'),
          date: $('#queueListDate'),
          status: $('#queueListStatus'),
          transaction: $('#queueListTransaction'),
          priority: $('#queueListPriority'),
          counter: $('#queueListCounter'),
          reset: $('#queueListReset'),
          refresh: $('#queueListRefresh'),
        };

        $table.paginatedTable({
          url: endpoint,
          tbodySelector: 'tbody',
          pagerContainer: '#queueListPager',
          perPageContainer: '#queueListPerPage',
          statusContainer: '#queueListStatusText',
          searchInput: '#queueListSearch',
          loader: { type: 'skeleton' },
          perPage: { default: 10, options: [10, 25, 50, 100] },
          timeoutMs: 30000,
          requestData: function (state) {
            return {
              page: state.page,
              per_page: state.perPage,
              q: state.q,
              date: String(els.date.val() || ''),
              status: String(els.status.val() || ''),
              transaction_id: String(els.transaction.val() || ''),
              priority_id: String(els.priority.val() || ''),
              counter_id: String(els.counter.val() || ''),
            };
          },
          renderEmpty: function () {
            return '<tr><td colspan="9" class="text-center text-secondary py-4">No queue tickets found.</td></tr>';
          },
          renderRow: function (item) {
            const queueNumber = escHtml(item.queue_number || '');
            const name = escHtml(item.name || '-');
            const transaction = escHtml(item.transaction_name || '-');
            const priority = escHtml(item.priority_name || '-');
            const transferIcon = item.is_transfer ? ' &nbsp;&nbsp;&nbsp;<i class="bi bi-repeat text-primary" aria-label="Transfer" title="Transfer"></i>' : '';
            const counter = item.counter_id
              ? ('<span class="badge text-bg-secondary">Counter ' + escHtml(item.counter_id) + '</span>')
              : '<span class="text-secondary">—</span>';
            const status = getStatusBadge(item.status);
            const created = escHtml(formatTime(item.created_at));
            const waitTime = escHtml(formatDuration(item.waiting_time_seconds));
            const serviceTime = escHtml(formatDuration(item.service_time_seconds));

            return (
              '<tr>' +
                '<td class="fw-semibold" data-queue-id="' + escHtml(item.id) + '" data-queue-number="' + queueNumber + '">' + queueNumber + transferIcon + '</td>' +
                '<td>' + name + '</td>' +
                '<td class="text-secondary">' + transaction + '</td>' +
                '<td class="text-secondary">' + priority + '</td>' +
                '<td>' + counter + '</td>' +
                '<td>' + status + '</td>' +
                '<td class="text-secondary">' + created + '</td>' +
                '<td class="text-end text-secondary">' + waitTime + '</td>' +
                '<td class="text-end text-secondary">' + serviceTime + '</td>' +
              '</tr>'
            );
          },
        });

        const api = $table.data('paginatedTable');

        const logsEndpoint = String($table.data('logs-endpoint') || '');
        let qlmState = { queueId: null, page: 1, perPage: 10, q: '', status: '' };
        let qlmModal = null;

        function qlmSetLoading(isLoading) {
          $('#qlmLoading').toggleClass('d-none', !isLoading);
          $('#qlmError').toggleClass('d-none', true).text('');
          $('#qlmList').toggleClass('d-none', isLoading);
        }

        function qlmRender(list, meta) {
          const $list = $('#qlmList').empty();
          if (!list || list.length === 0) {
            $list.append('<div class="list-group-item text-secondary">No logs found.</div>');
          } else {
            list.forEach(function(item) {
              const ts = escHtml(item.created_at || '');
              const action = escHtml(item.action || '');
              const oldStatus = escHtml(item.old_status || '');
              const newStatus = escHtml(item.new_status || '');
              const userName = escHtml(item.user_name || '');
              let header = '<div class="d-flex justify-content-between"><div><strong>' + action + '</strong></div><div class="text-secondary">' + ts + '</div></div>';
              const sub = '<div class="text-secondary">Status: ' + oldStatus + ' → ' + newStatus + (userName ? (' • ' + userName) : '') + '</div>';
              let body = '';
              if ((item.action || '') === 'transfer_prioritized') {
                let json = null;
                try { json = item.remarks ? JSON.parse(item.remarks) : null; } catch (e) { json = null; }
                if (json && (json.from_transaction_id || json.to_transaction_id)) {
                  const fromId = parseInt(json.from_transaction_id, 10);
                  const toId = parseInt(json.to_transaction_id, 10);
                  const fromName = escHtml(txNameById[fromId] || ('Transaction #' + (isNaN(fromId) ? '' : fromId)));
                  const toName = escHtml(txNameById[toId] || ('Transaction #' + (isNaN(toId) ? '' : toId)));
                  header = '<div class="d-flex justify-content-between"><div><strong>Transfer Prioritized</strong></div><div class="text-secondary">' + ts + '</div></div>';
                  body =
                    '<div class="mt-1">' +
                      '<span class="badge text-bg-primary me-2">From: ' + fromName + '</span>' +
                      '<i class="bi bi-arrow-right me-2"></i>' +
                      '<span class="badge text-bg-primary">To: ' + toName + '</span>' +
                    '</div>';
                } else {
                  const remarks = escHtml(item.remarks || '');
                  body = remarks ? ('<pre class="mb-0 small" style="white-space: pre-wrap;word-break: break-word;">' + remarks + '</pre>') : '';
                }
              } else {
                const remarks = escHtml(item.remarks || '');
                body = remarks ? ('<pre class="mb-0 small" style="white-space: pre-wrap;word-break: break-word;">' + remarks + '</pre>') : '';
              }
              $list.append('<div class="list-group-item">' + header + sub + body + '</div>');
            });
          }
          const page = meta?.page ?? 1;
          const lastPage = meta?.last_page ?? 1;
          $('#qlmMeta').text('Page ' + page + ' of ' + lastPage + (meta?.total ? (' • ' + meta.total + ' logs') : ''));
          $('#qlmPrev').prop('disabled', page <= 1);
          $('#qlmNext').prop('disabled', page >= lastPage);
        }

        function qlmLoad() {
          if (!qlmState.queueId || !logsEndpoint) return;
          qlmSetLoading(true);
          const params = {
            page: qlmState.page,
            per_page: qlmState.perPage,
            q: qlmState.q,
            status: qlmState.status,
            queue_id: qlmState.queueId
          };
          $.getJSON(logsEndpoint, params)
            .done(function(res) {
              qlmRender(res.data, res.meta);
            })
            .fail(function(xhr) {
              const msg = xhr?.responseJSON?.message || 'Failed to load logs.';
              $('#qlmError').removeClass('d-none').text(msg);
            })
            .always(function() {
              qlmSetLoading(false);
            });
        }

        function refreshToFirstPage() {
          api.state.page = 1;
          api.refresh();
        }

        els.date.on('change', function () { refreshToFirstPage(); });
        els.status.on('change', function () { refreshToFirstPage(); });
        els.transaction.on('change', function () { refreshToFirstPage(); });
        els.priority.on('change', function () { refreshToFirstPage(); });
        els.counter.on('change', function () { refreshToFirstPage(); });

        els.reset.on('click', function () {
          els.search.val('');
          els.status.val('');
          els.transaction.val('');
          els.priority.val('');
          els.counter.val('');
          els.date.val(todayStr());
          refreshToFirstPage();
        });

        els.refresh.on('click', function () {
          api.refresh();
        });

        $('#queueListTable').on('dblclick', 'tbody tr', function () {
          const $cells = $(this).find('td').first();
          const queueId = $cells.data('queue-id');
          const queueNumber = $cells.data('queue-number') || '';
          if (!queueId) return;
          qlmState.queueId = queueId;
          qlmState.page = 1;
          qlmState.q = '';
          qlmState.status = '';
          $('#qlmSearch').val('');
          $('#qlmStatus').val('');
          $('#queueLogsModal .modal-title').text('Queue Logs: ' + queueNumber);
          if (!qlmModal) {
            qlmModal = new bootstrap.Modal(document.getElementById('queueLogsModal'));
          }
          qlmModal.show();
          qlmLoad();
        });

        $('#qlmPrev').on('click', function () {
          if (qlmState.page > 1) {
            qlmState.page -= 1;
            qlmLoad();
          }
        });
        $('#qlmNext').on('click', function () {
          qlmState.page += 1;
          qlmLoad();
        });
        $('#qlmSearch').on('input', function () {
          qlmState.q = String($(this).val() || '');
          qlmState.page = 1;
          qlmLoad();
        });
        $('#qlmStatus').on('change', function () {
          qlmState.status = String($(this).val() || '');
          qlmState.page = 1;
          qlmLoad();
        });

        $table.on('data:error', function () {
          els.tbody.html('<tr><td colspan="9" class="text-center text-danger py-4">Failed to load data. Please try again.</td></tr>');
        });
      });
    })();
  </script>
@endpush
