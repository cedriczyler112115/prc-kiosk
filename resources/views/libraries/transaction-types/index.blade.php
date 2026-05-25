  <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
    <div>
      <h4 class="mb-0">Transaction Types</h4>
      <div class="text-secondary small">Manage transaction types used by the queue system.</div>
    </div>

    <button class="btn btn-dark" type="button" data-bs-toggle="modal" data-bs-target="#createTransactionModal">
      <i class="bi bi-plus-lg me-1"></i>
      New
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
    <div class="d-flex align-items-center gap-2">
      <input
        id="transactionTypesSearch"
        type="search"
        class="form-control form-control-sm"
        placeholder="Search name/code…"
        style="max-width: 260px;"
        aria-label="Search transaction types"
      >
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
      <div id="transactionTypesPerPage"></div>
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table
          id="transactionTypesTable"
          class="table table-hover align-middle mb-0 transactions-table"
          data-endpoint="{{ route('libraries.transaction-types.data') }}"
          data-destroy-base="{{ url('/libraries/transaction-types') }}"
          data-csrf="{{ csrf_token() }}"
        >
          <thead class="table-light">
            <tr>
              <th style="width: 80px;">ID</th>
              <th style="width: 70px;">Order</th>
              <th>Name</th>
              <th style="width: 140px;">Code</th>
              <th>Description</th>
              <th style="width: 320px;">Flags</th>
              <th style="width: 160px;">Created</th>
              <th style="width: 160px;">Updated</th>
              <th style="width: 160px;" class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 p-3">
      <div id="transactionTypesStatus" class="small text-secondary"></div>
      <div id="transactionTypesPager"></div>
    </div>
  </div>

  <div class="modal fade" id="createTransactionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <form method="POST" action="{{ route('libraries.transaction-types.store') }}">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title">New Transaction Type</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" required maxlength="150" value="{{ old('name') }}">
                @error('name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-3">
                <label class="form-label">Code</label>
                <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" required maxlength="20" value="{{ old('code') }}">
                @error('code')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-3">
                <label class="form-label">Workflow order</label>
                <input type="number" name="workflow_order" class="form-control" min="1" value="{{ old('workflow_order', 1) }}">
              </div>
              <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
              </div>
              <div class="col-12">
                <div class="d-flex flex-wrap gap-3">
                  <div class="form-check">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input" type="checkbox" value="1" id="create_is_active" name="is_active" {{ old('is_active', 1) ? 'checked' : '' }}>
                    <label class="form-check-label" for="create_is_active">Active</label>
                  </div>
                  <div class="form-check">
                    <input type="hidden" name="transfer_allowed" value="0">
                    <input class="form-check-input" type="checkbox" value="1" id="create_transfer_allowed" name="transfer_allowed" {{ old('transfer_allowed', 1) ? 'checked' : '' }}>
                    <label class="form-check-label" for="create_transfer_allowed">Transfer allowed</label>
                  </div>
                  <div class="form-check">
                    <input type="hidden" name="priority_enabled" value="0">
                    <input class="form-check-input" type="checkbox" value="1" id="create_priority_enabled" name="priority_enabled" {{ old('priority_enabled', 1) ? 'checked' : '' }}>
                    <label class="form-check-label" for="create_priority_enabled">Priority enabled</label>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-dark">Create</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="editTransactionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <form method="POST" id="editTransactionForm" action="#">
          @csrf
          @method('PUT')
          <div class="modal-header">
            <h5 class="modal-title">Edit Transaction Type</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Name</label>
                <input type="text" name="name" id="edit_name" class="form-control" required maxlength="150">
              </div>
              <div class="col-md-3">
                <label class="form-label">Code</label>
                <input type="text" name="code" id="edit_code" class="form-control" required maxlength="20">
              </div>
              <div class="col-md-3">
                <label class="form-label">Workflow order</label>
                <input type="number" name="workflow_order" id="edit_workflow_order" class="form-control" min="1">
              </div>
              <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
              </div>
              <div class="col-12">
                <div class="d-flex flex-wrap gap-3">
                  <div class="form-check">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input" type="checkbox" value="1" id="edit_is_active" name="is_active">
                    <label class="form-check-label" for="edit_is_active">Active</label>
                  </div>
                  <div class="form-check">
                    <input type="hidden" name="transfer_allowed" value="0">
                    <input class="form-check-input" type="checkbox" value="1" id="edit_transfer_allowed" name="transfer_allowed">
                    <label class="form-check-label" for="edit_transfer_allowed">Transfer allowed</label>
                  </div>
                  <div class="form-check">
                    <input type="hidden" name="priority_enabled" value="0">
                    <input class="form-check-input" type="checkbox" value="1" id="edit_priority_enabled" name="priority_enabled">
                    <label class="form-check-label" for="edit_priority_enabled">Priority enabled</label>
                  </div>
                </div>
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

@push('scripts')
  <script>
    (function () {
      const editModal = document.getElementById('editTransactionModal');
      if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
          const button = event.relatedTarget;
          if (!button) return;

          const id = button.getAttribute('data-id');

          const form = document.getElementById('editTransactionForm');
          form.action = "{{ url('/libraries/transaction-types') }}/" + id;

          document.getElementById('edit_name').value = button.getAttribute('data-name') || '';
          document.getElementById('edit_code').value = button.getAttribute('data-code') || '';
          document.getElementById('edit_description').value = button.getAttribute('data-description') || '';
          document.getElementById('edit_workflow_order').value = button.getAttribute('data-workflow_order') || 1;

          document.getElementById('edit_is_active').checked = button.getAttribute('data-is_active') === '1';
          document.getElementById('edit_transfer_allowed').checked = button.getAttribute('data-transfer_allowed') === '1';
          document.getElementById('edit_priority_enabled').checked = button.getAttribute('data-priority_enabled') === '1';
        });
      }

      $(function () {
        const $table = $('#transactionTypesTable');
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

        function limit(value, maxLen) {
          const s = value == null ? '' : String(value);
          if (s.length <= maxLen) return s;
          return s.slice(0, Math.max(0, maxLen - 1)) + '…';
        }

        $table.paginatedTable({
          url: endpoint,
          tbodySelector: 'tbody',
          pagerContainer: '#transactionTypesPager',
          perPageContainer: '#transactionTypesPerPage',
          statusContainer: '#transactionTypesStatus',
          searchInput: '#transactionTypesSearch',
          loader: { type: 'skeleton' },
          perPage: { default: 10, options: [10, 25, 50, 100] },
          timeoutMs: 30000,
          renderEmpty: function () {
            return '<tr><td colspan="9" class="text-center text-secondary py-4">No transaction types found.</td></tr>';
          },
          renderRow: function (t) {
            const id = t.id;
            const name = escHtml(t.name);
            const code = escHtml(t.code);
            const description = escHtml(limit(t.description, 80));
            const workflowOrder = escHtml(t.workflow_order);

            const isActiveBadge = t.is_active
              ? '<span class="badge text-bg-success">Active</span>'
              : '<span class="badge text-bg-secondary">Inactive</span>';

            const transferBadge = t.transfer_allowed
              ? '<span class="badge text-bg-primary">Transfer Allowed</span>'
              : '<span class="badge text-bg-secondary">Transfer Not Allowed</span>';

            const priorityBadge = t.priority_enabled
              ? '<span class="badge text-bg-warning">Priority Enabled</span>'
              : '<span class="badge text-bg-secondary">Priority Disabled</span>';

            const createdAt = escHtml(t.created_at || '');
            const updatedAt = escHtml(t.updated_at || '');

            const editButton =
              '<button type="button" class="btn btn-sm btn-outline-secondary" ' +
                'data-bs-toggle="modal" data-bs-target="#editTransactionModal" ' +
                'data-id="' + escAttr(id) + '" ' +
                'data-name="' + escAttr(t.name) + '" ' +
                'data-code="' + escAttr(t.code) + '" ' +
                'data-description="' + escAttr(t.description || '') + '" ' +
                'data-workflow_order="' + escAttr(t.workflow_order) + '" ' +
                'data-is_active="' + (t.is_active ? '1' : '0') + '" ' +
                'data-transfer_allowed="' + (t.transfer_allowed ? '1' : '0') + '" ' +
                'data-priority_enabled="' + (t.priority_enabled ? '1' : '0') + '"' +
              '>Edit</button>';

            const deleteForm =
              '<form method="POST" action="' + escAttr(destroyBase) + '/' + escAttr(id) + '" class="d-inline" data-confirm="Delete this transaction type?">' +
                '<input type="hidden" name="_token" value="' + escAttr(csrf) + '">' +
                '<input type="hidden" name="_method" value="DELETE">' +
                '<button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>' +
              '</form>';

            return (
              '<tr>' +
                '<td class="text-secondary">' + escHtml(id) + '</td>' +
                '<td class="text-secondary">' + workflowOrder + '</td>' +
                '<td class="fw-semibold">' + name + '</td>' +
                '<td><span class="badge text-bg-secondary">' + code + '</span></td>' +
                '<td class="text-secondary">' + description + '</td>' +
                '<td>' + isActiveBadge + ' ' + transferBadge + ' ' + priorityBadge + '</td>' +
                '<td class="text-secondary">' + createdAt + '</td>' +
                '<td class="text-secondary">' + updatedAt + '</td>' +
                '<td class="text-end">' + editButton + ' ' + deleteForm + '</td>' +
              '</tr>'
            );
          },
        });
      });
    })();
  </script>
@endpush
