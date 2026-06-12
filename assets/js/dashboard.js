// Function to update fields when a doctor card is clicked
function selectDoctor(id, name) {
    // 1. Update the input fields in the booking panel
    document.getElementById('selected-doctor-id').value = id;
    document.getElementById('selected-doctor-name').value = name;

    // 2. Visual card selection toggle
    const cards = document.querySelectorAll('.doctor-card');
    cards.forEach(card => {
        card.classList.remove('active-card');
    });

    // Find the clicked card container element and add active style
    event.currentTarget.classList.add('active-card');
}

// Restrict calendar input to today or future dates only
document.addEventListener("DOMContentLoaded", function() {
    const dateInput = document.getElementById('appointment-date');
    const today = new Date();
    
    const yyyy = today.getFullYear();
    let mm = today.getMonth() + 1; // Months start at 0
    let dd = today.getDate();

    // Format numbers to have a leading zero if less than 10
    if (mm < 10) mm = '0' + mm;
    if (dd < 10) dd = '0' + dd;

    const minDate = yyyy + '-' + mm + '-' + dd;
    dateInput.setAttribute('min', minDate);
});

// Sidebar dynamic navigation switcher logic
document.querySelectorAll('.nav-tab').forEach(tab => {
    tab.addEventListener('click', function(e) {
        e.preventDefault();

        // 1. Remove active highlights from all links
        document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
        
        // 2. Add active highlight to clicked link
        this.classList.add('active');

        // 3. Hide all panel contents
        document.querySelectorAll('.tab-content').forEach(panel => panel.classList.add('hidden-panel'));

        // 4. Show target workspace panel
        const targetId = this.getAttribute('data-target');
        document.getElementById(targetId).classList.remove('hidden-panel');
    });
});