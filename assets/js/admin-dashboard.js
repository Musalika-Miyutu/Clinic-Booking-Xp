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