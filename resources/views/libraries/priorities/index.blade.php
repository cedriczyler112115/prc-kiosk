@extends('layouts.app')

@section('title', 'Priorities')

@section('content')
  <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
    <div>
      <h4 class="mb-0">Priorities</h4>
      <div class="text-secondary small">Manage priority types used by the queue system.</div>
    </div>

    <button class="btn btn-dark" type="button" data-bs-toggle="modal" data-bs-target="#createPriorityModal">
      <i class="bi bi-plus-lg me-1"></i>
      New
    </button>
  </div>

  <style>
    .priorities-table th,
    .priorities-table td {
      padding: .75rem 1rem;
    }
    .priorities-table {
      border-collapse: separate;
      border-spacing: 0 .35rem;
    }
    .priorities-table tbody tr {
      background: var(--bs-body-bg);
    }
  </style>

  <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-2">
    <div class="d-flex align-items-center gap-2">
      <input
        id="prioritiesSearch"
        type="search"
        class="form-control form-control-sm"
        placeholder="Search name/code…"
        style="max-width: 260px;"
        aria-label="Search priorities"
      >
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
      <div id="prioritiesPerPage"></div>
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table
          id="prioritiesTable"
          class="table table-hover align-middle mb-0 priorities-table"
          data-endpoint="{{ route('libraries.priorities.data') }}"
          data-destroy-base="{{ url('/libraries/priorities') }}"
          data-csrf="{{ csrf_token() }}"
        >
          <thead class="table-light">
            <tr>
              <th style="width: 80px;">ID</th>
              <th style="width: 90px;">Level</th>
              <th>Name</th>
              <th style="width: 140px;">Code</th>
              <th style="width: 120px;">Active</th>
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
      <div id="prioritiesStatus" class="small text-secondary"></div>
      <div id="prioritiesPager"></div>
    </div>
  </div>

  <div class="modal fade" id="createPriorityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <form method="POST" action="{{ route('libraries.priorities.store') }}">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title">New Priority</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" required maxlength="100" value="{{ old('name') }}">
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
                <label class="form-label">Priority level</label>
                <input type="number" name="priority_level" class="form-control @error('priority_level') is-invalid @enderror" min="1" value="{{ old('priority_level', 1) }}">
                @error('priority_level')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6 d-flex align-items-end">
                <div class="form-check">
                  <input type="hidden" name="is_active" value="0">
                  <input class="form-check-input" type="checkbox" value="1" id="create_is_active_priority" name="is_active" {{ old('is_active', 1) ? 'checked' : '' }}>
                  <label class="form-check-label" for="create_is_active_priority">Active</label>
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

  <div class="modal fade" id="editPriorityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <form method="POST" id="editPriorityForm" action="#">
          @csrf
          @method('PUT')
          <div class="modal-header">
            <h5 class="modal-title">Edit Priority</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Name</label>
                <input type="text" name="name" id="edit_name" class="form-control" required maxlength="100">
              </div>
              <div class="col-md-3">
                <label class="form-label">Code</label>
                <input type="text" name="code" id="edit_code" class="form-control" required maxlength="20">
              </div>
              <div class="col-md-3">
                <label class="form-label">Priority level</label>
                <input type="number" name="priority_level" id="edit_priority_level" class="form-control" required min="1">
              </div>
              <div class="col-md-6 d-flex align-items-end">
                <div class="form-check">
                  <input type="hidden" name="is_active" value="0">
                  <input class="form-check-input" type="checkbox" value="1" id="edit_is_active" name="is_active">
                  <label class="form-check-label" for="edit_is_active">Active</label>
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
@endsection

@push('scripts')
  <script>
    (function () {
      const editModal = document.getElementById('editPriorityModal');
      if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
          const button = event.relatedTarget;
          if (!button) return;

          const id = button.getAttribute('data-id');

          const form = document.getElementById('editPriorityForm');
          form.action = "{{ url('/libraries/priorities') }}/" + id;

          document.getElementById('edit_name').value = button.getAttribute('data-name') || '';
          document.getElementById('edit_code').value = button.getAttribute('data-code') || '';
          document.getElementById('edit_priority_level').value = button.getAttribute('data-priority_level') || 1;
          document.getElementById('edit_is_active').checked = button.getAttribute('data-is_active') === '1';
        });
      }

      $(function () {
        const $table = $('#prioritiesTable');
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
          pagerContainer: '#prioritiesPager',
          perPageContainer: '#prioritiesPerPage',
          statusContainer: '#prioritiesStatus',
          searchInput: '#prioritiesSearch',
          loader: { type: 'skeleton' },
          perPage: { default: 10, options: [10, 25, 50, 100] },
          timeoutMs: 30000,
          renderEmpty: function () {
            return '<tr><td colspan="8" class="text-center text-secondary py-4">No priorities found.</td></tr>';
          },
          renderRow: function (p) {
            const id = p.id;

            const isActiveBadge = p.is_active
              ? '<span class="badge text-bg-success">Active</span>'
              : '<span class="badge text-bg-secondary">Inactive</span>';

            const editButton =
              '<button type="button" class="btn btn-sm btn-outline-secondary" ' +
                'data-bs-toggle="modal" data-bs-target="#editPriorityModal" ' +
                'data-id="' + escAttr(id) + '" ' +
                'data-name="' + escAttr(p.name) + '" ' +
                'data-code="' + escAttr(p.code) + '" ' +
                'data-priority_level="' + escAttr(p.priority_level) + '" ' +
                'data-is_active="' + (p.is_active ? '1' : '0') + '"' +
              '>Edit</button>';

            const deleteForm =
              '<form method="POST" action="' + escAttr(destroyBase) + '/' + escAttr(id) + '" class="d-inline" data-confirm="Delete this priority?">' +
                '<input type="hidden" name="_token" value="' + escAttr(csrf) + '">' +
                '<input type="hidden" name="_method" value="DELETE">' +
                '<button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>' +
              '</form>';

            return (
              '<tr>' +
                '<td class="text-secondary">' + escHtml(id) + '</td>' +
                '<td class="text-secondary">' + escHtml(p.priority_level) + '</td>' +
                '<td class="fw-semibold">' + escHtml(p.name) + '</td>' +
                '<td><span class="badge text-bg-secondary">' + escHtml(p.code) + '</span></td>' +
                '<td>' + isActiveBadge + '</td>' +
                '<td class="text-secondary">' + escHtml(p.created_at || '') + '</td>' +
                '<td class="text-secondary">' + escHtml(p.updated_at || '') + '</td>' +
                '<td class="text-end">' + editButton + ' ' + deleteForm + '</td>' +
              '</tr>'
            );
          },
        });
      });
    })();
  </script>
@endpush
