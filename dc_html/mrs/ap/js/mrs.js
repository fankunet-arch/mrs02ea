(function() {
    const toggleAll = document.getElementById('toggle-all');
    if (toggleAll) {
        toggleAll.addEventListener('change', function() {
            document.querySelectorAll('.row-check').forEach(cb => {
                cb.checked = toggleAll.checked;
            });
        });
    }
})();
