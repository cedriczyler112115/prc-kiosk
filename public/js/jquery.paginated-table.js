/*!
 * jQuery Paginated Table
 *
 * Features:
 * - AJAX pagination with page size selector (default 10; options 10/25/50/100)
 * - Integrated loader (spinner | progress | skeleton)
 * - Prev/Next, page numbers, and jump-to-page input
 * - Timeout management (default 30 seconds) + error handling
 * - Accessibility: ARIA labels, aria-current, keyboard-friendly buttons/inputs
 * - Performance: debounced search support + in-memory page cache (LRU)
 *
 * Basic usage:
 *   $('#myTable').paginatedTable({
 *     url: '/my/data/endpoint',
 *     tbodySelector: 'tbody',
 *     pagerContainer: '#pager',
 *     perPageContainer: '#perPage',
 *     jumpContainer: '#jump',
 *     statusContainer: '#status',
 *     renderRow: function (item) { return '<tr>...</tr>'; }
 *   });
 */
(function ($) {
  'use strict';

  const STYLE_ID = 'jq-paginated-table-styles';

  function ensureStyles() {
    if (document.getElementById(STYLE_ID)) return;

    const css = `
      .jqpt-skeleton td{padding:.75rem 1rem}
      .jqpt-skeleton .jqpt-skelbar{display:block;width:100%;height:.9rem;background:#e9ecef;border-radius:.375rem;animation:jqpt-skel-pulse 1.2s ease-in-out infinite}
      @keyframes jqpt-skel-pulse{0%{opacity:1}50%{opacity:.4}100%{opacity:1}}
      .jqpt-controls{display:flex;gap:.5rem;align-items:center;flex-wrap:wrap}
      .jqpt-controls .page-link{cursor:pointer}
      .jqpt-controls input[type="number"]{max-width:6rem}
      .jqpt-controls select{max-width:7rem}
    `;

    const style = document.createElement('style');
    style.id = STYLE_ID;
    style.textContent = css;
    document.head.appendChild(style);
  }

  function debounce(fn, waitMs) {
    let t = null;
    return function () {
      const ctx = this;
      const args = arguments;
      clearTimeout(t);
      t = setTimeout(function () { fn.apply(ctx, args); }, waitMs);
    };
  }

  function createLRUCache(maxEntries, ttlMs) {
    const map = new Map();

    function now() { return Date.now(); }

    function get(key) {
      const entry = map.get(key);
      if (!entry) return null;
      if (ttlMs > 0 && now() - entry.t > ttlMs) {
        map.delete(key);
        return null;
      }
      map.delete(key);
      map.set(key, entry);
      return entry.v;
    }

    function set(key, value) {
      if (map.has(key)) map.delete(key);
      map.set(key, { v: value, t: now() });
      while (map.size > maxEntries) {
        const firstKey = map.keys().next().value;
        map.delete(firstKey);
      }
    }

    function clear() { map.clear(); }

    return { get, set, clear };
  }

  function clamp(n, min, max) {
    if (n < min) return min;
    if (n > max) return max;
    return n;
  }

  function safeToastr(type, message) {
    try {
      if (window.toastr && typeof window.toastr[type] === 'function') {
        window.toastr[type](message);
        return;
      }
    } catch (_) {}
    window.alert(message);
  }

  $.fn.paginatedTable = function (options) {
    ensureStyles();

    const settings = $.extend(true, {
      url: null,
      method: 'GET',
      timeoutMs: 30000,
      cache: { enabled: true, maxEntries: 50, ttlMs: 60000 },
      loader: { type: 'spinner' }, // spinner | progress | skeleton
      perPage: { default: 10, options: [10, 25, 50, 100] },
      initial: { page: 1, q: '' },
      searchInput: null, // selector or element; optional
      searchDebounceMs: 300,
      requestData: function (state) { return { page: state.page, per_page: state.perPage, q: state.q }; },
      globalAjaxEvents: false,
      tbodySelector: 'tbody',
      pagerContainer: null,
      perPageContainer: null,
      jumpContainer: null,
      statusContainer: null,
      renderRow: null,
      renderEmpty: function () { return '<tr><td colspan="99" class="text-center text-secondary py-4">No records found.</td></tr>'; },
      aria: {
        paginationLabel: 'Pagination',
        perPageLabel: 'Items per page',
        jumpLabel: 'Jump to page',
        firstLabel: 'First page',
        prevLabel: 'Previous page',
        nextLabel: 'Next page',
        lastLabel: 'Last page',
      },
    }, options || {});

    if (!settings.url) throw new Error('paginatedTable: url is required');
    if (typeof settings.renderRow !== 'function') throw new Error('paginatedTable: renderRow(item) is required');

    return this.each(function () {
      const $table = $(this);
      const $tbody = $table.find(settings.tbodySelector);
      const $pager = settings.pagerContainer ? $(settings.pagerContainer) : $();
      const $perPageWrap = settings.perPageContainer ? $(settings.perPageContainer) : $();
      const $jumpWrap = settings.jumpContainer ? $(settings.jumpContainer) : $();
      const $status = settings.statusContainer ? $(settings.statusContainer) : $();
      const $search = settings.searchInput ? $(settings.searchInput) : $();

      const state = {
        page: settings.initial.page || 1,
        perPage: settings.perPage.default || 10,
        q: settings.initial.q || '',
        lastPage: 1,
        total: 0,
        loading: false,
        requestId: 0,
      };

      const cache = createLRUCache(settings.cache.maxEntries, settings.cache.ttlMs);

      function cacheKey() {
        return JSON.stringify({ url: settings.url, page: state.page, perPage: state.perPage, q: state.q });
      }

      function setLoading(on) {
        state.loading = on;
        const $wrap = $table.closest('.table-responsive').length ? $table.closest('.table-responsive') : $table.parent();
        if (!$wrap.length) return;
        if ($wrap.css('position') === 'static') $wrap.css('position', 'relative');

    $wrap.find('.jqpt-loader-overlay, .jqpt-progress').remove();

        if (!on) return;

    {
          const colCount = Math.max(1, $table.find('thead th').length);
          const rows = Math.min(10, state.perPage);
          const parts = [];
          for (let i = 0; i < rows; i++) {
            parts.push('<tr class="jqpt-skeleton">');
            for (let c = 0; c < colCount; c++) {
          parts.push('<td><span class="jqpt-skelbar" aria-hidden="true"></span></td>');
            }
            parts.push('</tr>');
          }
          $tbody.html(parts.join(''));
        }
      }

      function updateStatus() {
        if (!$status.length) return;
        const from = state.total === 0 ? 0 : Math.min(((state.page - 1) * state.perPage) + 1, state.total);
        const to = Math.min(state.page * state.perPage, state.total);
        $status.attr('aria-live', 'polite');
        $status.text(state.total === 0 ? 'No records.' : `Showing ${from}-${to} of ${state.total}`);
      }

      function renderPager() {
        if (!$pager.length) return;

        const last = Math.max(1, state.lastPage);
        const current = clamp(state.page, 1, last);

        const maxButtons = 7;
        const half = Math.floor(maxButtons / 2);
        let start = Math.max(1, current - half);
        let end = Math.min(last, start + maxButtons - 1);
        start = Math.max(1, end - maxButtons + 1);

        const $nav = $('<nav>').attr('aria-label', settings.aria.paginationLabel);
        const $ul = $('<ul class="pagination pagination-sm mb-0">');

        function pageItem(label, page, disabled, ariaLabel, isCurrent) {
          const $li = $('<li class="page-item">');
          if (disabled) $li.addClass('disabled');
          if (isCurrent) $li.addClass('active');

          const $btn = $('<button type="button" class="page-link">').text(label);
          $btn.attr('aria-label', ariaLabel || label);
          if (isCurrent) $btn.attr('aria-current', 'page');
          $btn.on('click', function () {
            if (disabled) return;
            goToPage(page);
          });
          $li.append($btn);
          return $li;
        }

        $ul.append(pageItem('«', 1, current <= 1, settings.aria.firstLabel, false));
        $ul.append(pageItem('‹', current - 1, current <= 1, settings.aria.prevLabel, false));

        if (start > 1) {
          $ul.append(pageItem('1', 1, false, 'Page 1', current === 1));
          if (start > 2) {
            $ul.append($('<li class="page-item disabled"><span class="page-link" aria-hidden="true">…</span></li>'));
          }
        }

        for (let p = start; p <= end; p++) {
          $ul.append(pageItem(String(p), p, false, `Page ${p}`, p === current));
        }

        if (end < last) {
          if (end < last - 1) {
            $ul.append($('<li class="page-item disabled"><span class="page-link" aria-hidden="true">…</span></li>'));
          }
          $ul.append(pageItem(String(last), last, false, `Page ${last}`, current === last));
        }

        $ul.append(pageItem('›', current + 1, current >= last, settings.aria.nextLabel, false));
        $ul.append(pageItem('»', last, current >= last, settings.aria.lastLabel, false));

        $nav.append($ul);
        $pager.empty().append($nav);
      }

      function renderPerPage() {
        if (!$perPageWrap.length) return;

        const $select = $('<select class="form-select form-select-sm">')
          .attr('aria-label', settings.aria.perPageLabel);

        settings.perPage.options.forEach(function (n) {
          const $opt = $('<option>').val(String(n)).text(String(n));
          if (n === state.perPage) $opt.prop('selected', true);
          $select.append($opt);
        });

        $select.on('change', function () {
          const v = parseInt($(this).val(), 10);
          if (!Number.isFinite(v)) return;
          state.perPage = v;
          state.page = 1;
          cache.clear();
          $table.trigger('perPage:change', [state]);
          load();
        });

        const $label = $('<span class="small text-secondary">').text('Per page');
        $perPageWrap.empty().addClass('jqpt-controls').append($label).append($select);
      }

      function renderJump() {
        if (!$jumpWrap.length) return;

        const $input = $('<input type="number" class="form-control form-control-sm">')
          .attr('min', '1')
          .attr('step', '1')
          .attr('inputmode', 'numeric')
          .attr('aria-label', settings.aria.jumpLabel)
          .val(state.page);

        const $btn = $('<button type="button" class="btn btn-outline-secondary btn-sm">').text('Go');
        $btn.attr('aria-label', settings.aria.jumpLabel);

        function submit() {
          const v = parseInt($input.val(), 10);
          if (!Number.isFinite(v)) return;
          goToPage(v);
        }

        $btn.on('click', submit);
        $input.on('keydown', function (e) {
          if (e.key === 'Enter') submit();
        });

        const $label = $('<span class="small text-secondary">').text('Page');
        $jumpWrap.empty().addClass('jqpt-controls').append($label).append($input).append($btn);
      }

      function renderRows(items) {
        if (!items || items.length === 0) {
          $tbody.html(settings.renderEmpty());
          return;
        }
        const parts = [];
        for (let i = 0; i < items.length; i++) {
          parts.push(settings.renderRow(items[i]));
        }
        $tbody.html(parts.join(''));

        $tbody.find('img').each(function () {
          if (!this.getAttribute('loading')) this.setAttribute('loading', 'lazy');
        });
      }

      function applyResponse(resp) {
        const meta = resp && resp.meta ? resp.meta : {};
        state.page = meta.page || state.page;
        state.perPage = meta.per_page || state.perPage;
        state.total = meta.total || 0;
        state.lastPage = meta.last_page || 1;

        renderRows(resp.data || []);
        renderPager();
        renderPerPage();
        renderJump();
        updateStatus();

        $table.trigger('data:loaded', [resp, state]);
      }

      function load() {
        const key = cacheKey();
        const cached = settings.cache.enabled ? cache.get(key) : null;
        if (cached) {
          applyResponse(cached);
          return;
        }

        const requestId = ++state.requestId;
        setLoading(true);

        $.ajax({
          url: settings.url,
          method: settings.method,
          dataType: 'json',
          timeout: settings.timeoutMs,
          global: settings.globalAjaxEvents,
          data: settings.requestData(state),
        })
          .done(function (resp) {
            if (requestId !== state.requestId) return;
            if (settings.cache.enabled) cache.set(key, resp);
            applyResponse(resp);
          })
          .fail(function (xhr, textStatus) {
            if (requestId !== state.requestId) return;
            const isTimeout = textStatus === 'timeout';
            const msg = isTimeout ? 'Request timed out. Please try again.' : 'Failed to load data. Please try again.';
            safeToastr('error', msg);
            $table.trigger('data:error', [xhr, textStatus, state]);
          })
          .always(function () {
            if (requestId !== state.requestId) return;
            setLoading(false);
          });
      }

      function goToPage(p) {
        const last = Math.max(1, state.lastPage || 1);
        const target = clamp(parseInt(p, 10) || 1, 1, last);
        if (target === state.page) return;
        state.page = target;
        $table.trigger('page:change', [state]);
        load();
      }

      function refresh() {
        cache.clear();
        $table.trigger('data:refresh', [state]);
        load();
      }

      if ($search.length) {
        $search.val(state.q);
        $search.attr('aria-label', 'Search');
        $search.on('input', debounce(function () {
          state.q = String($search.val() || '').trim();
          state.page = 1;
          cache.clear();
          load();
        }, settings.searchDebounceMs));
      }

      $table.data('paginatedTable', { refresh: refresh, state: state });

      renderPerPage();
      renderJump();
      load();
    });
  };
})(window.jQuery);
