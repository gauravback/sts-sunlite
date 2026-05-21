<?php
require_once 'config/auth.php';
include './include/header.php';

// ==========================================
// 🚀 DYNAMIC ROLE-BASED LOGIC (ADMIN VS USER)
// ==========================================
$user_id = $_SESSION['user_id'] ?? 1; // Example user ID
$role = $_SESSION['role'] ?? 'admin'; // Change this to 'user' to test the user view!

// Variables initialize
$total_leads = $total_customers = $total_sales = $total_gov_sales = 0;

// Arrays for Chart Data
$chart_deals_stage = [];
$chart_lost_deals = [];
$chart_won_revenue = [];
$chart_deals_created = [];
$chart_deals_won = [];

if ($role === 'admin') {
    // ----------------------------------------
    // ADMIN: Fetch Total Counts & Chart Data for ALL users
    // ----------------------------------------
    $total_leads = 1250;
    $total_customers = 840;
    $total_sales = 425;
    $total_gov_sales = 150;
    $dashboard_title = "Admin Overview Dashboard";

    // Admin Chart Data (Large Numbers for everyone)
    $chart_deals_stage = [440, 550, 130, 330]; // Discovery, Proposal, Negotiation, Won
    $chart_lost_deals = [120, 180, 90, 240, 150, 100]; // Jan-Jun
    $chart_won_revenue = [310000, 400000, 280000, 510000, 420000, 690000]; // Jan-Jun
    $chart_deals_created = [450, 520, 380, 240, 330, 460, 590, 780, 510, 420, 600, 850]; // Jan-Dec
    $chart_deals_won = [250, 300, 200, 150, 220, 350, 400, 500, 350, 280, 450, 600]; // Jan-Dec

} else {
    // ----------------------------------------
    // USER: Fetch Total Counts & Chart Data for THIS user only
    // ----------------------------------------
    $total_leads = 120;
    $total_customers = 45;
    $total_sales = 20;
    $total_gov_sales = 5;
    $dashboard_title = "My Deals Dashboard";

    // User Chart Data (Smaller Numbers for personal performance)
    $chart_deals_stage = [14, 25, 8, 13]; 
    $chart_lost_deals = [2, 5, 1, 4, 3, 2]; 
    $chart_won_revenue = [12000, 15000, 8000, 21000, 18000, 29000]; 
    $chart_deals_created = [15, 22, 18, 14, 13, 26, 19, 28, 21, 12, 20, 25]; 
    $chart_deals_won = [5, 10, 8, 5, 8, 15, 10, 18, 12, 8, 15, 20]; 
}

