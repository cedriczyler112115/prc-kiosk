@extends('layouts.app')

@section('title', 'Guard Queue Entry')

@section('content')
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div class="w-100">
      <label for="inputName" class="form-label fw-bold" style="font-size: 1.25rem;">Client Name</label>
      <input type="text" id="inputName" class="form-control form-control-lg" placeholder="Enter name" aria-label="Name"
             style="height: 68px; font-size: 28px;">
    </div>
  </div>

  <style>
    #inputName {
      color: #333333;
    }
    #inputName::placeholder {
      color: #cccccc;
      opacity: 1;
    }
    .select-card {
      border: 3px solid var(--bs-border-color);
      border-radius: 1rem;
      padding: 1.5rem;
      height: 100%;
      min-height: 100px;
      cursor: pointer;
      transition: all .2s ease-in-out;
      user-select: none;
      background-color: var(--bs-white);
      color: var(--bs-dark);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 1rem;
      position: relative;
    }
    .select-card:hover {
      border-color: var(--bs-primary);
      background-color: var(--bs-light);
      transform: translateY(-2px);
    }
    .select-card.active {
      border-color: var(--bs-primary);
      background-color: var(--bs-primary);
      color: white;
      box-shadow: 0 .5rem 1.5rem rgba(13,110,253,.15);
      transform: translateY(-2px);
    }
    .select-card.active .text-muted {
      color: rgba(255,255,255,0.8) !important;
    }
    .select-card .icon-wrapper {
      font-size: 3.5rem;
      margin-bottom: -1rem;
      color: var(--bs-primary);
      transition: color .2s ease;
    }
    .select-card.active .icon-wrapper {
      color: white;
    }
    .counter-indicator {
      position: absolute;
      top: 0;
      right: 0;
      padding: 0.5rem 1rem;
      font-size: 5rem;
      font-weight: 800;
      line-height: 1;
      color: var(--bs-secondary);
      opacity: 0.5;
      transition: color .2s ease, opacity .2s ease;
    }
    .select-card.active .counter-indicator {
      color: var(--bs-white);
      opacity: 0.5;
    }
    .tx-title {
      font-weight: 700;
      font-size: 2.0rem;
      line-height: 1;
      text-align: center;
      word-break: break-word;
    }
    .visually-hidden-focusable {
      position: absolute !important;
      width: 1px;
      height: 1px;
      padding: 0;
      margin: -1px;
      overflow: hidden;
      clip: rect(0,0,0,0);
      border: 0;
    }
    .priority-row {
      display: none;
    }
    .priority-row.active {
      display: block;
    }
  </style>

  <!-- Print Area (Hidden by default) -->
  <div id="print-area" style="display: none;">
    <div class="header">Professional Regulation Commission<br>CARAGA</div>
    <div class="queue-number" id="printQueueNumber"></div>
    <div class="meta" id="printTransaction"></div>
    <div class="meta" id="printDate"></div>
    <div class="meta" style="margin-top: 1rem; font-size: 0.8rem;">Please wait for your number to be called.</div>
  </div>

  <div
    id="transactionsGrid"
    class="row row-cols-1 row-cols-md-3 g-4"
    role="radiogroup"
    aria-label="Select transaction"
  >
    @foreach($transactions as $t)
      <div class="col">
        <label class="select-card shadow-sm" data-transaction-id="{{ $t->id }}" tabindex="0" aria-checked="false" role="radio">
          {{-- @if($t->counter_number)
            <div class="counter-indicator">{{ $t->counter_number }}</div>
          @endif --}}
          <div class="icon-wrapper">
            @if(Str::contains(Str::lower($t->name), ['payment', 'cashier', 'fee', 'bill']))
              <i class="bi bi-cash-stack"></i>
            @elseif(Str::contains(Str::lower($t->name), ['license', 'id', 'card']))
              <i class="bi bi-person-vcard"></i>
            @elseif(Str::contains(Str::lower($t->name), ['register', 'apply', 'enroll']))
              <i class="bi bi-clipboard-check"></i>
            @elseif(Str::contains(Str::lower($t->name), ['claim', 'release', 'pickup']))
              <i class="bi bi-box-seam"></i>
            @elseif(Str::contains(Str::lower($t->name), ['exam', 'test']))
              <i class="bi bi-pencil-square"></i>
            @else
              <i class="bi bi-file-earmark-text"></i>
            @endif
          </div>
          <div class="tx-title">{{ $t->name }}</div>
          <input class="visually-hidden-focusable tx-radio" type="radio" name="transaction_id" value="{{ $t->id }}" aria-label="{{ $t->name }}">
        </label>
      </div>
    @endforeach
  </div>
