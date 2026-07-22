@extends('layouts.app')

@section('title', 'PRC Queue Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-body py-2">
            <form id="filterForm" class="row g-3 align-items-center">
                <div class="col-auto">
                    <label class="fw-bold">Period:</label>
                </div>
                <div class="col-auto">
                    <select class="form-select" id="periodSelect" name="period">
                        <option value="today" selected>Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>
                <div class="col-auto d-none custom-date-group">
                    <input type="date" class="form-control" name="start_date" id="startDate">
                </div>
                <div class="col-auto d-none custom-date-group">
                    <span>to</span>
                </div>
                <div class="col-auto d-none custom-date-group">
                    <input type="date" class="form-control" name="end_date" id="endDate">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary" id="refreshBtn">
                        <i class="bi bi-search"></i> Go
                    </button>
                </div>
                <div class="col-auto ms-auto">
                    <span class="text-muted small" id="lastUpdated"></span>
                </div>
            </form>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row mb-4">
        <!-- Total Tickets -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2 border-0 border-start border-4 border-primary">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Tickets</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800" id="kpiTotal">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-ticket-perforated fs-1 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Waiting -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2 border-0 border-start border-4 border-warning">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Waiting</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800" id="kpiWaiting">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-hourglass-split fs-1 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Served -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2 border-0 border-start border-4 border-success">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Served / Serving</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800" id="kpiServed">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-check-circle fs-1 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Avg Wait Time -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2 border-0 border-start border-4 border-info">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Avg Wait Time</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><span id="kpiAvgWait">-</span> <small class="text-muted fs-6">min</small></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-clock-history fs-1 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Ticket Volume Overview</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="position: relative; height: 320px;">
                        <canvas id="timelineChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Status Distribution</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie" style="position: relative; height: 320px;">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Transaction Type Volume</h6>
                </div>
                <div class="card-body">
                    <div class="chart-bar" style="position: relative; height: 320px;">
                        <canvas id="transactionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('vendor/chartjs/chart.umd.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Elements
        const els = {
            periodSelect: document.getElementById('periodSelect'),
            customDateGroups: document.querySelectorAll('.custom-date-group'),
            refreshBtn: document.getElementById('refreshBtn'),
            filterForm: document.getElementById('filterForm'),
            lastUpdated: document.getElementById('lastUpdated'),
            kpi: {
                total: document.getElementById('kpiTotal'),
                waiting: document.getElementById('kpiWaiting'),
                served: document.getElementById('kpiServed'),
                avgWait: document.getElementById('kpiAvgWait'),
            }
        };

        // Charts
        let timelineChart = null;
        let statusChart = null;
        let transactionChart = null;

        // Toggle Custom Date inputs
        els.periodSelect.addEventListener('change', function() {
            if (this.value === 'custom') {
                els.customDateGroups.forEach(el => el.classList.remove('d-none'));
            } else {
                els.customDateGroups.forEach(el => el.classList.add('d-none'));
            }
        });

        // Form Submit
        els.filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            fetchData();
        });

        // Fetch Data
        async function fetchData() {
            // Set loading state
            els.refreshBtn.disabled = true;
            els.refreshBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...';
            document.body.style.cursor = 'wait';

            try {
                const formData = new FormData(els.filterForm);
                const params = new URLSearchParams(formData);

                const response = await fetch(`{{ route('dashboard.stats') }}?${params.toString()}`);
                if (!response.ok) throw new Error('Failed to fetch data');

                const data = await response.json();
                updateDashboard(data);
                
                // Update timestamp
                const now = new Date();
                els.lastUpdated.textContent = 'Updated: ' + now.toLocaleTimeString();

            } catch (error) {
                console.error(error);
                alert('Error loading dashboard data');
            } finally {
                els.refreshBtn.disabled = false;
                els.refreshBtn.innerHTML = '<i class="bi bi-search"></i> Go';
                document.body.style.cursor = 'default';
            }
        }

        // Update Dashboard
        function updateDashboard(data) {
            // Update KPIs
            els.kpi.total.textContent = data.kpi.total;
            els.kpi.waiting.textContent = data.kpi.waiting;
            els.kpi.served.textContent = data.kpi.served;
            els.kpi.avgWait.textContent = data.kpi.avg_wait;

            // Update Charts
            updateTimelineChart(data.charts.timeline);
            updateStatusChart(data.charts.status);
            updateTransactionChart(data.charts.transaction);
        }

        // 1. Timeline Chart
        function updateTimelineChart(data) {
            const ctx = document.getElementById('timelineChart').getContext('2d');
            
            if (timelineChart) {
                timelineChart.destroy();
            }

            timelineChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Tickets Created',
                        data: data.data,
                        backgroundColor: 'rgba(78, 115, 223, 0.05)',
                        borderColor: 'rgba(78, 115, 223, 1)',
                        pointRadius: 3,
                        pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                        pointBorderColor: 'rgba(78, 115, 223, 1)',
                        pointHoverRadius: 3,
                        pointHoverBackgroundColor: 'rgba(78, 115, 223, 1)',
                        pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                        pointHitRadius: 10,
                        pointBorderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    layout: { padding: { left: 10, right: 25, top: 25, bottom: 0 } },
                    scales: {
                        x: { grid: { display: false, drawBorder: false }, ticks: { maxTicksLimit: 12 } },
                        y: { 
                            ticks: { maxTicksLimit: 5, padding: 10, callback: (val) => val }, 
                            grid: { color: "rgb(234, 236, 244)", zeroLineColor: "rgb(234, 236, 244)", drawBorder: false, borderDash: [2], zeroLineBorderDash: [2] } 
                        },
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: "rgb(255,255,255)",
                            bodyColor: "#858796",
                            titleMarginBottom: 10,
                            titleColor: '#6e707e',
                            titleFont: { size: 14 },
                            borderColor: '#dddfeb',
                            borderWidth: 1,
                            xPadding: 15,
                            yPadding: 15,
                            displayColors: false,
                            intersect: false,
                            mode: 'index',
                            caretPadding: 10,
                        }
                    }
                }
            });
        }

        // 2. Status Chart
        function updateStatusChart(data) {
            const ctx = document.getElementById('statusChart').getContext('2d');
            
            if (statusChart) {
                statusChart.destroy();
            }

            const labels = Object.keys(data).map(s => s.charAt(0).toUpperCase() + s.slice(1));
            const values = Object.values(data);

            statusChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: ['#f6c23e', '#1cc88a', '#36b9cc', '#858796', '#e74a3b'], // waiting (yellow), serving (green), completed (cyan), skipped (gray), cancelled (red)
                        hoverBackgroundColor: ['#dda20a', '#17a673', '#2c9faf', '#60616f', '#be2617'],
                        hoverBorderColor: "rgba(234, 236, 244, 1)",
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            backgroundColor: "rgb(255,255,255)",
                            bodyColor: "#858796",
                            borderColor: '#dddfeb',
                            borderWidth: 1,
                            xPadding: 15,
                            yPadding: 15,
                            displayColors: false,
                            caretPadding: 10,
                        },
                    },
                    cutout: '70%',
                },
            });
        }

        // 3. Transaction Chart
        function updateTransactionChart(data) {
            const ctx = document.getElementById('transactionChart').getContext('2d');
            
            if (transactionChart) {
                transactionChart.destroy();
            }

            transactionChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Tickets',
                        data: data.data,
                        backgroundColor: '#4e73df',
                        hoverBackgroundColor: '#2e59d9',
                        borderColor: "#4e73df",
                        maxBarThickness: 50,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    layout: { padding: { left: 10, right: 25, top: 25, bottom: 0 } },
                    scales: {
                        x: { grid: { display: false, drawBorder: false }, ticks: { maxTicksLimit: 10 } },
                        y: { 
                            ticks: { maxTicksLimit: 5, padding: 10, callback: (val) => val }, 
                            grid: { color: "rgb(234, 236, 244)", zeroLineColor: "rgb(234, 236, 244)", drawBorder: false, borderDash: [2], zeroLineBorderDash: [2] } 
                        },
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: "rgb(255,255,255)",
                            bodyColor: "#858796",
                            titleMarginBottom: 10,
                            titleColor: '#6e707e',
                            titleFont: { size: 14 },
                            borderColor: '#dddfeb',
                            borderWidth: 1,
                            xPadding: 15,
                            yPadding: 15,
                            displayColors: false,
                            intersect: false,
                            mode: 'index',
                            caretPadding: 10,
                        }
                    }
                }
            });
        }

        // Initial Load
        fetchData();
    });
</script>
@endpush
@endsection
