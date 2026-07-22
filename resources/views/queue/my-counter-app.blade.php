<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Counter {{ $user->counter_id }} – App Mode</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --counter-card-height: calc(100vh - 88px); /* full height minus info-bar */
        }

        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
            background: #f4f6f9;
        }

        /* ── Top info bar ─────────────────────────────────────── */
        #app-info-bar {
            background: #fff;
            border-bottom: 1px solid #dee2e6;
            padding: 6px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-shrink: 0;
            height: 44px;
        }

        #app-info-bar .info-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.72rem;
            color: #6c757d;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 20px;
            padding: 2px 10px;
        }

        #app-info-bar .info-pill i {
            font-size: 0.8rem;
        }

        /* ── Layout ───────────────────────────────────────────── */
        #app-body {
            display: flex;
            height: calc(100vh - 44px);
            overflow: hidden;
        }

        /* ── Column widths ────────────────────────────────────── */
        .col-skipped  { width: 22%; flex-shrink: 0; }
        .col-current  { flex: 1 1 auto; }
        .col-next     { width: 22%; flex-shrink: 0; }

        /* ── Cards ────────────────────────────────────────────── */
        .app-card {
            display: flex;
            flex-direction: column;
            height: 100%;
            background: #fff;
            border-right: 1px solid #dee2e6;
            overflow: hidden;
        }

        .col-next .app-card {
            border-right: none;
            border-left: 1px solid #dee2e6;
        }

        .col-current .app-card {
            border: none;
        }

        .app-card-header {
            flex-shrink: 0;
            padding: 10px 14px;
            border-bottom: 1px solid #dee2e6;
            background: #fff;
            font-size: 0.85rem;
            font-weight: 600;
            color: #495057;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .app-card-body {
            flex: 1 1 auto;
            overflow-y: auto;
            min-height: 0;
        }

        /* ── Current transaction centre panel ─────────────────── */
        #current-ticket-panel {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            height: 100%;
            padding: 2rem;
        }

        /* ── Action buttons footer ────────────────────────────── */
        #action-footer {
            flex-shrink: 0;
            padding: 8px 12px;
            border-top: 1px solid #dee2e6;
            background: #f8f9fa;
        }

        #action-buttons {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 6px;
        }

        #action-buttons .btn {
            font-size: 0.82rem;
            padding: 6px 14px;
        }

        /* ── SSE badge ────────────────────────────────────────── */
        #connection-status {
            font-size: 0.7rem;
        }

        /* Skipped clickable rows */
        .clickable { cursor: pointer; }
        .clickable:hover { background: #f0f4ff !important; }

        /* Waiting list scroll */
        .waiting-list-scroll {
            overflow-y: auto;
        }
    </style>
</head>
<body>

    {{-- ── Info bar ─────────────────────────────────────────── --}}
    <div id="app-info-bar">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="info-pill">
                <i class="bi bi-window-sidebar"></i>
                Counter {{ $user->counter_id ?? '—' }}
            </span>
            <span class="info-pill">
                <i class="bi bi-tag"></i>
                {{ $transaction ? $transaction->name : 'No Transaction' }}
            </span>
            <span class="info-pill">
                <i class="bi bi-person-badge"></i>
                {{ $user->name }}
            </span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span id="connection-status" class="badge bg-success rounded-pill px-3 py-2">
                <span class="spinner-grow spinner-grow-sm me-1" role="status" aria-hidden="true"
                      style="width: 0.5rem; height: 0.5rem;"></span>
                Live
            </span>
        </div>
    </div>

    {{-- ── Main body ────────────────────────────────────────── --}}
    <div id="app-body">

        {{-- Skipped list --}}
        <div class="col-skipped">
            <div class="app-card">
                <div class="app-card-header">
                    <span>Skipped</span>
                </div>
                <div class="app-card-body">
                    <ul class="list-group list-group-flush" id="status-list" aria-live="polite" aria-relevant="additions text">
                        <li class="list-group-item text-center py-3 text-muted bg-transparent" style="font-size:0.82rem;">
                            None
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Current transaction --}}
        <div class="col-current">
            <div class="app-card">
                <div class="app-card-header">
                    <span>Current Transaction</span>
                </div>
                <div class="app-card-body d-flex flex-column" style="overflow:hidden;">
                    <div id="current-ticket-panel">
                        <div class="text-muted">
                            <div class="spinner-border text-primary mb-3" role="status"></div>
                            <p>Loading queue status…</p>
                        </div>
                    </div>
                </div>
                <div id="action-footer">
                    <div id="action-buttons">
                        {{-- populated by JS --}}
                    </div>
                </div>
            </div>
        </div>

        {{-- Next in line --}}
        <div class="col-next">
            <div class="app-card">
                <div class="app-card-header">
                    <span>Next</span>
                    <span class="badge bg-primary rounded-pill" style="font-size:0.7rem;">
                        Waiting: <span id="waiting-count">0</span>
                    </span>
                </div>
                <div class="app-card-body">
                    <ul class="list-group list-group-flush waiting-list-scroll" id="waiting-list">
                        {{-- populated by JS --}}
                    </ul>
                </div>
            </div>
        </div>

    </div>

    {{-- Transfer Modal --}}
    <div class="modal fade" id="transferModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Transfer Transaction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Select the target transaction type to transfer the current ticket to:</p>
                    <select class="form-select form-select-lg mb-3" id="transfer-target">
                        @foreach($transactions as $tx)
                            <option value="{{ $tx->id }}">{{ $tx->name }}</option>
                        @endforeach
                    </select>
                    <div class="alert alert-info small mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        The ticket will be moved to the waiting list of the selected transaction.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="confirmTransfer()">
                        <i class="bi bi-arrow-right-circle me-1"></i> Confirm Transfer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('vendor/jquery/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>
        let currentTicketId = null;
        let transferModal   = null;

        document.addEventListener('DOMContentLoaded', function () {
            transferModal = new bootstrap.Modal(document.getElementById('transferModal'));
            fetchData();

            if (window.EventSource) {
                const eventSource = new EventSource('{{ route("queue.stream") }}');

                eventSource.onopen = function () {
                    document.getElementById('connection-status').className = 'badge bg-success rounded-pill px-3 py-2';
                    document.getElementById('connection-status').innerHTML =
                        '<span class="spinner-grow spinner-grow-sm me-1" role="status" aria-hidden="true" style="width:0.5rem;height:0.5rem;"></span> Live (Stream)';
                };

                eventSource.addEventListener('queue_created', function (e) {
                    try {
                        const payload = JSON.parse(e.data);
                        const myTransactionId = @json($transaction?->id);
                        if (myTransactionId === null || payload.transaction_id == myTransactionId) {
                            fetchData();
                        }
                    } catch (err) {
                        fetchData();
                    }
                });

                eventSource.addEventListener('queue_updated', function (e) {
                    try {
                        const payload       = JSON.parse(e.data);
                        const myCounterId   = @json($user->counter_id);
                        const myTransactionId = @json($transaction?->id);
                        const isMyCurrentTicket = currentTicketId !== null && payload.id == currentTicketId;

                        const isRelevant =
                            isMyCurrentTicket ||
                            (payload.counter_id != null && payload.counter_id == myCounterId) ||
                            (
                                (myTransactionId === null || payload.transaction_id == myTransactionId) &&
                                (payload.status === 'waiting' || payload.status === 'skipped' || payload.status === 'cancelled')
                            );

                        if (isRelevant) fetchData();
                    } catch (err) {
                        console.warn('SSE parse error:', err);
                    }
                });

                eventSource.onerror = function () {
                    document.getElementById('connection-status').className = 'badge bg-danger rounded-pill px-3 py-2';
                    document.getElementById('connection-status').innerText = 'Offline (Reconnecting…)';
                };
            } else {
                setInterval(fetchData, 5000);
            }
        });

        /* ── Data fetching ──────────────────────────────────── */
        function fetchData() {
            fetch('{{ route("queue.my-counter.data") }}')
                .then(r => r.json())
                .then(data => {
                    renderCurrent(data.current);
                    renderWaiting(data.waiting);
                    renderStatusCombined(data.skipped || [], data.cancelled || []);
                })
                .catch(() => {
                    document.getElementById('connection-status').className = 'badge bg-danger rounded-pill px-3 py-2';
                    document.getElementById('connection-status').innerText = 'Offline';
                });
        }

        /* ── Render current ticket ──────────────────────────── */
        function renderCurrent(ticket) {
            const panel   = document.getElementById('current-ticket-panel');
            const buttons = document.getElementById('action-buttons');

            if (!ticket) {
                currentTicketId = null;
                panel.innerHTML = `
                    <div class="text-muted opacity-50">
                        <i class="bi bi-inbox display-1"></i>
                        <h3 class="mt-3">No Active Transaction</h3>
                        <p>Click "Call Next" to start serving.</p>
                    </div>`;
                buttons.innerHTML = `
                    <button class="btn btn-primary btn-sm shadow-sm" onclick="performAction('call')">
                        <i class="bi bi-megaphone me-1"></i> Call Next
                    </button>`;
                return;
            }

            currentTicketId = ticket.id;

            const priorityIcon = ticket.priority_id
                ? '<i class="bi bi-person-wheelchair text-danger ms-3" style="font-size:0.7em;vertical-align:middle;"></i>'
                : '';

            let statusBadge = '';
            switch (ticket.status) {
                case 'called':  statusBadge = '<span class="badge bg-warning text-dark mb-3 px-3 py-2">CALLED</span>'; break;
                case 'serving': statusBadge = '<span class="badge bg-success mb-3 px-3 py-2">SERVING</span>'; break;
                default:        statusBadge = `<span class="badge bg-secondary mb-3 px-3 py-2">${ticket.status.toUpperCase()}</span>`;
            }

            const nameHtml = ticket.name ? `<span class="ms-3 fs-3 text-muted">${ticket.name}</span>` : '';
            panel.innerHTML = `
                ${statusBadge}
                <h1 class="display-1 fw-bold mb-0 text-dark d-flex align-items-center justify-content-center" style="font-size:6rem;">
                    <span>${ticket.queue_number}${priorityIcon}</span>${nameHtml}
                </h1>
                <div class="mt-4 text-muted">
                    <i class="bi bi-clock me-1"></i>
                    Called: ${new Date(ticket.called_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                </div>`;

            let actionButtons = '';
            if (ticket.status === 'called') {
                actionButtons = `
                    <button class="btn btn-success btn-sm shadow-sm" onclick="performAction('serve')">
                        <i class="bi bi-play-circle me-1"></i> Serve
                    </button>
                    <button class="btn btn-outline-primary btn-sm shadow-sm" onclick="performAction('reannounce')" title="Re-announce current call">
                        <i class="bi bi-megaphone me-1"></i> Re-Call
                    </button>
                    <button class="btn btn-secondary btn-sm shadow-sm" onclick="performAction('skip')">
                        <i class="bi bi-skip-forward me-1"></i> Skip
                    </button>
                    <button class="btn btn-danger btn-sm shadow-sm" onclick="performAction('cancel')">
                        <i class="bi bi-x-circle me-1"></i> Cancel
                    </button>
                    <button class="btn btn-warning btn-sm shadow-sm text-dark" onclick="showTransferModal()">
                        <i class="bi bi-arrow-left-right me-1"></i> Transfer
                    </button>`;
            } else if (ticket.status === 'serving') {
                actionButtons = `
                    <button class="btn btn-primary btn-sm shadow-sm" onclick="performAction('complete')">
                        <i class="bi bi-check-circle me-1"></i> Complete
                    </button>
                    <button class="btn btn-secondary btn-sm shadow-sm" onclick="performAction('skip')">
                        <i class="bi bi-skip-forward me-1"></i> Skip
                    </button>
                    <button class="btn btn-outline-primary btn-sm shadow-sm" onclick="performAction('reannounce')" title="Re-announce current call">
                        <i class="bi bi-megaphone me-1"></i> Again
                    </button>
                    <button class="btn btn-warning btn-sm shadow-sm text-dark" onclick="showTransferModal()">
                        <i class="bi bi-arrow-left-right me-1"></i> Transfer
                    </button>`;
            }

            buttons.innerHTML = actionButtons;
        }

        /* ── Render waiting list ────────────────────────────── */
        function renderWaiting(tickets) {
            const list  = document.getElementById('waiting-list');
            const count = document.getElementById('waiting-count');
            count.innerText = tickets.length;

            if (tickets.length === 0) {
                list.innerHTML = `
                    <li class="list-group-item text-center py-5 text-muted bg-transparent" style="font-size:0.82rem;">
                        <i class="bi bi-cup-hot display-6 mb-3 d-block opacity-50"></i>
                        No waiting tickets
                    </li>`;
                return;
            }

            let html = '';
            tickets.forEach(ticket => {
                const priorityIcon = ticket.priority_id
                    ? '<i class="bi bi-person-wheelchair text-danger ms-2 me-2" style="font-size:1.4em;"></i>'
                    : '';
                const nameHtml = ticket.name ? `<span class="text-muted ms-2" style="font-size:0.75rem;">( ${ticket.name} )</span>` : '';
                html += `
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 bg-white">
                        <div class="d-flex align-items-center">
                            <span class="fw-bold fs-6 text-dark">${ticket.queue_number}</span>${nameHtml}
                        </div>
                        <small class="text-muted">
                            ${priorityIcon} ${new Date(ticket.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                        </small>
                    </li>`;
            });
            list.innerHTML = html;
        }

        /* ── Render skipped / cancelled list ────────────────── */
        function renderStatusCombined(skipped, cancelled) {
            const list = document.getElementById('status-list');
            if (!list) return;

            const items = []
                .concat((skipped   || []).map(t => ({ ...t, _status: 'skipped'   })))
                .concat((cancelled || []).map(t => ({ ...t, _status: 'cancelled' })))
                .sort((a, b) => new Date(b.created_at) - new Date(a.created_at));

            if (items.length === 0) {
                list.innerHTML = `<li class="list-group-item text-center py-3 text-muted bg-transparent" style="font-size:0.78rem;">No skipped/cancelled</li>`;
                return;
            }

            let html = '';
            items.forEach(t => {
                const badge = t._status === 'cancelled'
                    ? '<span class="badge bg-danger me-1" style="font-size:0.65rem;">Cancelled</span>'
                    : '<span class="badge bg-secondary me-1" style="font-size:0.65rem;">Skipped</span>';
                const isSkipped = t._status === 'skipped';
                const liClass = isSkipped
                    ? 'list-group-item d-flex justify-content-between align-items-center py-2 clickable'
                    : 'list-group-item d-flex justify-content-between align-items-center py-2';
                const attrs = isSkipped
                    ? `data-ticket-id="${t.id}" data-status="${t._status}" role="button" tabindex="0" aria-label="Recall skipped ticket ${t.queue_number}"`
                    : `aria-disabled="true" style="cursor:not-allowed;opacity:.9;"`;
                html += `
                    <li class="${liClass}" ${attrs} style="font-size:0.8rem;">
                        <div class="d-flex align-items-center">${badge}<span class="fw-bold">${t.queue_number}</span></div>
                        <small class="text-muted">${new Date(t.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</small>
                    </li>`;
            });
            list.innerHTML = html;

            Array.from(list.querySelectorAll('li.clickable')).forEach(li => {
                li.addEventListener('click',    ()  => handleStatusClick(li));
                li.addEventListener('keypress', (e) => { if (e.key === 'Enter' || e.key === ' ') handleStatusClick(li); });
            });
        }

        /* ── Recall skipped / restore cancelled ─────────────── */
        function handleStatusClick(li) {
            if (currentTicketId !== null) {
                alert('Unable to call. There is a Queue in the current Transaction.');
                return;
            }
            const id     = li.getAttribute('data-ticket-id');
            const status = li.getAttribute('data-status');
            if (!id || !status) return;

            const originalHtml = li.innerHTML;
            li.classList.add('disabled');
            li.innerHTML = '<span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span><span class="ms-2">Updating…</span>';

            let url = '';
            if (status === 'skipped')   url = '{{ route("queue.my-counter.recall-skipped") }}';
            else if (status === 'cancelled') url = '{{ route("queue.my-counter.restore-cancelled") }}';
            else return;

            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ ticket_id: id })
            })
            .then(r => r.json())
            .then(json => {
                if (!(json && json.success)) {
                    alert(json && json.error ? json.error : 'Failed to update status.');
                    li.classList.remove('disabled');
                    li.innerHTML = originalHtml;
                    return;
                }
                fetchData();
            })
            .catch(err => {
                console.error(err);
                alert('Network error. Please try again.');
                li.classList.remove('disabled');
                li.innerHTML = originalHtml;
            });
        }

        /* ── Perform counter action ─────────────────────────── */
        function performAction(action, payload = {}) {
            let url  = '';
            let body = { ...payload };

            if (action !== 'call') {
                if (!currentTicketId) return;
                body.ticket_id = currentTicketId;
            }

            switch (action) {
                case 'call':       url = '{{ route("queue.my-counter.call") }}';             break;
                case 'serve':      url = '{{ route("queue.my-counter.serve") }}';            break;
                case 'complete':   url = '{{ route("queue.my-counter.complete") }}';         break;
                case 'skip':       url = '{{ route("queue.my-counter.skip") }}';             break;
                case 'cancel':     url = '{{ route("queue.my-counter.cancel") }}';           break;
                case 'transfer':   url = '{{ route("queue.my-counter.transfer") }}';         break;
                case 'reannounce': url = '{{ route("queue.my-counter.reannounce") }}';       break;
            }

            const btns = document.getElementById('action-buttons');
            const prev = btns ? btns.innerHTML : '';
            if (btns) btns.querySelectorAll('button').forEach(b => b.setAttribute('disabled', 'true'));

            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify(body)
            })
            .then(r => r.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    if (btns) btns.innerHTML = prev;
                    return;
                }
                if (action === 'transfer') {
                    renderCurrent(null);
                }
                fetchData();
            })
            .catch(() => {
                alert('An error occurred. Please try again.');
                if (btns) btns.innerHTML = prev;
            });
        }

        function showTransferModal() { transferModal.show(); }

        function confirmTransfer() {
            const targetId = document.getElementById('transfer-target').value;
            if (!targetId) return;
            performAction('transfer', { transaction_id: targetId });
            transferModal.hide();
        }
    </script>
</body>
</html>
