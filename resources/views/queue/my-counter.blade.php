@extends('layouts.app')

@section('title', 'My Counter')

@push('styles')
    <style>
        :root {
            --counter-card-height: 620px;
        }

        .counter-fixed-card {
            height: var(--counter-card-height);
            min-height: var(--counter-card-height);
            max-height: var(--counter-card-height);
        }

        .counter-scroll-area {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
        }

        .counter-card-body {
            flex: 1 1 auto;
            min-height: 0;
        }

        .counter-fixed-card .card-header,
        .counter-fixed-card .card-footer {
            flex-shrink: 0;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-0">
                    <i class="bi bi-window-sidebar me-2"></i>
                    {{ $user->counter_id ? ('Counter ' . $user->counter_id) : 'No Counter Assigned' }}
                </h4>
                <div class="text-muted small mt-1">
                    <span class="badge bg-light text-dark border me-2">
                        <i class="bi bi-tag me-1"></i>
                        {{ $transaction ? $transaction->name : 'No Transaction Assigned' }}
                    </span>
                    <span class="badge bg-light text-dark border">
                        <i class="bi bi-person-badge me-1"></i>
                        {{ $user->name }}
                    </span>
                </div>
            </div>
            <div>
                <span id="connection-status" class="badge bg-success rounded-pill px-3 py-2">
                    <span class="spinner-grow spinner-grow-sm me-1" role="status" aria-hidden="true"
                        style="width: 0.5rem; height: 0.5rem;"></span>
                    Live
                </span>
            </div>
        </div>

        <div class="row g-4 align-items-stretch">
        <div class="col-lg-3">
            <div class="card shadow-sm border-0" aria-labelledby="statusCombinedHeading">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 text-secondary fw-bold">Skipped</h5>
                </div>

                <div class="card-body p-3">
                    <div style="height: 600px; overflow-y: auto;">
                        <ul class="list-group list-group-flush" id="status-list"
                            aria-live="polite"
                            aria-relevant="additions text">
                            <li class="list-group-item text-center py-3 text-muted bg-transparent">
                                None
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
            <!-- Current Transaction -->
            <div class="col-lg-6">
                <div class="card h-100 shadow-sm border-0 d-flex flex-column counter-fixed-card">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="mb-0 text-secondary fw-bold">Current Transaction</h5>
                    </div>
                    <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-5 position-relative flex-grow-1 counter-card-body"
                        id="current-ticket-panel">
                        <!-- Dynamic Content will be injected here -->
                        <div class="text-muted">
                            <div class="spinner-border text-primary mb-3" role="status"></div>
                            <p>Loading queue status...</p>
                        </div>
                    </div>
                    <div class="card-footer bg-light p-4 border-top">
                        <div class="d-grid gap-2 d-md-flex justify-content-md-center" id="action-buttons">
                            <!-- Dynamic Buttons -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Next in Line -->
<div class="col-lg-3">
    <div class="card h-100 shadow-sm border-0 d-flex flex-column counter-fixed-card">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-secondary fw-bold">Next</h5>
            <span class="badge bg-primary rounded-pill">
                Waiting Tickets:
                <span class="badge bg-primary rounded-pill" id="waiting-count">0</span>
            </span>
        </div>

        <div class="card-body p-0 bg-light">
            <ul class="list-group list-group-flush waiting-list-scroll" id="waiting-list">
                <!-- Dynamic List -->
            </ul>
        </div>
    </div>
</div>
        </div>
    </div>

    <!-- Transfer Modal -->
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

    @push('scripts')
        <script>
            let currentTicketId = null;
            let transferModal = null;

            document.addEventListener('DOMContentLoaded', function () {
                transferModal = new bootstrap.Modal(document.getElementById('transferModal'));
                fetchData();

                if (window.EventSource) {
                    const eventSource = new EventSource('{{ route("queue.stream") }}');

                    eventSource.onopen = function () {
                        document.getElementById('connection-status').className = 'badge bg-success rounded-pill px-3 py-2';
                        document.getElementById('connection-status').innerHTML = '<span class="spinner-grow spinner-grow-sm me-1" role="status" aria-hidden="true" style="width: 0.5rem; height: 0.5rem;"></span> Live (Stream)';
                    };

                    eventSource.addEventListener('queue_created', function (e) {
                        // Only refetch if a new ticket is created for MY transaction type.
                        try {
                            const payload = JSON.parse(e.data);
                            const myTransactionId = @json($transaction?->id);
                            if (myTransactionId === null || payload.transaction_id == myTransactionId) {
                                fetchData();
                            }
                        } catch (err) {
                            fetchData(); // parse error fallback
                        }
                    });

                    eventSource.addEventListener('queue_updated', function (e) {
                        try {
                            const payload = JSON.parse(e.data);

                            const myCounterId = @json($user->counter_id);
                            const myTransactionId = @json($transaction?->id);
                            const isMyCurrentTicket = currentTicketId !== null && payload.id == currentTicketId;

                            // Only trigger a DB fetch when the event is relevant to this counter.
                            // Ignoring unrelated events dramatically reduces myCounterData() DB calls.
                            const isRelevant =
                                isMyCurrentTicket ||
                                (payload.counter_id != null && payload.counter_id == myCounterId) ||
                                (
                                    (myTransactionId === null || payload.transaction_id == myTransactionId) &&
                                    (payload.status === 'waiting' || payload.status === 'skipped' || payload.status === 'cancelled')
                                );

                            if (isRelevant) {
                                fetchData();
                            }
                        } catch (err) {
                            // Do NOT auto-fetch on parse errors — that makes every malformed event
                            // trigger a DB round-trip. Log and wait for the next real event.
                            console.warn('SSE parse error:', err);
                        }
                    });

                    eventSource.onerror = function (e) {
                        console.error('SSE Error:', e);
                        document.getElementById('connection-status').className = 'badge bg-danger rounded-pill px-3 py-2';
                        document.getElementById('connection-status').innerText = 'Offline (Reconnecting...)';
                    };
                } else {
                    // Fallback polling — increased from 2 s to 5 s to reduce DB pressure
                    // on browsers/environments that don't support EventSource.
                    setInterval(fetchData, 5000);
                }
            });

            function fetchData() {
                fetch('{{ route("queue.my-counter.data") }}')
                    .then(response => response.json())
                    .then(data => {
                        renderCurrent(data.current);
                        renderWaiting(data.waiting);
                        renderStatusCombined(data.skipped || [], data.cancelled || []);
                    })
                    .catch(error => {
                        console.error('Error fetching data:', error);
                        document.getElementById('connection-status').className = 'badge bg-danger rounded-pill px-3 py-2';
                        document.getElementById('connection-status').innerText = 'Offline';
                    });
            }

            function renderCurrent(ticket) {
                const panel = document.getElementById('current-ticket-panel');
                const buttons = document.getElementById('action-buttons');

                if (!ticket) {
                    currentTicketId = null;
                    panel.innerHTML = `
                                                <div class="text-muted opacity-50">
                                                    <i class="bi bi-inbox display-1"></i>
                                                    <h3 class="mt-3">No Active Transaction</h3>
                                                    <p>Click "Call Next" to start serving.</p>
                                                </div>
                                            `;
                    buttons.innerHTML = `
                                                <button class="btn btn-primary btn-lg px-5 py-3 shadow-sm" onclick="performAction('call')">
                                                    <i class="bi bi-megaphone me-2"></i> Call Next
                                                </button>
                                            `;
                    return;
                }

                currentTicketId = ticket.id;

                // Priority Icon Logic
                let priorityIcon = ticket.priority_id
                    ? '<i class="bi bi-person-wheelchair text-danger ms-3" style="font-size: 0.7em; vertical-align: middle;"></i>'
                    : '';

                let statusBadge = '';
                switch (ticket.status) {
                    case 'called': statusBadge = '<span class="badge bg-warning text-dark mb-3 px-3 py-2">CALLED</span>'; break;
                    case 'serving': statusBadge = '<span class="badge bg-success mb-3 px-3 py-2">SERVING</span>'; break;
                    default: statusBadge = `<span class="badge bg-secondary mb-3 px-3 py-2">${ticket.status.toUpperCase()}</span>`;
                }

                const nameHtml = ticket.name ? `<span class="ms-3 fs-3 text-muted">${ticket.name}</span>` : '';
                panel.innerHTML = `
                                            ${statusBadge}
                                            <h1 class="display-1 fw-bold mb-0 text-dark d-flex align-items-center justify-content-center" style="font-size: 6rem;">
                                                <span>${ticket.queue_number}${priorityIcon}</span>${nameHtml}
                                            </h1>
                                            <div class="mt-4 text-muted">
                                                <i class="bi bi-clock me-1"></i> 
                                                Called: ${new Date(ticket.called_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                            </div>
                                        `;

                // Buttons based on status
                let actionButtons = '';

                if (ticket.status === 'called') {
                    actionButtons = `
                                                <button class="btn btn-success btn-lg px-4 py-3 shadow-sm" onclick="performAction('serve')">
                                                    <i class="bi bi-play-circle me-2"></i> Serve
                                                </button>
                                                <button class="btn btn-outline-primary btn-lg px-4 py-3 shadow-sm" onclick="performAction('reannounce')" title="Re-announce current call">
                                                    <i class="bi bi-megaphone me-2"></i> <br>Call&nbsp;Again
                                                </button>
                                                <button class="btn btn-secondary btn-lg px-4 py-3 shadow-sm" onclick="performAction('skip')">
                                                    <i class="bi bi-skip-forward me-2"></i> Skip
                                                </button>
                                                <button class="btn btn-danger btn-lg px-4 py-3 shadow-sm" onclick="performAction('cancel')">
                                                    <i class="bi bi-x-circle me-2"></i> Cancel
                                                </button>
                                                <button class="btn btn-warning btn-lg px-4 py-3 shadow-sm text-dark" onclick="showTransferModal()">
                                                    <i class="bi bi-arrow-left-right me-2"></i> Transfer
                                                </button>
                                            `;
                } else if (ticket.status === 'serving') {
                    actionButtons = `
                                                <button class="btn btn-primary btn-lg px-5 py-3 shadow-sm" onclick="performAction('complete')">
                                                    <i class="bi bi-check-circle me-2"></i> Complete
                                                </button>
                                                <button class="btn btn-secondary btn-lg px-4 py-3 shadow-sm" onclick="performAction('skip')">
                                                    <i class="bi bi-skip-forward me-2"></i> Skip
                                                </button>                                                
                                                <button class="btn btn-outline-primary btn-lg px-4 py-3 shadow-sm" onclick="performAction('reannounce')" title="Re-announce current call">
                                                    <i class="bi bi-megaphone me-2"></i><br>Call&nbsp;Again
                                                </button>
                                                <button class="btn btn-warning btn-lg px-4 py-3 shadow-sm text-dark" onclick="showTransferModal()">
                                                    <i class="bi bi-arrow-left-right me-2"></i> Transfer
                                                </button>
                                            `;
                }

                buttons.innerHTML = actionButtons;
            }

            function renderWaiting(tickets) {
                const list = document.getElementById('waiting-list');
                const count = document.getElementById('waiting-count');

                count.innerText = tickets.length;

                if (tickets.length === 0) {
                    list.innerHTML = `
                                                <li class="list-group-item text-center py-5 text-muted bg-transparent">
                                                    <i class="bi bi-cup-hot display-6 mb-3 d-block opacity-50"></i>
                                                    No waiting tickets
                                                </li>
                                            `;
                    return;
                }

                let html = '';
                tickets.forEach(ticket => {
                    let priorityIcon = ticket.priority_id
                        ? '<i class="bi bi-person-wheelchair text-danger ms-2 me-2" style="font-size: 1.7em;"></i>'
                        : '';

                    let priorityClass = ticket.priority_id ? 'bg-white' : 'bg-white';

                    const nameHtml = ticket.name ? `<span class="text-muted ms-4">( ${ticket.name} )</span>` : '';
                    html += `
                                                <li class="list-group-item d-flex justify-content-between align-items-center py-3 ${priorityClass}">
                                                    <div class="d-flex align-items-center">
                                                        <span class="fw-bold fs-5 text-dark">${ticket.queue_number}</span>${nameHtml}
                                                    </div>
                                                    <small class="text-muted">
                                                       ${priorityIcon} ${new Date(ticket.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                                    </small>
                                                </li>
                                            `;
                });
                list.innerHTML = html;
            }

            function renderStatusCombined(skipped, cancelled) {
                const list = document.getElementById('status-list');
                if (!list) return;
                const items = []
                    .concat((skipped || []).map(t => ({ ...t, _status: 'skipped' })))
                    .concat((cancelled || []).map(t => ({ ...t, _status: 'cancelled' })))
                    .sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
                if (items.length === 0) {
                    list.innerHTML = `<li class="list-group-item text-center py-3 text-muted bg-transparent">No skipped/cancelled tickets</li>`;
                    return;
                }
                let html = '';
                items.forEach(t => {
                    const badge = t._status === 'cancelled'
                        ? '<span class="badge bg-danger me-2">Cancelled</span>'
                        : '<span class="badge bg-secondary me-2">Skipped</span>';
                    const isSkipped = t._status === 'skipped';
                    const liClass = isSkipped ? 'list-group-item d-flex justify-content-between align-items-center py-2 clickable' : 'list-group-item d-flex justify-content-between align-items-center py-2';
                    const attrs = isSkipped
                        ? `data-ticket-id="${t.id}" data-status="${t._status}" role="button" tabindex="0" aria-label="Recall skipped ticket ${t.queue_number}"`
                        : `aria-disabled="true" style="cursor: not-allowed; opacity: .9;"`;
                    html += `
                                                <li class="${liClass}" ${attrs}>
                                                    <div class="d-flex align-items-center">${badge}<span class="fw-bold">${t.queue_number}</span></div>
                                                    <small class="text-muted">${new Date(t.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</small>
                                                </li>
                                            `;
                });
                list.innerHTML = html;
                // Attach click handlers
                Array.from(list.querySelectorAll('li.clickable')).forEach(li => {
                    li.addEventListener('click', () => handleStatusClick(li));
                    li.addEventListener('keypress', (e) => { if (e.key === 'Enter' || e.key === ' ') handleStatusClick(li); });
                });
            }

            function handleStatusClick(li) {
                if (currentTicketId !== null) {
                    alert('Unable to call. There is a Queue in the current Transaction.');
                    return;
                }

                const id = li.getAttribute('data-ticket-id');
                const status = li.getAttribute('data-status');
                if (!id || !status) return;
                // Loading state
                const originalHtml = li.innerHTML;
                li.classList.add('disabled');
                li.innerHTML = '<span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span><span class="ms-2">Updating…</span>';
                let url = '';
                if (status === 'skipped') {
                    url = '{{ route("queue.my-counter.recall-skipped") }}';
                } else if (status === 'cancelled') {
                    url = '{{ route("queue.my-counter.restore-cancelled") }}';
                } else {
                    return;
                }
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ ticket_id: id })
                })
                    .then(r => r.json())
                    .then(json => {
                        if (json && json.success) {
                            // Let SSE handle the UI refresh
                        } else {
                            alert(json && json.error ? json.error : 'Failed to update status.');
                            li.classList.remove('disabled');
                            li.innerHTML = originalHtml;
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert('Network error. Please try again.');
                        li.classList.remove('disabled');
                        li.innerHTML = originalHtml;
                    });
            }

            function performAction(action, payload = {}) {
                let url = '';
                let body = { ...payload };

                if (action !== 'call') {
                    if (!currentTicketId) return;
                    body.ticket_id = currentTicketId;
                }

                switch (action) {
                    case 'call': url = '{{ route("queue.my-counter.call") }}'; break;
                    case 'serve': url = '{{ route("queue.my-counter.serve") }}'; break;
                    case 'complete': url = '{{ route("queue.my-counter.complete") }}'; break;
                    case 'skip': url = '{{ route("queue.my-counter.skip") }}'; break;
                    case 'cancel': url = '{{ route("queue.my-counter.cancel") }}'; break;
                    case 'transfer': url = '{{ route("queue.my-counter.transfer") }}'; break;
                    case 'reannounce': url = '{{ route("queue.my-counter.reannounce") }}'; break;
                }

                const buttons = document.getElementById('action-buttons');
                const previousButtons = buttons ? buttons.innerHTML : '';
                if (buttons) {
                    buttons.querySelectorAll('button').forEach(b => b.setAttribute('disabled', 'true'));
                }
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(body)
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            alert(data.error);
                            if (buttons) {
                                buttons.innerHTML = previousButtons;
                            }
                        } else {
                            // Let SSE handle the UI refresh to avoid double fetching
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                        if (buttons) {
                            buttons.innerHTML = previousButtons;
                        }
                    });
            }

            function showTransferModal() {
                transferModal.show();
            }

            function confirmTransfer() {
                const targetId = document.getElementById('transfer-target').value;
                if (!targetId) return;

                performAction('transfer', { transaction_id: targetId });
                transferModal.hide();
            }
        </script>
    @endpush
@endsection
