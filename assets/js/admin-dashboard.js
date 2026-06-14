// Admin Panel view switcher logic
document.querySelectorAll('.nav-tab').forEach(tab => {
    tab.addEventListener('click', function(e) {
        e.preventDefault();

        // 1. Remove active styling class from all navigation sidebar elements
        document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
        
        // 2. Assign active visual highlighting to the element clicked
        this.classList.add('active');

        // 3. Apply hidden layout class to all workspace main panel areas
        document.querySelectorAll('.tab-content').forEach(panel => panel.classList.add('hidden-panel'));

        // 4. Drop hidden layout class off the targeted workspace panel container
        const targetId = this.getAttribute('data-target');
        document.getElementById(targetId).classList.remove('hidden-panel');
    });
});

// Function to update appointment status using AJAX Fetch API
function updateStatus(appointmentId, newStatus) {
    // Create form data payload to send to our PHP backend script
    const formData = new FormData();
    formData.append('appointment_id', appointmentId);
    formData.append('status', newStatus);

    // Send asynchronous background request
    fetch('handlers/update-status.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 1. Locate the status badge on the screen and swap its text
            const badge = document.getElementById(`status-badge-${appointmentId}`);
            badge.innerText = newStatus;

            // 2. Clear old CSS status classes and apply the new color class instantly
            badge.className = `status-badge status-${newStatus}`;

            // 3. Remove the action buttons and replace them with a "Processed" label
            const actionsContainer = document.getElementById(`actions-${appointmentId}`);
            if (actionsContainer) {
                actionsContainer.parentElement.innerHTML = `<span style='color:#94a3b8; font-size:0.85rem; font-style:italic;'>Processed</span>`;
            }
        } else {
            alert('Error updating status: ' + data.message);
        }
    })
    .catch(error => {
        console.error('AJAX Error:', error);
        alert('An unexpected error occurred connection-wise.');
    });
}