<br>
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
      <div class="d-flex align-items-center gap-3 mb-4">
        <div class="form-check form-switch fs-4 m-0">
          <input class="form-check-input" type="checkbox" id="globalSpecial" aria-controls="priorityGlobalRow" aria-expanded="false">
          <label class="form-check-label fw-bold" for="globalSpecial">Special Lane</label>
        </div>
      </div>

      <div id="priorityGlobalRow" class="priority-row" aria-live="polite">
        <div class="row row-cols-1 row-cols-md-3 g-4" role="radiogroup" aria-label="Select priority">
          @foreach($priorities as $p)
            <div class="col">
              <label class="select-card shadow-sm" data-priority-id="{{ $p->id }}" tabindex="0" aria-checked="false" role="radio">
                <div class="icon-wrapper">
                  @if(Str::contains(Str::lower($p->name), ['senior', 'elder']))
                    <i class="bi bi-person-arms-up"></i>
                  @elseif(Str::contains(Str::lower($p->name), ['pwd', 'disability', 'disabled']))
                    <i class="bi bi-person-wheelchair"></i>
                  @elseif(Str::contains(Str::lower($p->name), ['pregnant', 'mother']))
                    <i class="bi bi-gender-female"></i>
                  @elseif(Str::contains(Str::lower($p->name), ['student']))
                    <i class="bi bi-mortarboard"></i>
                  @else
                    <i class="bi bi-person-badge"></i>
                  @endif
                </div>
                <div class="tx-title">{{ $p->name }}</div>
                
                <input class="visually-hidden-focusable pr-radio" type="radio" name="priority_id" value="{{ $p->id }}" aria-label="{{ $p->name }}">
              </label>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div><br>
  <div class="mt-4 d-flex align-items-center gap-2">
   
    <button type="button" id="submitSelection" class="btn btn-success" style="width: 100%;height:60px;font-size:25px" disabled aria-disabled="true"> <i class="bi bi-printer"></i> 
      Queue and Print
    </button>
  </div>
  <div id="selectionStatus" class="small text-muted mt-2" aria-live="polite"></div>
@endsection

