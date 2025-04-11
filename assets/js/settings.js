document.addEventListener('DOMContentLoaded', function () {
    const checkboxes = document.querySelectorAll('.module-toggle');
    checkboxes.forEach(cb => {
        cb.addEventListener('change', function () {
            const row = document.getElementById('post-types-' + this.value);
            if (row) {
                row.style.display = this.checked ? '' : 'none';
            }
        });
    });
});
