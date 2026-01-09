document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('cmb-modal-overlay');
    const closeBtn = document.querySelector('.cmb-modal-close');
    const form = document.getElementById('cmb-callback-form');
    const messageDiv = document.getElementById('cmb-message');
    const phoneInput = document.querySelector("#cmb-phone");
    
    let iti;
    if (phoneInput) {
        iti = window.intlTelInput(phoneInput, {
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
            preferredCountries: ['us', 'gb'],
            separateDialCode: true,
            autoPlaceholder: "aggressive"
        });
    }

    if (!modal) return;

    // Open Modal (Delegation for multiple buttons)
    document.body.addEventListener('click', function(e) {
        if (e.target.matches('#cmb-floating-btn, .cmb-trigger-btn, .cmb-trigger-btn *')) {
            modal.classList.add('active');
        }
    });

    // Close Modal
    closeBtn.addEventListener('click', function() {
        modal.classList.remove('active');
    });

    // Close on click outside
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.remove('active');
        }
    });

    // Form Submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = form.querySelector('.cmb-submit-btn');
        const originalText = submitBtn.innerText;
        
        submitBtn.disabled = true;
        submitBtn.innerText = 'Sending...';
        messageDiv.style.display = 'none';

        const formData = new FormData(form);
        
        // If intl-tel-input is active, get the full international number
        if (iti) {
            if (iti.isValidNumber()) {
                const fullNumber = iti.getNumber();
                formData.set('cmb_phone', fullNumber);
            } else {
                // If invalid, we can either submit as is (let backend handle) or show error.
                // For now let's submit what we have, but maybe prepend dial code if needed.
                // Or better, if invalid, maybe we should stop?
                // The prompt didn't strictly require validation, but robust code should.
                // However, to keep it simple and safe:
                const fullNumber = iti.getNumber();
                formData.set('cmb_phone', fullNumber);
            }
        }

        formData.append('action', 'cmb_submit_request');
        formData.append('nonce', cmb_obj.nonce);

        fetch(cmb_obj.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            messageDiv.style.display = 'block';
            if (data.success) {
                messageDiv.className = 'cmb-message success';
                messageDiv.innerText = data.data.message;
                form.reset();
                setTimeout(() => {
                    modal.classList.remove('active');
                    messageDiv.style.display = 'none';
                }, 3000);
            } else {
                messageDiv.className = 'cmb-message error';
                messageDiv.innerText = data.data.message || 'An error occurred';
            }
        })
        .catch(error => {
            messageDiv.style.display = 'block';
            messageDiv.className = 'cmb-message error';
            messageDiv.innerText = 'Connection error. Please try again.';
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerText = originalText;
        });
    });
});
