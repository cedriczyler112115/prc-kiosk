<div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0">Waiting List</h4>
    <div class="text-secondary small">Review and filter queued tickets.</div>
  </div>

  <a href="{{ route('queue.guard-entry') }}" class="btn btn-dark">
    <i class="bi bi-plus-lg me-1"></i>
    New Entry
  </a>
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
      id="guardSummarySearch"
      type="search"
      class="form-control form-control-sm"
      placeholder="Search queue/name…"
      style="max-width: 170px;"
      aria-label="Search waiting list"
    >

    <input id="guardSummaryDateFrom" type="date" class="form-control form-control-sm" style="max-width: 120px;" aria-label="From date">
    <input id="guardSummaryDateTo" type="date" class="form-control form-control-sm" style="max-width: 120px;" aria-label="To date">

    <select id="guardSummaryStatus" class="form-select form-select-sm" style="max-width: 130px;" aria-label="Status">
      <option value="">All status</option>
      <option value="waiting">Waiting</option>
      <option value="called">Called</option>
      <option value="serving">Serving</option>
      <option value="completed">Completed</option>
      <option value="skipped">Skipped</option>
      <option value="cancelled">Cancelled</option>
      <option value="transferred">Transferred</option>
    </select>

    <select id="guardSummaryTransaction" class="form-select form-select-sm" style="max-width: 150px;" aria-label="Transaction type">
      <option value="">All transactions</option>
      @foreach($transactions as $transaction)
        <option value="{{ $transaction->id }}">{{ $transaction->name }}</option>
      @endforeach
    </select>

    <select id="guardSummaryPriority" class="form-select form-select-sm" style="max-width: 150px;" aria-label="Priority">
      <option value="">All priorities</option>
      @foreach($priorities as $priority)
        <option value="{{ $priority->id }}">{{ $priority->name }}</option>
      @endforeach
    </select>

    <select id="guardSummaryCounter" class="form-select form-select-sm" style="max-width: 150px;" aria-label="Counter">
      <option value="">All counters</option>
      @foreach($counters as $counterId)
        <option value="{{ $counterId }}">Counter {{ $counterId }}</option>
      @endforeach
    </select>
      <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
    <div class="form-check form-switch m-0">
      <input class="form-check-input" type="checkbox" id="guardSummaryToggleWaitingToday" checked>
      <label class="form-check-label small fw-semibold" for="guardSummaryToggleWaitingToday">Waiting Today</label>
    </div>
    <button class="btn btn-outline-secondary btn-sm" type="button" id="guardSummaryReset">Reset</button>
    
  </div>
  </div>

<div id="guardSummaryPerPage"></div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table
        id="guardSummaryTable"
        class="table table-hover bordered align-middle mb-0 transactions-table"
        data-endpoint="{{ route('queue.guard-summary.data') }}"
      >
        <thead class="table-light">
          <tr>
            <th style="width: 160px;">Queue #</th>
            <th>Name</th>
            <th style="width: 220px;">Transaction</th>
            <th style="width: 200px;">Priority</th>
            <th style="width: 160px;">Counter</th>
            <th style="width: 160px;">Status</th>
            <th style="width: 170px;">Created</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 p-3">
    <div id="guardSummaryStatusText" class="small text-secondary"></div>
    <div id="guardSummaryPager"></div>
  </div>
</div>