// Convert PHP Arrays to JSON so JavaScript can read them for Graphs
$json_deals_stage = json_encode($chart_deals_stage);
$json_lost_deals = json_encode($chart_lost_deals);
$json_won_revenue = json_encode($chart_won_revenue);
$json_deals_created = json_encode($chart_deals_created);
$json_deals_won = json_encode($chart_deals_won);
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.44.0/tabler-icons.min.css">
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<style>
    /* Add your previous CSS styling here (Skipping to keep code short, copy from previous chat) */
    :root { --bg-body: #f4f7fe; --bg-surface: #ffffff; --text-main: #1e293b; --text-muted: #64748b; --border-color: #e2e8f0; --primary: #4318FF; --success: #05cd99; --danger: #ee5d50; --warning: #ffce20; --info: #0ea5e9; --shadow-card: 0px 10px 20px rgba(0, 0, 0, 0.03); }
    body { background-color: var(--bg-body); font-family: 'Inter', sans-serif; color: var(--text-main); overflow-x: hidden; }
    .page-wrapper { padding: 2rem 0; }
    .content-modern { max-width: 1400px; margin: 0 auto; padding: 0 1.5rem; }
    .card-modern { background: var(--bg-surface); border: 1px solid rgba(226, 232, 240, 0.6); border-radius: 20px; box-shadow: var(--shadow-card); height: 100%; transition: transform 0.3s ease; }
    .card-modern:hover { transform: translateY(-4px); box-shadow: 0px 15px 25px rgba(0, 0, 0, 0.06); }
    .card-header-modern { background: transparent; border-bottom: 1px solid var(--border-color); padding: 1.25rem 1.5rem; border-radius: 20px 20px 0 0; }
    .card-title-text { font-size: 1.1rem; font-weight: 700; color: var(--text-main); margin: 0; }
    .btn-outline-light-modern { background: #f8fafc; border: 1px solid var(--border-color); color: var(--text-muted); font-weight: 500; font-size: 0.85rem; border-radius: 10px; padding: 8px 16px; }
    .date-picker-box { background: white; border: 1px solid var(--border-color); padding: 8px 16px; border-radius: 10px; font-weight: 600; font-size: 0.85rem; }
    .deal-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: white; }
    .summary-icon { width: 56px; height: 56px; border-radius: 14px; font-size: 1.8rem; }
    .table-modern { width: 100%; border-collapse: separate; border-spacing: 0; min-width: 700px; }
    .table-modern thead th { background: #f8fafc; color: var(--text-muted); font-weight: 600; font-size: 0.75rem; text-transform: uppercase; padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color); white-space: nowrap; }
    .table-modern tbody td { padding: 1rem 1.5rem; vertical-align: middle; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; color: var(--text-main); white-space: nowrap; }
    .badge-status { padding: 6px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: 600; }
    .badge-progress { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
    .badge-won { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
    .badge-lost { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
</style>

<div class="">
    <div class="content-modern">

        <div class="d-flex align-items-center justify-content-between gap-3 mb-4 flex-wrap">
            <div>
                <h3 class="fw-bold mb-1" style="color: var(--text-main); letter-spacing: -0.5px;"><?php echo $dashboard_title; ?></h3>
                <p class="text-muted mb-0" style="font-size: 0.9rem;">
                    <?php echo $role === 'admin' ? 'Company-wide pipeline and performance overview.' : 'Your personal pipeline and sales performance.'; ?>
                </p>
            </div>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="date-picker-box d-flex align-items-center">
                    <i class="ti ti-calendar-event text-primary me-2 fs-5"></i>
                    <span>23 May 2025 - 30 May 2025</span>
                </div>  
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-sm-6">
                <div class="card card-modern p-4 d-flex flex-row align-items-center gap-3">
                    <div class="deal-icon summary-icon shadow-sm" style="background: linear-gradient(135deg, var(--primary), #8b5cf6);"><i class="ti ti-users-group"></i></div>
                    <div>
                        <p class="text-muted mb-1 fw-medium" style="font-size: 0.85rem; text-transform: uppercase;">Total Leads</p>
                        <h3 class="fw-bold mb-0" style="color: var(--text-main);"><?php echo number_format($total_leads); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="card card-modern p-4 d-flex flex-row align-items-center gap-3">
                    <div class="deal-icon summary-icon shadow-sm" style="background: linear-gradient(135deg, var(--success), #10b981);"><i class="ti ti-user-check"></i></div>
                    <div>
                        <p class="text-muted mb-1 fw-medium" style="font-size: 0.85rem; text-transform: uppercase;">Total Customers</p>
                        <h3 class="fw-bold mb-0" style="color: var(--text-main);"><?php echo number_format($total_customers); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="card card-modern p-4 d-flex flex-row align-items-center gap-3">
                    <div class="deal-icon summary-icon shadow-sm" style="background: linear-gradient(135deg, var(--warning), #f59e0b);"><i class="ti ti-businessplan"></i></div>
                    <div>
                        <p class="text-muted mb-1 fw-medium" style="font-size: 0.85rem; text-transform: uppercase;">Corporate Sales</p>
                        <h3 class="fw-bold mb-0" style="color: var(--text-main);"><?php echo number_format($total_sales); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="card card-modern p-4 d-flex flex-row align-items-center gap-3">
                    <div class="deal-icon summary-icon shadow-sm" style="background: linear-gradient(135deg, var(--info), #38bdf8);"><i class="ti ti-building-monument"></i></div>
                    <div>
                        <p class="text-muted mb-1 fw-medium" style="font-size: 0.85rem; text-transform: uppercase;">Govt. Sales</p>
                        <h3 class="fw-bold mb-0" style="color: var(--text-main);"><?php echo number_format($total_gov_sales); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-7 col-lg-6 d-flex">       
                <div class="card card-modern flex-fill w-100">
                    <div class="card-header-modern"><h6 class="card-title-text">Recently Created Deals</h6></div>
                    <div class="card-body p-4 text-center text-muted">
                        <i>[Recent deals table HTML goes here]</i>
                    </div>
                </div>
            </div> 

            <div class="col-xl-5 col-lg-6 d-flex">       
                <div class="card card-modern flex-fill w-100">
                    <div class="card-header-modern d-flex justify-content-between">
                        <h6 class="card-title-text">Deals Pipeline Stage</h6>
                    </div>
                    <div class="card-body d-flex align-items-center justify-content-center p-4">
                        <div id="deals-chart" style="width: 100%; min-height: 300px;"></div>
                    </div>
                </div>
            </div> 
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-6 d-flex">       
                <div class="card card-modern flex-fill w-100">
                    <div class="card-header-modern d-flex justify-content-between">
                        <h6 class="card-title-text">Lost Deals Analysis</h6>
                    </div>
                    <div class="card-body p-4">
                        <div id="last-chart" style="min-height: 280px;"></div>
                    </div>
                </div>
            </div> 
            <div class="col-md-6 d-flex">   
                <div class="card card-modern flex-fill w-100">
                    <div class="card-header-modern d-flex justify-content-between">
                        <h6 class="card-title-text">Won Deals Revenue</h6>
                    </div>
                    <div class="card-body p-4">
                        <div id="won-chart" style="min-height: 280px;"></div>
                    </div>
                </div>
            </div> 
        </div>

        <div class="row g-4">
            <div class="col-md-12 d-flex">      
                <div class="card card-modern flex-fill w-100">
                    <div class="card-header-modern d-flex justify-content-between">
                        <h6 class="card-title-text">Deals Performance by Year</h6>
                    </div>
                    <div class="card-body p-4">
                        <div id="deals-year" style="min-height: 350px;"></div>
                    </div>
                </div>
            </div> 
        </div>

    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    
    // PHP variables injected into JavaScript!
    const chartDataStage = <?php echo $json_deals_stage; ?>;
    const chartDataLost = <?php echo $json_lost_deals; ?>;
    const chartDataRevenue = <?php echo $json_won_revenue; ?>;
    const chartDataCreated = <?php echo $json_deals_created; ?>;
    const chartDataWon = <?php echo $json_deals_won; ?>;

    // 1. Deals By Stage (Donut Chart)
    var optionsDonut = {
        series: chartDataStage,  // <-- DYNAMIC DATA HERE
        chart: { type: 'donut', height: 320, fontFamily: 'Inter, sans-serif' },
        labels: ['Discovery', 'Proposal', 'Negotiation', 'Won'],
        colors: ['#4318FF', '#05cd99', '#ffce20', '#111c43'],
        plotOptions: { pie: { donut: { size: '75%', labels: { show: true, name: { show: true }, value: { show: true, fontSize: '24px', fontWeight: 700 } } } } },
        dataLabels: { enabled: false },
        stroke: { width: 0 },
        legend: { position: 'bottom', markers: { radius: 12 } }
    };
    new ApexCharts(document.querySelector("#deals-chart"), optionsDonut).render();

    // 2. Lost Deals (Bar Chart)
    var optionsLost = {
        series: [{ name: 'Lost Deals', data: chartDataLost }], // <-- DYNAMIC DATA HERE
        chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
        colors: ['#ee5d50'],
        plotOptions: { bar: { borderRadius: 6, columnWidth: '40%' } },
        dataLabels: { enabled: false },
        xaxis: { categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'], axisBorder: { show: false }, axisTicks: { show: false } },
        grid: { borderColor: '#e2e8f0', strokeDashArray: 4, yaxis: { lines: { show: true } } }
    };
    new ApexCharts(document.querySelector("#last-chart"), optionsLost).render();

    // 3. Won Deals Revenue (Smooth Spline Area Chart)
    var optionsWon = {
        series: [{ name: 'Revenue ($)', data: chartDataRevenue }], // <-- DYNAMIC DATA HERE
        chart: { type: 'area', height: 280, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
        colors: ['#05cd99'],
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 100] } },
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 3 },
        xaxis: { categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'], axisBorder: { show: false }, axisTicks: { show: false } },
        grid: { borderColor: '#e2e8f0', strokeDashArray: 4 }
    };
    new ApexCharts(document.querySelector("#won-chart"), optionsWon).render();

    // 4. Deals By Year (Mixed Bar & Line Chart)
    var optionsYear = {
        series: [
            { name: 'Deals Created', type: 'column', data: chartDataCreated }, // <-- DYNAMIC DATA HERE
            { name: 'Deals Won', type: 'line', data: chartDataWon }           // <-- DYNAMIC DATA HERE
        ],
        chart: { height: 350, type: 'line', toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
        stroke: { width: [0, 3], curve: 'smooth' },
        colors: ['#e0e7ff', '#4318FF'],
        plotOptions: { bar: { borderRadius: 6, columnWidth: '35%' } },
        xaxis: { categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'], axisBorder: { show: false }, axisTicks: { show: false } },
        grid: { borderColor: '#e2e8f0', strokeDashArray: 4 },
        legend: { position: 'top', horizontalAlign: 'right', markers: { radius: 12 } }
    };
    new ApexCharts(document.querySelector("#deals-year"), optionsYear).render();

});
</script>

<?php include './include/footer.php';?>