@push('scripts')
  <script>
    (function () {
      function evaluatePrintButtonState(state) {
        if (!state.transactionId) {
          return { enabled: false, reason: 'Select at least one transaction.' };
        }
        if (state.specialLane && !state.priorityId) {
          return { enabled: false, reason: 'Select a priority while Special Lane is ON.' };
        }
        return { enabled: true, reason: '' };
      }

      function updatePrintButtonUI(state) {
        const $status = $('#selectionStatus');
        const $btn = $('#submitSelection');

        const result = evaluatePrintButtonState(state);
        $btn.prop('disabled', !result.enabled).attr('aria-disabled', result.enabled ? 'false' : 'true');

        if (!result.enabled) {
          $btn.attr('title', result.reason);
          $status.text(result.reason);
          return;
        }

        const txLabel = $('[data-transaction-id=\"' + state.transactionId + '\"] .tx-title').text().trim();
        const prLabel = state.priorityId ? $('[data-priority-id=\"' + state.priorityId + '\"] .fw-semibold').text().trim() : null;
        $btn.attr('title', 'Queue and print ticket');
        $status.text(state.specialLane ? ('Ready: ' + txLabel + ' · ' + prLabel) : ('Ready: ' + txLabel));
      }

      const $grid = $('#transactionsGrid');
      const $globalSpecial = $('#globalSpecial');
      const $priorityWrap = $('#priorityGlobalRow');
      let current = { transactionId: null, specialLane: false, priorityId: null };

      function clearAllSelections() {
        $grid.find('.select-card').removeClass('active').attr('aria-checked', 'false');
        $grid.find('.tx-radio').prop('checked', false);
        // Keep global Special Lane as is; clear only priority selection
        $priorityWrap.find('.select-card').removeClass('active').attr('aria-checked', 'false');
        $priorityWrap.find('.pr-radio').prop('checked', false);
        current.transactionId = null;
        current.priorityId = null;
        // specialLane is managed separately
      }

      function onSelectTransaction($card) {
        const tid = String($card.data('transaction-id'));
        if (!tid) return;

        // Reset all then activate current
        clearAllSelections();
        $card.addClass('active').attr('aria-checked', 'true');
        $card.find('.tx-radio').prop('checked', true);

        current.transactionId = tid;
        // Preserve global special lane; clear priority if special lane is off
        if (!current.specialLane) {
          current.priorityId = null;
        }
        updatePrintButtonUI(current);
      }

      function onToggleGlobalSpecial($checkbox) {
        const checked = $checkbox.is(':checked');
        if (checked) {
          $priorityWrap.addClass('active').slideDown(120);
          $checkbox.attr('aria-expanded', 'true');
        } else {
          // Clear priority selection
          $priorityWrap.find('.select-card').removeClass('active').attr('aria-checked', 'false');
          $priorityWrap.find('.pr-radio').prop('checked', false);
          $priorityWrap.removeClass('active').slideUp(120);
          $checkbox.attr('aria-expanded', 'false');
        }
        current.specialLane = checked;
        if (!checked) current.priorityId = null;
        updatePrintButtonUI(current);
      }

      function onSelectPriority($card) {
        const pid = String($card.data('priority-id'));

        // Unselect others in this group
        $priorityWrap.find('.select-card').removeClass('active').attr('aria-checked', 'false');
        $card.addClass('active').attr('aria-checked', 'true');
        $card.find('.pr-radio').prop('checked', true);

        current.priorityId = pid;
        updatePrintButtonUI(current);
      }

      // Click handlers
      $grid.on('click', '.select-card', function (e) {
        // Avoid card click when clicking inside controls
        if ($(e.target).is('input, .pr-radio')) return;
        onSelectTransaction($(this));
      });
      $grid.on('keydown', '.select-card', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          onSelectTransaction($(this));
        }
      });
      $grid.on('change', '.tx-radio', function () {
        onSelectTransaction($(this).closest('.select-card'));
      });
      $globalSpecial.on('change', function () {
        onToggleGlobalSpecial($(this));
      });
      $('#priorityGlobalRow').on('click keydown', '.select-card', function (e) {
        if (e.type === 'keydown' && !(e.key === 'Enter' || e.key === ' ')) return;
        // e.preventDefault(); // preventing default might stop radio check? No, we handle it manually.
        if(e.type === 'keydown') e.preventDefault();
        
        onSelectPriority($(this));
      });

      $('#submitSelection').on('click', function () {
        if (!current.transactionId) return;

        const $btn = $(this);
        const originalText = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Printing...');

        const name = $('#inputName').val().trim();
        $.ajax({
          url: '{{ route('queue.guard-entry.store') }}',
          method: 'POST',
          data: {
            transaction_id: current.transactionId,
            special_lane: current.specialLane ? 1 : 0,
            priority_id: current.specialLane ? current.priorityId : null,
            name: name,
            _token: '{{ csrf_token() }}'
          },
          success: function (response) {
            // Use iframe for direct printing to bypass preview dialog (if kiosk mode enabled)
            // and ensure correct visibility/styling
            const ticket = response.ticket;
            let $iframe = $('#print-iframe');
            if ($iframe.length === 0) {
              $iframe = $('<iframe id="print-iframe" name="print-iframe" style="display:none;"></iframe>');
              $('body').append($iframe);
            }
            
            const doc = $iframe[0].contentWindow.document;
            doc.open();
            doc.write(`
              <html>
              <head>
                <style>
                  @page { size: 80mm auto; margin: 0; }
                  body { 
                    font-family: 'Courier New', monospace; 
                    width: 72mm; 
                    margin: 2mm auto; 
                    text-align: center; 
                    color: black !important;
                    background: white;
                  }
                  .header { font-weight: bold; font-size: 10px; margin-bottom: 10px; line-height: 1.5; }
                  .queue-number { font-size: 48px; font-weight: 900; margin-top: 5px; display: block; }
                  .meta { font-size: 9px; margin-bottom: 0px; }
                  .transaction-name { font-size: 12px; margin-bottom: 5px; }
                  .footer { font-size: 10px; margin-top: 15px; border-top: 1px dashed #000; padding-top: 5px; }
                </style>
              </head>
              <body>
                <div class="header">Professional Regulation Commision - CARAGA</div>
                <div class="meta">${ticket.created_at}</div>
                <div class="queue-number">${ticket.queue_number}</div>
                <div class="transaction-name">${ticket.transaction_name} ${ticket.priority_name ? '(' + ticket.priority_name + ')' : ''}</div>
                <div class="footer">Please wait for your number to be called.</div>
                <script>
                  window.onload = function() {
                    window.focus();
                    window.print();
                  };
                <\/script>
              </body>
              </html>
            `);
            doc.close();

            // Reset form immediately (printing is handled by browser/OS)
            setTimeout(function() {
              clearAllSelections();
              current = { transactionId: null, specialLane: false, priorityId: null };
              $globalSpecial.prop('checked', false).trigger('change');
              $('#inputName').val('');
              $btn.html(originalText);
              updatePrintButtonUI(current);
            }, 1000); 
          },
          error: function (xhr) {
            console.error(xhr);
            alert('Error creating queue ticket. Please try again.');
            $btn.html(originalText);
            updatePrintButtonUI(current);
          }
        });
      });

      // Initial state
      updatePrintButtonUI(current);
    })();
  </script>
@endpush
