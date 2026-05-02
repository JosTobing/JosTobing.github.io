// admin.js - Admin Functions
class CloudBoxAdmin {
    constructor() {
        this.init();
    }
    
    init() {
        this.setupSidebar();
        this.setupDataTables();
        this.loadStatistics();
    }
    
    setupSidebar() {
        const toggle = document.querySelector('.sidebar-toggle');
        const sidebar = document.querySelector('.admin-sidebar');
        
        if (toggle && sidebar) {
            toggle.addEventListener('click', () => {
                sidebar.classList.toggle('show');
            });
        }
    }
    
    setupDataTables() {
        // Search functionality
        const searchInput = document.querySelector('.header-search input');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.searchTable(e.target.value);
            });
        }
    }
    
    searchTable(query) {
        const tables = document.querySelectorAll('.admin-table tbody tr');
        const searchTerm = query.toLowerCase();
        
        tables.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    }
    
    async loadStatistics() {
        try {
            const response = await fetch('../api/admin_api.php?action=stats');
            const stats = await response.json();
            
            this.updateStats(stats);
        } catch (error) {
            console.error('Failed to load stats:', error);
        }
    }
    
    updateStats(stats) {
        // Update stat cards
        const statCards = document.querySelectorAll('.stat-info h3');
        if (statCards.length >= 4) {
            statCards[0].textContent = stats.customers || 0;
            statCards[1].textContent = stats.files || 0;
            statCards[2].textContent = stats.storage || '0 MB';
            statCards[3].textContent = stats.revenue || 'Rp 0';
        }
    }
}

// Initialize admin
document.addEventListener('DOMContentLoaded', () => {
    new CloudBoxAdmin();
});