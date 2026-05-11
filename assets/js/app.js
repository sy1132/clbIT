document.addEventListener('DOMContentLoaded', () => {
    const typeSelect = document.querySelector('[data-post-filter-type]');
    const searchForm = document.querySelector('[data-live-search-form]');

    if (typeSelect && searchForm) {
        typeSelect.addEventListener('change', () => searchForm.submit());
    }

    document.querySelectorAll('[data-auto-submit]').forEach((element) => {
        element.addEventListener('change', () => {
            const form = element.closest('form');
            if (form) {
                form.submit();
            }
        });
    });
});