@push('scripts')
  <script>
    (function () {
      $(function () {
        const $table = $('#guardSummaryTable');
        if (!$table.length || typeof $.fn.paginatedTable !== 'function') return;

        const endpoint = String($table.data('endpoint') || '');
        const urlParams = new URLSearchParams(window.location.search);

        const els = {
          toggle: $('#guardSummaryToggleWaitingToday'),
          search: $('#guardSummarySearch'),
          dateFrom: $('#guardSummaryDateFrom'),
          dateTo: $('#guardSummaryDateTo'),
          status: $('#guardSummaryStatus'),
          transaction: $('#guardSummaryTransaction'),
          priority: $('#guardSummaryPriority'),
          counter: $('#guardSummaryCounter'),
          reset: $('#guardSummaryReset'),
          tbody: $table.find('tbody'),
        };

        function todayStr() {
          return new Date().toISOString().slice(0, 10);
        }

        function escHtml(value) {
          return $('<div>').text(value == null ? '' : String(value)).html();
        }

        function initialToggle() {
          if (urlParams.has('toggle')) return urlParams.get('toggle') === '1';
          const stored = sessionStorage.getItem('guardSummary_toggleWaitingToday');
          if (stored !== null) return stored === 'true';
          return true;
        }

        function applyToggleState(isOn) {
          sessionStorage.setItem('guardSummary_toggleWaitingToday', isOn ? 'true' : 'false');

          if (isOn) {
            const today = todayStr();
            els.dateFrom.val(today).prop('disabled', true);
            els.dateTo.val(today).prop('disabled', true);
            els.status.val('waiting').prop('disabled', true);
          } else {
            els.dateFrom.prop('disabled', false);
            els.dateTo.prop('disabled', false);
            els.status.prop('disabled', false);
          }
        }

        function normalizeDateRange() {
          const from = String(els.dateFrom.val() || '');
          const to = String(els.dateTo.val() || '');
          if (from && to && from > to) {
            els.dateFrom.val(to);
            els.dateTo.val(from);
          }
        }

        const initial = {
          page: parseInt(String(urlParams.get('page') || ''), 10) || 1,
          perPage: [10, 25, 50, 100].includes(parseInt(String(urlParams.get('per_page') || ''), 10))
            ? parseInt(String(urlParams.get('per_page') || ''), 10)
            : 10,
          q: String(urlParams.get('q') || urlParams.get('search') || ''),
          date_from: String(urlParams.get('date_from') || ''),
          date_to: String(urlParams.get('date_to') || ''),
          status: String(urlParams.get('status') || ''),
          transaction_id: String(urlParams.get('transaction_id') || ''),
          priority_id: String(urlParams.get('priority_id') || ''),
          counter_id: String(urlParams.get('counter_id') || ''),
        };

        const toggleOn = initialToggle();
        els.toggle.prop('checked', toggleOn);

        els.search.val(initial.q);
        els.dateFrom.val(initial.date_from);
        els.dateTo.val(initial.date_to);
        els.status.val(initial.status);
        els.transaction.val(initial.transaction_id);
        els.priority.val(initial.priority_id);
        els.counter.val(initial.counter_id);

        applyToggleState(toggleOn);

        function getStatusBadge(status) {
          const map = {
            waiting: 'text-bg-warning',
            called: 'text-bg-info',
            serving: 'text-bg-primary',
            completed: 'text-bg-success',
            skipped: 'text-bg-secondary',
            cancelled: 'text-bg-danger',
            transferred: 'text-bg-dark',
          };
          const key = String(status || '').toLowerCase();
          const cls = map[key] || 'text-bg-secondary';
          return '<span class="badge ' + cls + '">' + escHtml(key.toUpperCase() || '-') + '</span>';
        }

        function formatTime(value) {
          if (!value) return '-';
          const d = new Date(value);
          if (Number.isNaN(d.getTime())) return '-';
          return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }

        $table.paginatedTable({
          url: endpoint,
          tbodySelector: 'tbody',
          pagerContainer: '#guardSummaryPager',
          perPageContainer: '#guardSummaryPerPage',
          statusContainer: '#guardSummaryStatusText',
          searchInput: '#guardSummarySearch',
          loader: { type: 'skeleton' },
          perPage: { default: initial.perPage, options: [10, 25, 50, 100] },
          initial: { page: initial.page, q: initial.q },
          cache: { enabled: false },
          requestData: function (ptState) {
            normalizeDateRange();
            return {
              page: ptState.page,
              per_page: ptState.perPage,
              q: ptState.q,
              date_from: String(els.dateFrom.val() || ''),
              date_to: String(els.dateTo.val() || ''),
              status: String(els.status.val() || ''),
              transaction_id: String(els.transaction.val() || ''),
              priority_id: String(els.priority.val() || ''),
              counter_id: String(els.counter.val() || ''),
            };
          },
          renderEmpty: function () {
            return '<tr><td colspan="7" class="text-center text-secondary py-4">No queue entries found.</td></tr>';
          },
          renderRow: function (item) {
            const queueNumber = escHtml(item.queue_number || '');
            const name = escHtml(item.name || '-');
            const transaction = item.transaction ? escHtml(item.transaction.name || '-') : '-';
            const priority = item.priority ? escHtml(item.priority.name || '-') : '-';
            const counter = item.counter_id ? ('<span class="badge text-bg-secondary">Counter ' + escHtml(item.counter_id) + '</span>') : '<span class="text-secondary">—</span>';
            const status = getStatusBadge(item.status);
            const created = escHtml(formatTime(item.created_at));

            return (
              '<tr>' +
                '<td class="fw-semibold">' + queueNumber + '</td>' +
                '<td>' + name + '</td>' +
                '<td class="text-secondary">' + transaction + '</td>' +
                '<td class="text-secondary">' + priority + '</td>' +
                '<td>' + counter + '</td>' +
                '<td>' + status + '</td>' +
                '<td class="text-secondary">' + created + '</td>' +
              '</tr>'
            );
          },
        });

        const api = $table.data('paginatedTable');

        function refreshToFirstPage() {
          api.state.page = 1;
          api.refresh();
        }

        els.dateFrom.on('change', function () { refreshToFirstPage(); });
        els.dateTo.on('change', function () { refreshToFirstPage(); });
        els.status.on('change', function () { refreshToFirstPage(); });
        els.transaction.on('change', function () { refreshToFirstPage(); });
        els.priority.on('change', function () { refreshToFirstPage(); });
        els.counter.on('change', function () { refreshToFirstPage(); });

        els.toggle.on('change', function () {
          applyToggleState($(this).is(':checked'));
          refreshToFirstPage();
        });

        els.reset.on('click', function () {
          els.search.val('');
          els.transaction.val('');
          els.priority.val('');
          els.counter.val('');

          if (els.toggle.is(':checked')) {
            const today = todayStr();
            els.dateFrom.val(today);
            els.dateTo.val(today);
            els.status.val('waiting');
          } else {
            els.dateFrom.val('');
            els.dateTo.val('');
            els.status.val('');
          }

          refreshToFirstPage();
        });

        $table.on('data:error', function () {
          els.tbody.html('<tr><td colspan="7" class="text-center text-danger py-4">Failed to load data. Please try again.</td></tr>');
        });

        $(document).on('keydown', function (e) {
          const $t = $(e.target);
          if ($t.is('input, textarea, select') || $t.prop('isContentEditable')) return;

          if (e.key === 'ArrowLeft') {
            e.preventDefault();
            api.state.page = Math.max(1, api.state.page - 1);
            api.refresh();
          } else if (e.key === 'ArrowRight') {
            e.preventDefault();
            api.state.page = Math.min(api.state.lastPage || api.state.page + 1, api.state.page + 1);
            api.refresh();
          } else if (e.key === 'Home') {
            e.preventDefault();
            api.state.page = 1;
            api.refresh();
          } else if (e.key === 'End') {
            e.preventDefault();
            api.state.page = api.state.lastPage || api.state.page;
            api.refresh();
          }
        });
      });
    })();
  </script>
@endpush

