<?php
require_once 'config/auth.php';
include 'include/header.php';
?>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

<style>
    /* Modern Background with subtle abstract gradients */
    body { 
        background-color: #f4f7fe; 
        background-image: 
            radial-gradient(at 0% 0%, hsla(253,16%,7%,0.03) 0, transparent 50%), 
            radial-gradient(at 50% 0%, hsla(225,39%,30%,0.03) 0, transparent 50%), 
            radial-gradient(at 100% 0%, hsla(339,49%,30%,0.03) 0, transparent 50%);
        font-family: 'Plus Jakarta Sans', sans-serif; 
        min-height: 100vh;
    }

    .search-container { 
        position: relative; 
        max-width: 700px; 
        margin: 80px auto; 
        z-index: 10;
    }

    /* Headings */
    .hero-title {
        font-size: 2.5rem;
        font-weight: 800;
        background: linear-gradient(135deg, #1b2559 0%, #4318ff 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        letter-spacing: -1px;
    }
    .hero-subtitle {
        font-size: 1.05rem;
        color: #64748b;
        font-weight: 500;
    }

    /* True Glassmorphism Card */
    .glass-card { 
        background: rgba(255, 255, 255, 0.7); 
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.8);
        border-radius: 24px; 
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08); 
        padding: 40px; 
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .glass-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 30px 60px -12px rgba(67, 24, 255, 0.12);
    }

    /* Input Field Styling */
    .input-wrapper {
        position: relative;
    }
    .custom-input { 
        border: 2px solid #e2e8f0; 
        border-radius: 16px; 
        padding: 18px 20px 18px 60px; 
        width: 100%; 
        font-size: 1.15rem; 
        font-weight: 500;
        background: #ffffff; 
        color: #1e293b;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .custom-input::placeholder { color: #94a3b8; font-weight: 400; }
    .custom-input:focus { 
        outline: none; 
        border-color: #4318ff; 
        box-shadow: 0 0 0 5px rgba(67, 24, 255, 0.15); 
    }
    .input-icon { 
        position: absolute; 
        left: 22px; 
        top: 50%; 
        transform: translateY(-50%); 
        color: #64748b; 
        font-size: 1.5rem; 
        z-index: 10; 
        transition: color 0.3s ease;
    }
    .custom-input:focus ~ .input-icon { color: #4318ff; }

    /* Loader */
    .search-loader {
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: #4318ff;
        display: none;
        animation: spin 1s linear infinite;
    }
    @keyframes spin { 100% { transform: translateY(-50%) rotate(360deg); } }

    /* Search Results Box with animation */
    .search-results {
        position: absolute; 
        top: calc(100% + 15px); 
        left: 0; 
        right: 0; 
        background: rgba(255, 255, 255, 0.95); 
        backdrop-filter: blur(10px);
        border-radius: 20px;
        box-shadow: 0 20px 40px -10px rgba(0,0,0,0.1); 
        z-index: 1000; 
        max-height: 400px; 
        overflow-y: auto;
        display: none; 
        border: 1px solid rgba(255,255,255,0.5);
        opacity: 0;
        transform: translateY(10px);
        animation: slideUp 0.3s forwards ease-out;
    }
    @keyframes slideUp {
        to { opacity: 1; transform: translateY(0); }
    }

    /* Custom Scrollbar for results */
    .search-results::-webkit-scrollbar { width: 8px; }
    .search-results::-webkit-scrollbar-track { background: transparent; }
    .search-results::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    
    /* Result Items injected by AJAX */
    .result-item { 
        padding: 18px 25px; 
        border-bottom: 1px solid #f1f5f9; 
        cursor: pointer; 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        text-decoration: none; 
        transition: all 0.2s; 
        background: transparent;
    }
    .result-item:hover { 
        background: #f8fafc; 
        text-decoration: none;
        padding-left: 30px; /* Micro-interaction on hover */
    }
    .result-item:last-child { border-bottom: none; }
    .company-title { font-size: 1.15rem; color: #0f172a; font-weight: 700; margin: 0 0 4px 0; }
    .contact-name { font-size: 0.9rem; color: #64748b; margin: 0; display: flex; align-items: center; gap: 5px; }
    
    /* Badges */
    .badge-lead { 
        background: linear-gradient(135deg, rgba(67, 24, 255, 0.1), rgba(67, 24, 255, 0.05)); 
        color: #4318ff; 
        padding: 6px 14px; 
        border-radius: 30px; 
        font-size: 0.75rem; 
        font-weight: 700; 
        border: 1px solid rgba(67, 24, 255, 0.2); 
        box-shadow: 0 2px 10px rgba(67,24,255,0.05);
    }
    .badge-customer { 
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05)); 
        color: #10b981; 
        padding: 6px 14px; 
        border-radius: 30px; 
        font-size: 0.75rem; 
        font-weight: 700; 
        border: 1px solid rgba(16, 185, 129, 0.2); 
        box-shadow: 0 2px 10px rgba(16,185,129,0.05);
    }
</style>

<div class="container-fluid py-5">
    <div class="search-container">
        <div class="text-center mb-5">
            <h2 class="hero-title">Initiate New Sale</h2>
            <p class="hero-subtitle mt-2">Find an existing Lead or Customer to lock the deal</p>
        </div>
        
        <div class="glass-card">
            <div class="input-wrapper">
                <input type="text" id="searchInput" class="custom-input" placeholder="Type Company or Client Name..." autocomplete="off">
                <i class="ti ti-search input-icon"></i>
                <i class="ti ti-loader search-loader" id="searchLoader"></i>
            </div>
            
            <div id="searchResults" class="search-results"></div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function(){
    // Debounce Timer variable setup
    let typingTimer;
    let doneTypingInterval = 400; // 400 milliseconds wait karega type karne ke baad

    $('#searchInput').on('keyup', function(){
        clearTimeout(typingTimer);
        var query = $(this).val();
        
        if(query.length > 1){
            // Show loader while waiting
            $('#searchLoader').show();
            
            typingTimer = setTimeout(function() {
                $.ajax({
                    url: "ajax-search.php", 
                    method: "POST",
                    data: {search: query},
                    success: function(data){
                        $('#searchLoader').hide(); // Hide loader
                        $('#searchResults').html(data);
                        $('#searchResults').css('display', 'block'); // Use CSS display for animation
                    }
                });
            }, doneTypingInterval);
        } else {
            $('#searchLoader').hide();
            $('#searchResults').hide();
            $('#searchResults').html("");
        }
    });

    // Handle backspace/delete quickly
    $('#searchInput').on('keydown', function () {
        clearTimeout(typingTimer);
    });

    // Kahi bahar click karne par dropdown band ho jaye
    $(document).on('click', function (e) {
        if ($(e.target).closest(".glass-card").length === 0) {
            $("#searchResults").hide();
        }
    });
});
</script>

<?php include 'include/footer.php'; ?>