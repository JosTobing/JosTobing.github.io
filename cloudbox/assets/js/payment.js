// payment.js - Payment Functions
function openPaymentModal(planId, planName, price) {
    const modal = document.getElementById('paymentModal');
    const planIdInput = document.getElementById('planId');
    const planNameDisplay = document.getElementById('planNameDisplay');
    const planPriceDisplay = document.getElementById('planPriceDisplay');
    
    planIdInput.value = planId;
    planNameDisplay.textContent = `Paket ${planName}`;
    planPriceDisplay.textContent = `Rp ${price.toLocaleString('id-ID')}`;
    
    modal.classList.add('show');
    modal.style.display = 'flex';
}

function closePaymentModal() {
    const modal = document.getElementById('paymentModal');
    modal.classList.remove('show');
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
}

// File preview for payment proof
document.getElementById('paymentProof')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('filePreview');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `
                <div class="preview-container">
                    <img src="${e.target.result}" alt="Payment Proof">
                    <p>${file.name}</p>
                </div>
            `;
        };
        reader.readAsDataURL(file);
    }
});

// Close modal on outside click
window.addEventListener('click', function(e) {
    const modal = document.getElementById('paymentModal');
    if (e.target === modal) {
        closePaymentModal();
    }
});