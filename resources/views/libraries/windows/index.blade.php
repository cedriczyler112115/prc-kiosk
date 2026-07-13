@extends('layouts.app')

@section('title', 'Windows')

@section('content')
  <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
    <div>
      <h4 class="mb-0">User Assignments</h4>
      <div class="text-secondary small">Manage user assignments.</div>
    </div>

    <button class="btn btn-dark" type="button" data-bs-toggle="modal" data-bs-target="#createCounterModal">
      <i class="bi bi-plus-lg me-1"></i>
      New
    </button>
  </div>

  <style>
    .counters-table th,
    .counters-table td {
      padding: .75rem 1rem;
    }
    .counters-table {
      border-collapse: separate;
      border-spacing: 0 .35rem;
    }
    .counters-table tbody tr {
      background: var(--bs-body-bg);
    }
  </style>

  <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-2">
    <div class="d-flex align-items-center gap-2">
      <input
        id="countersSearch"
        type="search"
        class="form-control form-control-sm"
        placeholder="Search name/email/counter #…"
        style="max-width: 260px;"
        aria-label="Search assignments"
      >
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
      <div id="countersPerPage"></div>
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table
          id="countersTable"
          class="table table-hover align-middle mb-0 counters-table"
          data-endpoint="{{ route('libraries.windows.data') }}"
          data-destroy-base="{{ url('/libraries/windows') }}"
          data-csrf="{{ csrf_token() }}"
        >
          <thead class="table-light">
            <tr>
              <th style="width: 80px;">ID</th>
              <th style="width: 200px;">User</th>
              <th>Email</th>
              <th style="width: 140px;">Access Level</th>
              <th style="width: 180px;">Transaction</th>
              <th style="width: 100px;">Counter #</th>
              <th style="width: 140px;">Updated</th>
              <th style="width: 160px;" class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 p-3">
      <div id="countersStatus" class="small text-secondary"></div>
      <div id="countersPager"></div>
    </div>
  </div>

  <div class="modal fade" id="createCounterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <form method="POST" action="{{ route('libraries.windows.store') }}">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title">Assign Window</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">User</label>
                <select name="user_id" id="create_user_id" class="form-select @error('user_id') is-invalid @enderror" required aria-label="Select user">
                  <option value="" selected disabled>Select user…</option>
                  @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ (string) old('user_id') === (string) $u->id ? 'selected' : '' }}>
                      {{ $u->name }} ({{ $u->email }})
                    </option>
                  @endforeach
                </select>
                @error('user_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-12">
                <label class="form-label">Access Level</label>
                <select name="access_level_id" class="form-select @error('access_level_id') is-invalid @enderror" required>
                  <option value="" selected disabled>Select access level…</option>
                  @foreach($accessLevels as $level)
                    <option value="{{ $level->id }}" {{ (string) old('access_level_id') === (string) $level->id ? 'selected' : '' }}>
                      {{ $level->name }}
                    </option>
                  @endforeach
                </select>
                @error('access_level_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-8">
                <label class="form-label">Transaction (Optional)</label>
                <select name="transaction_id" id="create_transaction_id" class="form-select @error('transaction_id') is-invalid @enderror" aria-label="Select transaction">
                  <option value="">None (No Window)</option>
                  @foreach($transactions as $transaction)
                    <option value="{{ $transaction->id }}" {{ (string) old('transaction_id') === (string) $transaction->id ? 'selected' : '' }}>
                      {{ $transaction->name }}
                    </option>
                  @endforeach
                </select>
                @error('transaction_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-4">
                <label class="form-label">Counter number</label>
                <input type="number" name="counter_number" class="form-control @error('counter_number') is-invalid @enderror" min="1" value="{{ old('counter_number', 1) }}">
                @error('counter_number')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-dark">Assign</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="editCounterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <form method="POST" id="editCounterForm" action="#">
          @csrf
          @method('PUT')
          <div class="modal-header">
            <h5 class="modal-title">Edit Assignment</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">Access Level</label>
                <select name="access_level_id" id="edit_access_level_id" class="form-select" required>
                  @foreach($accessLevels as $level)
                    <option value="{{ $level->id }}">{{ $level->name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-8">
                <label class="form-label">Transaction (Optional)</label>
                <select name="transaction_id" id="edit_transaction_id" class="form-select" aria-label="Select transaction">
                  <option value="">None (No Window)</option>
                  @foreach($transactions as $transaction)
                    <option value="{{ $transaction->id }}">{{ $transaction->name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Counter number</label>
                <input type="text" name="counter_number" id="edit_counter_number" class="form-control" min="1">
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-dark">Save changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  <script>
    (function () {
      const $createModal = $('#createCounterModal');
      const $editModal = $('#editCounterModal');
      const $createUser = $('#create_user_id');
      const $createTransaction = $('#create_transaction_id');
      const $editTransaction = $('#edit_transaction_id');

      function initTransactionSelect($select, $modal) {
        if (!($select && $select.length)) return;
        if (typeof $select.select2 !== 'function') return;
        $select.select2({
          theme: 'bootstrap-5',
          width: '100%',
          dropdownParent: $modal && $modal.length ? $modal : $(document.body),
          placeholder: 'Select transaction…',
          allowClear: false
        });
      }

      initTransactionSelect($createTransaction, $createModal);
      initTransactionSelect($editTransaction, $editModal);

      if ($createModal.length) {
        $createModal.on('shown.bs.modal', function () {
          if ($createTransaction.length) {
            $createTransaction.trigger('focus');
          }
        });
      }

      const editModal = document.getElementById('editCounterModal');
      if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
          const button = event.relatedTarget;
          if (!button) return;

          const id = button.getAttribute('data-id');

          const form = document.getElementById('editCounterForm');
          form.action = "{{ url('/libraries/windows') }}/" + id;

          const transactionId = button.getAttribute('data-transaction_id') || '';
          document.getElementById('edit_counter_number').value = button.getAttribute('data-counter_number') || '';
          document.getElementById('edit_access_level_id').value = button.getAttribute('data-access_level_id');

          const $transactionSelect = $('#edit_transaction_id');
          if ($transactionSelect.length) {
            $transactionSelect.val(transactionId).trigger('change');
          }
        });
      }

      $(function () {
        const $table = $('#countersTable');
        if (!$table.length || typeof $.fn.paginatedTable !== 'function') return;

        const endpoint = String($table.data('endpoint') || '');
        const destroyBase = String($table.data('destroy-base') || '');
        const csrf = String($table.data('csrf') || '');

        function escHtml(value) {
          return $('<div>').text(value == null ? '' : String(value)).html();
        }

        function escAttr(value) {
          return (value == null ? '' : String(value))
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
        }

        $table.paginatedTable({
          url: endpoint,
          tbodySelector: 'tbody',
          pagerContainer: '#countersPager',
          perPageContainer: '#countersPerPage',
          statusContainer: '#countersStatus',
          searchInput: '#countersSearch',
          loader: { type: 'skeleton' },
          perPage: { default: 10, options: [10, 25, 50, 100] },
          timeoutMs: 30000,
          renderEmpty: function () {
            return '<tr><td colspan="7" class="text-center text-secondary py-4">No assignments found.</td></tr>';
          },
          renderRow: function (c) {
            const id = c.id;
            const transactionName = c.transaction_name || (c.transaction_id ? '#' + String(c.transaction_id) : '');
            const counterNumber = c.counter_number == null ? '' : String(c.counter_number);
            const accessLevel = c.access_level_name || 'N/A';

            const editButton =
              '<button type="button" class="btn btn-sm btn-outline-secondary" ' +
                'data-bs-toggle="modal" data-bs-target="#editCounterModal" ' +
                'data-id="' + escAttr(id) + '" ' +
                'data-transaction_id="' + escAttr(c.transaction_id) + '" ' +
                'data-counter_number="' + escAttr(counterNumber) + '" ' +
                'data-access_level_id="' + escAttr(c.access_level_id) + '" ' +
              '>Edit</button>';

            const deleteForm =
              '<form method="POST" action="' + escAttr(destroyBase) + '/' + escAttr(id) + '" class="d-inline" data-confirm="Unassign this window?">' +
                '<input type="hidden" name="_token" value="' + escAttr(csrf) + '">' +
                '<input type="hidden" name="_method" value="DELETE">' +
                '<button type="submit" class="btn btn-sm btn-outline-danger">Unassign</button>' +
              '</form>';

            return (
              '<tr>' +
                '<td class="text-secondary">' + escHtml(id) + '</td>' +
                '<td class="fw-semibold">' + escHtml(c.name) + '</td>' +
                '<td class="text-secondary">' + escHtml(c.email || '') + '</td>' +
                '<td class="text-secondary">' + escHtml(accessLevel) + '</td>' +
                '<td class="text-secondary">' + escHtml(transactionName) + '</td>' +
                '<td class="text-secondary">' + escHtml(counterNumber) + '</td>' +
                '<td class="text-secondary">' + escHtml(c.updated_at || '') + '</td>' +
                '<td class="text-end">' + editButton + ' ' + deleteForm + '</td>' +
              '</tr>'
            );
          },
        });
      });
    })();
  </script>
@endpush
