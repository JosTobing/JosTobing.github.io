// main.js - Customer Functions
class CloudBoxCustomer {
    constructor() {
        this.init();
    }
    
    init() {
        this.setupMobileMenu();
        this.setupFileUpload();
        this.setupNotifications();
        this.loadNotifications();
    }
    
    setupMobileMenu() {
        const menuToggle = document.querySelector('.menu-toggle');
        const sidebar = document.getElementById('sidebar');
        
        if (menuToggle && sidebar) {
            menuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('show');
                this.toggleOverlay();
            });
        }
        
        // Close sidebar when clicking outside
        document.addEventListener('click', (e) => {
            if (sidebar && sidebar.classList.contains('show') && 
                !sidebar.contains(e.target) && 
                !e.target.classList.contains('menu-toggle')) {
                sidebar.classList.remove('show');
                this.removeOverlay();
            }
        });
    }
    
    toggleOverlay() {
        let overlay = document.querySelector('.sidebar-overlay');
        
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            document.body.appendChild(overlay);
            
            overlay.addEventListener('click', () => {
                const sidebar = document.getElementById('sidebar');
                sidebar.classList.remove('show');
                overlay.remove();
            });
        }
        
        overlay.classList.toggle('show');
    }
    
    removeOverlay() {
        const overlay = document.querySelector('.sidebar-overlay');
        if (overlay) {
            overlay.remove();
        }
    }
    
    setupFileUpload() {
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        
        if (uploadArea && fileInput) {
            uploadArea.addEventListener('click', () => fileInput.click());
            
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.classList.add('drag-over');
            });
            
            uploadArea.addEventListener('dragleave', () => {
                uploadArea.classList.remove('drag-over');
            });
            
            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.classList.remove('drag-over');
                const files = e.dataTransfer.files;
                this.uploadFiles(files);
            });
            
            fileInput.addEventListener('change', (e) => {
                this.uploadFiles(e.target.files);
            });
        }
    }
    
    async uploadFiles(files) {
        for (let file of files) {
            if (file.size > 104857600) {
                this.showNotification('File terlalu besar. Maksimal 100MB', 'error');
                continue;
            }
            
            const formData = new FormData();
            formData.append('file', file);
            
            try {
                const response = await fetch('../api/upload.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.showNotification('File berhasil diupload!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    this.showNotification(result.message || 'Upload gagal', 'error');
                }
            } catch (error) {
                this.showNotification('Upload gagal: ' + error.message, 'error');
            }
        }
    }
    
    setupNotifications() {
        // Check for new notifications every 30 seconds
        setInterval(() => this.loadNotifications(), 30000);
    }
    
    async loadNotifications() {
        try {
            const response = await fetch('../api/notifications.php');
            const notifications = await response.json();
            
            this.updateNotificationBadge(notifications.length);
        } catch (error) {
            console.error('Failed to load notifications:', error);
        }
    }
    
    updateNotificationBadge(count) {
        const badge = document.querySelector('.notification-btn .badge');
        if (badge) {
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }
    }
    
    showNotification(message, type = 'info') {
        // Create toast notification
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
            <span>${message}</span>
        `;
        
        document.body.appendChild(toast);
        
        // Add styles if not exists
        if (!document.getElementById('toast-styles')) {
            const style = document.createElement('style');
            style.id = 'toast-styles';
            style.textContent = `
                .toast {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 15px 25px;
                    border-radius: 8px;
                    color: white;
                    z-index: 10000;
                    animation: slideIn 0.3s ease;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    box-shadow: 0 5px 20px rgba(0,0,0,0.2);
                    max-width: 350px;
                }
                .toast-success { background: #27AE60; }
                .toast-error { background: #E74C3C; }
                .toast-info { background: #3498DB; }
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOut {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        }
        
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}

// Global functions
function downloadFile(fileId) {
    window.location.href = `../api/download.php?id=${fileId}`;
}

function shareFile(fileId) {
    // Copy share link
    const shareLink = `${window.location.origin}/cloudbox/api/download.php?id=${fileId}&share=1`;
    navigator.clipboard.writeText(shareLink).then(() => {
        const cloudbox = new CloudBoxCustomer();
        cloudbox.showNotification('Link berhasil disalin!', 'success');
    });
}

async function deleteFile(fileId) {
    if (confirm('Anda yakin ingin menghapus file ini?')) {
        try {
            const response = await fetch('../api/delete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ file_id: fileId })
            });
            
            const result = await response.json();
            
            if (result.success) {
                const cloudbox = new CloudBoxCustomer();
                cloudbox.showNotification('File berhasil dihapus', 'success');
                setTimeout(() => location.reload(), 1000);
            }
        } catch (error) {
            console.error('Delete failed:', error);
        }
    }
}

function toggleMobileMenu() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('show');
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    new CloudBoxCustomer();
});